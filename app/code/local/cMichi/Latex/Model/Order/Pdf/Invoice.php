<?php
/**
 * Magento on LaTeX Extension
 *
 * This is the main file which renders the markup.
 *
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

class cMichi_Latex_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
{
	private $DS, $mediaDir, $extDir, $config, $outputDir, 
			$filename, $texFile, $compiledTexFile, $tmpFolder;



	/**
	 * Main function! Renders the pdf!
	 *
	 * @param $invoices Passed from the caller
	 * @return String
	 */	
	public function getPdf($invoices = array())
	{
		error_reporting(E_ALL);
		$this->init();

		echo '<pre>';
		foreach ($invoices as $invoice):
			$order = $invoice->getOrder();
			$data = $order['_origData:protected'];
			$shipping = $order->getShippingAddress();

			$markup = $this->getFittingTemplate($order);


			$markup = $this->substitute($markup, $shipping, 'Shipping');			

			//print_r($shipping);

			$substituteArray = $this->getAllKeyElements($markup, 'OrderItem');

			$markup = $this->substitute($markup, $order, 'Order');

			// get everything between %(Order:ItemsStart) and %(Order:ItemsEnd) 
			$pos1 = strpos($markup, '%(OrderItems:Start)');
			$orderItemLine = substr($markup, $pos1, strlen($markup));

			$pos2 = strpos($orderItemLine, '%(OrderItems:End)'); 			
			$orderItemLine = substr($orderItemLine, 0, $pos2);


			$orders = '';
			foreach ($invoice->getAllItems() as $item){
			    if ($item->getOrderItem()->getParentItem())
			        continue;

				$orderItem = $item->getOrderItem();
				$orders .= $this->substitute($orderItemLine, $orderItem, 'OrderItem');

				print_r($orderItem);
			}


			// replace everything between %(Order:ItemsStart) and %(Order:ItemsEnd)
			$pos1 = strpos($markup, '%(OrderItems:Start)');
			$pos3 = strpos($markup, '%(OrderItems:End)') + strlen('%(OrderItems:End)');						
			$markup = substr($markup, 0, $pos1) . $orders . substr($markup, $pos3, strlen($markup));			

			echo '</pre>';
		endforeach;

		
		$this->compileMarkup($markup);

		// we have to return the content of the pdf
		$pdf = new Zend_Pdf();
		$this->_setPdf($pdf);
		$pdf = Zend_Pdf::load($this->compiledTexFile);

		return $pdf;		
	}


	/**
	 * Write markup to TeX file and compile it.
	 *
	 * @return void
	 */
	private function compileMarkup($markup) {
	    if (!$handle = fopen($this->texFile, "w+"))
	         die('Unable to OPEN '.$this->texFile.'! Check rights.');

	    if (!fwrite($handle, $markup))
	         die('Unable to WRITE '.$this->texFile.'! Check rights.');

		if (!file_exists($this->texFile))
			die('not existing: ' . $this->texFile);

		$cmd = '/usr/texbin/pdflatex -output-directory ' . $this->tmpFolder . ' ' . 
				$this->tmpFolder . $this->filename . '.tex';
		$output = shell_exec($cmd);								
		
		// remove all tmp files
		#shell_exec('rm ' . $tmpFolder . $filename . '.tex');
		shell_exec('rm ' . $this->tmpFolder . $this->filename . '.aux');
		shell_exec('rm ' . $this->tmpFolder . $this->filename . '.log');

		if (!file_exists($this->compiledTexFile))
			die('Error: Compiled LaTeX file ' . $this->compiledTexFile . ' is not existing!<br />'
			 	. "<br /><br /><hr /><br /><br /><pre>$output</pre>" 
				. "<br /><br /><hr /><br /><br /><pre>$markup</pre>");
		else
			die('exists!' . $this->compiledTexFile);
		
	}



	/**
	 * Called to initialise all vars & load config.
	 *
	 * @return void
	 */
	private function init() {
		$ioObject = new Varien_Io_File();			
		$this->DS = $ioObject->dirsep();
		$DS = $this->DS;

		$this->extDir 			 = Mage::getBaseDir('app') . $DS . 'code' . $DS . 'local' . $DS . 'cMichi';
		$this->mediaDir 		 = Mage::getBaseDir('media') . $DS . 'latexinvoice';
		$this->outputDir	  	 = Mage::getBaseDir('media') . $DS . 'latexinvoice' . $DS . 'tmp';
		$this->filename 		 = 'invoice_'.time();
		$this->texFile			 = $this->outputDir . $DS . $this->filename . '.tex';
		$this->compiledTexFile 	 = $this->outputDir . $DS . $this->filename . '.pdf';
		$this->tmpFolder 		 = 'media' . $DS . 'latexinvoice' . $DS . 'tmp' . $DS;

		//load config
		require($this->extDir . '/Latex/etc/config.php');
		if (isset($config)) 
			$this->config = $config;
		else
			$this->config = $standardConfig;					
			
		// is there a template.lco in tmp dir?
		$lco = $this->outputDir . $DS . 'template.lco';
		if (!file_exists($lco))
			shell_exec('cp ' . $this->mediaDir . $DS .'template.lco '.$lco);
			
		return;
	}
	


	/**
	 * Find the fitting template  for this store,
	 * return it's markup content.
	 *
	 * @param order
	 * @return String 
	 */
	private function getFittingTemplate($order) {		
		$DS = $this->DS;
		$storeId = $order->getStoreId();
		
		// is there a template specified in the config?
		if (isset($this->config[$storeId])):
			$templateFilename = $this->mediaDir . $DS . $this->config[$storeId] .  '.tex';
			if (file_exists($templateFilename)):
				$markup = file_get_contents($templateFilename);
			else:
				die('Error: Template ' . $templateFilename . ' could not be found! Check config.php.');
			endif;
			
		// else use media/latexinvoice/template.tex if available
		elseif (file_exists($this->mediaDir . $DS . 'template.tex')):
			$markup = file_get_contents($this->mediaDir . $DS . 'template.tex');
			
		// else use the one delivered with the extension
		else:
			$markup = file_get_contents($this->extDir . $DS . 'templates' . $DS .'template.tex');
		endif;
			
		return $markup;
	}



	/**
	 * Finds all elements (with the (prefix:...)
	 *
	 * @return Array
	 */
	private function getAllKeyElements($markup, $prefix) {
		$keys = array();
		do {
			$pos1 = strpos($markup, "($prefix:");
			if ($pos1 != false):
				$markup = substr($markup, $pos1, strlen($markup));

				$pos2 = strpos($markup, ')');
				$keys[] = str_replace($prefix.':', '', substr($markup, 1, $pos2 - 1));
				$markup =  substr($markup, $pos2, strlen($markup));
			endif;
		} while ($pos1 != false);		
		
		return $keys;
	}
	
	
	
	/**
	 * Make a typographic high quality LaTeX string from this markup.
	 *
	 * @param $string 
	 * @return String
	 */
	private function replaceForTeX($string) {
		$replace = array(
			'-' => ' -- '
		);
		
		foreach ($replace as $from => $to)
			$string = str_replace($from, $to, $string);
		
		return $string;
	}



	/**
	 * Make a typographic high quality LaTeX string from this markup.
	 *
	 * @param $markup The LaTeX template
	 * @param $dataObj The object where getData is called on (shippingAddreess, Order, OrderItem, ...)
	 * @param $prefix The prefix used in the markup (Order, Shipping, OrderItem, ...)
	 * @return String
	 */
	private function substitute($markup, $dataObj, $prefix) {
		$substituteArray = $this->getAllKeyElements($markup, $prefix);

		foreach ($substituteArray as $key):
			$data = $dataObj->getData($key);

			if (in_array($key, $this->config['dateFields'])):
				$date = $dataObj->getData($key);
				$date = date($this->config['date'], strtotime($date)); 
				$markup = str_replace("(Order:$key)", $date, $markup);
			elseif (in_array($key, $this->config['priceFields'])):
				$data = $data . $this->config['currency'];
			endif;
			
			$data = $this->replaceForTeX($data);
			$markup = str_replace("($prefix:$key)", $data, $markup);
		endforeach;

		return $markup;
	}


		
}



