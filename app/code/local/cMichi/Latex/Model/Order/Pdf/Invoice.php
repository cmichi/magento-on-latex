<?php
/**
 * Magento on LaTeX Extension
 *
 * This file
 *
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

class cMichi_Latex_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
{
	private $DS;
	private $mediaDir;
	private $extDir;
	private $config;


	/**
	 * Find the fitting template  for this store,
	 * return it's markup content.
	 *
	 * @return String 
	 */
	private function getFittingTemplate($order) {
		$storeId = $order->getStoreId();
		
		// is there a template specified in the config?
		if ($config['whichTeXFilesToUse'][$storeId]):
			$templateFilename = $mediaDir . $DS . $config['whichTeXFilesToUse'][$storeId] .  '.tex';
			if (file_exists($templateFilename)):
				$markup = file_get_contents($templateFilename);
			else:
				die('Error: Template ' . $templateFilename . ' could not be found! Check config.php.');
			endif;
			
		// else use media/latexinvoice/template.tex if available
		else if (file_exists($mediaDir . $DS . 'template.tex')):
			$markup = file_get_contents($mediaDir . $DS . 'template.tex');
			
		// else use the one delivered with the extension
		else:
			$markup = file_get_contents($extDir . $DS . 'template' . $DS .'template.tex');
		endif;
			
		return $markup;
	}


	public function getPdf($invoices = array())
	{
		error_reporting(E_ALL);
		die('huhu');
		$ioObject = new Varien_Io_File();			
		$DS = $ioObject->dirsep();
		
		$extDir 		 = Mage::getBaseDir('app') . $DS . 'code' . $DS . 'local' . $DS . 'Mage';
		$mediaDir 		 = Mage::getBaseDir('media') . $DS . 'latexinvoice';
		$outputDir	  	 = Mage::getBaseDir('media') . $DS . 'latexinvoice' . $DS . 'tmp';
		$filename 		 = 'invoice_'.time();
		$texFile		 = $outputDir . $DS . $filename . '.tex';
		$compiledTexFile = $outputDir . $DS . $filename . '.pdf';
		
		require($extDir.'/Sales/etc/config.php');
		$config = $latexInvoiceConfig;


		// is there a template.lco in tmp dir?
		$lco = $outputDir . $DS . 'template.lco';
		if (!file_exists($lco))
			shell_exec('cp ' . $mediaDir . $DS .'template.lco '.$lco);
	




		foreach ($invoices as $invoice):
			$order = $invoice->getOrder();
			$data = $order['_origData:protected'];
			$shipping = $order->getShippingAddress();
			
			$markup = getFittingTemplate($order);
			
			$substituteArray = getAllDataElements($markup, 'OrderItem');
			
			foreach ($substituteArray as $key => $value)
				$markup = str_replace("($key)", $value, $markup);

			

			echo '<pre>';
			
			$orders = '';
			foreach ($invoice->getAllItems() as $item){
			    if ($item->getOrderItem()->getParentItem())
			        continue;
			
				$orderItem = $item->getOrderItem();
				$orders .= $orderItem->getData('sku') . ' & '  . str_replace($orderItem->getData('name'),'-',' ') .' & '.
						   $orderItem->getQtyInvoiced() .'  & ' . $orderItem->getOriginalPrice() . ' \euro{} & ' . 
						   $orderItem->getRowTotal() . '\euro{}';
				$orders .= '\\\\';
				print_r($orderItem);
			}
			
			// alles zwischen %(Order:ItemsStart) und %(Order:ItemsEnd) ersetzen
			$pos1 = strpos($markup, '%(Order:ItemsStart)');
			$pos2 = strpos($markup, '%(Order:ItemsEnd)') + strlen('%(Order:ItemsEnd)');			
			$markup = substr($markup, 0, $pos1) . $orders . substr($markup, $pos2, strlen($markup));



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
#		shell_exec('rm ' . $tmpFolder . $filename . '.tex');
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



