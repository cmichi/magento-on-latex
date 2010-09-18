<?php
/**
 * Magento on LaTeX Extension
 *
 *
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

class Mage_Sales_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
{

	public function getPdf($invoices = array())
	{
		$ioObject = new Varien_Io_File();			
		$DS = $ioObject->dirsep();
		
		$mediaDir 		 = Mage::getBaseDir('media') . $DS . 'latexinvoice';
		$outputDir	  	 = Mage::getBaseDir('media') . $DS . 'latexinvoice' . $DS . 'tmp';
		$extDir 		 = Mage::getBaseDir('app') . $DS . 'code' . $DS . 'local' . $DS . 'Mage';
		$filename 		 = 'invoice_'.time();
		$texFile		 = $outputDir . $DS . $filename . '.tex';
		$compiledTexFile = $outputDir . $DS . $filename . '.pdf';


		// is there a template in dir?
		if (file_exists($mediaDir . $DS . 'template.tex'))
			$markup = file_get_contents($mediaDir . $DS . 'template.tex');
		else
			// copy the template from the extension directory
			$markup = file_get_contents($extDir . $DS . 'template' . $DS .'template.tex');

		// is there a template.lco in tmp dir?
		$lco = $outputDir . $DS . 'template.lco';
		if (!file_exists($lco))
			shell_exec('cp ' . $mediaDir . $DS .'template.lco '.$lco);
	




		foreach ($invoices as $invoice):
			$order = $invoice->getOrder();
			$data = $order['_origData:protected'];
			$shipping = $order->getShippingAddress();
			
			#$shipping = $shipping['_order:protected'];
			// substitute vars in $markup
			$substitutions = array(
				'Receiver:Name' => $shipping->getName(), //$data['customer_firstname'].' '.$data['customer_lastname'],
				'Receiver:Street' => $shipping->getStreetFull(),
				'Receiver:Zip' => $shipping->getPostcode(),
				'Receiver:City' => $shipping->getCity(),
				'Order:No' => $order->getIncrementId()
			);
			
			foreach ($substitutions as $key => $value)
				$markup = str_replace("($key)", $value, $markup);

			
			// alles zwischen %(Order:ItemsStart) und %(Order:ItemsEnd) ersetzen
			$pos1 = strpos($markup, '%(Order:ItemsStart)');
			$pos2 = strpos($markup, '%(Order:ItemsEnd)') + strlen('%(Order:ItemsEnd)');

			echo '<pre>';
			
			$orders = '';
			foreach ($invoice->getAllItems() as $item){
			    if ($item->getOrderItem()->getParentItem())
			        continue;
			
				$orderItem = $item->getOrderItem();
				$orders .= $orderItem->getSKU() . ' & '  . $orderItem->getName() .' & '.
						   $orderItem->getQtyInvoiced() .'  & ' . $orderItem->getOriginalPrice() . ' \euro{} & ' . 
						   $orderItem->getRowTotal() . '\euro{}';
				$orders .= '\\\\';
				print_r($orders);
			}
			
			$markup = substr($markup, 0, $pos1) . $orders . substr($markup, $pos2, strlen($markup));



			print_r($orders);
			echo '</pre>';
		endforeach;
		
		//if (is_writable($texFile)) {
	    if (!$handle = fopen($texFile, "w+"))
	         die("Unable to OPEN $texFile! Check rights.");

	    if (!fwrite($handle, $markup))
	         die("Unable to WRITE $texFile! Check rights.");
		
		if (!file_exists($texFile))
			die('not existing: ' . $texFile);
				
		$tmpFolder = 'media' . $DS . 'latexinvoice' . $DS . 'tmp' . $DS;
		$cmd = "/usr/texbin/pdflatex -output-directory $tmpFolder $tmpFolder" . $filename . '.tex';
		$output = shell_exec($cmd);								
		shell_exec('rm ' . $tmpFolder . $filename . '.tex');
		shell_exec('rm ' . $tmpFolder . $filename . '.aux');
		shell_exec('rm ' . $tmpFolder . $filename . '.log');

		if (!file_exists($compiledTexFile))
			die('Error: Compiled LaTeX file ' . $compiledTexFile . ' is not existing!<br />' . "<pre>$output</pre>");
		else
			die('exists!' . $compiledTexFile);



		// we have to return the content of the pdf
		$pdf = new Zend_Pdf();
		$this->_setPdf($pdf);
		$pdf = Zend_Pdf::load($compiledTexFile);

		return $pdf;		
	}
		
}



