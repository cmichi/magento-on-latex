<?php
/**
 * Magento on LaTeX Extension
 *
 * This is the main file which renders the markup.
 *
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

class Cmichi_Latex_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Abstract
{
	private $mediaDir, $extDir, $config, $outputDir, 
		$filename, $texFile, $compiledTexFile, $tmpFolder;

	// either pdflatex has to be in your environment variable
	// or you have to set the path here!
	// Remember to use / (UNIX) or \ (Win)
	#private $pdflatexPath = '/usr/texbin/pdflatex';
	private $pdflatexPath = '/usr/bin/pdflatex';
	#private $pdflatexPath = 'pdflatex';


	// if set to true the output is shown and no pdf is sent,
	// see function getPdf() for details
	private $debug = false;


	/**
	 * Main function! Renders the pdf!
	 *
	 * @param $invoices Passed from the caller
	 * @return String
	 */	
	public function getPdf($invoices = array())
	{
		$this->init();

		foreach ($invoices as $invoice):
			$order = $invoice->getOrder();
			$data = $order['_origData:protected'];
			$shipping = $order->getShippingAddress();
			$storeId = $order->getStoreId();

			$markup = $this->getFittingTemplate($order);

			$markup = $this->substitute($markup, $shipping, 'Shipping', $storeId);			

			$substituteArray = $this->getAllKeyElements($markup, 'OrderItem');

			$markup = $this->substitute($markup, $order, 'Order', $storeId);

			// get everything between %(Order:ItemsStart) and %(Order:ItemsEnd) 
			$pos1 = strpos($markup, '%(OrderItems:Start)');
			$orderItemLine = substr($markup, $pos1, strlen($markup));

			$pos2 = strpos($orderItemLine, '%(OrderItems:End)'); 			
			$orderItemLine = substr($orderItemLine, 0, $pos2);


			$orders = '';
			foreach ($invoice->getAllItems() as $item) {
				$orderItem = $item->getOrderItem();
				//$this->log($orderItem);

				if ($orderItem->getParentItem())
					continue;

				$orders .= $this->substitute($orderItemLine, $orderItem, 'OrderItem', $storeId);
			}


			// replace everything between %(Order:ItemsStart) and %(Order:ItemsEnd)
			$pos1 = strpos($markup, '%(OrderItems:Start)');
			$pos3 = strpos($markup, '%(OrderItems:End)') + strlen('%(OrderItems:End)');						
			$markup = substr($markup, 0, $pos1) . $orders . substr($markup, $pos3, strlen($markup));			
		endforeach;


		$this->compileMarkup($markup);
		$this->log($markup);

		// we have to return the content of the pdf
		$pdf = file_get_contents($this->compiledTexFile);
		$pdf = Zend_Pdf::parse($pdf);
		#$pdf = Zend_Pdf::load($this->compiledTexFile);

		// remove the generated pdf
		if ($this->debug == false)		
			shell_exec('rm ' . $this->compiledTexFile);
		
		if ($this->debug == true)		
			die('end reached');
		else
			return $pdf;		

	}



	/**
	 * Write markup to TeX file and compile it.
	 *
	 * @param $markup The TeX code
	 * @return 
	 */
	private function compileMarkup($markup)
	{
		if (!$handle = @fopen($this->texFile, "w"))
			die('Unable to OPEN '.$this->texFile.'! Check rights.');


		if (fwrite($handle, $markup) == false)
			die('Unable to WRITE '.$this->texFile.'! Check rights.');

		if (!fclose($handle))
			die('cannot close file');

		if (!file_exists($this->texFile))
			die('not existing: ' . $this->texFile);

		// example: $cmd = '/usr/texbin/pdflatex -output-directory $tmpFolder $tmpFodler $filename.tex
		$cmd = 	$this->pdflatexPath . ' -output-directory ' . $this->tmpFolder . ' ' . 
			$this->tmpFolder . $this->filename . '.tex';
		$this->log('executing: ' . $cmd);
		$output = shell_exec($cmd);								
		$this->log($output);


		// remove all tmp files
		if ($this->debug == false)
			shell_exec('rm ' . $this->tmpFolder . $this->filename . '.tex');

		shell_exec('rm ' . $this->tmpFolder . $this->filename . '.aux');
		shell_exec('rm ' . $this->tmpFolder . $this->filename . '.log');

		if (!file_exists($this->compiledTexFile))
			die('Error: Compiled LaTeX file ' . $this->compiledTexFile . ' is not existing!<br />'
			. "<br /><br /><hr /><br /><br /><pre>$output</pre>" 
			. "<br /><br /><hr /><br /><br /><pre>$markup</pre>");
	}



	/**
	 * Called to initialise all vars & load config.
	 *
	 * @return 
	 */
	private function init()
	{
		$ioObject = new Varien_Io_File();			
		
		$this->extDir 			 = Mage::getBaseDir('app') . DS . 'code' . DS . 'local' . DS . 'Cmichi';
		$this->mediaDir 		 = Mage::getBaseDir('media') . DS . 'latex';
		$this->outputDir	  	 = Mage::getBaseDir('media') . DS . 'latex' . DS . 'tmp';
		$this->filename 		 = 'invoice_'.time();
		$this->texFile			 = $this->outputDir . DS . $this->filename . '.tex';
		$this->compiledTexFile	 = $this->outputDir . DS . $this->filename . '.pdf';
		$this->tmpFolder 		 = 'media' . DS . 'latex' . DS . 'tmp' . DS;

		//load config
		require($this->extDir . '/Latex/etc/config.php');
		$this->config = $config;

		// is there a template.lco in tmp dir?
		$lco = $this->outputDir . DS . 'template.lco';
		if (!file_exists($lco) && file_exists($this->mediaDir . DS . 'template.lco')):
			copy($this->mediaDir . DS .'template.lco', $lco);
		elseif (!file_exists($lco) && file_exists($this->extDir . DS . 'Latex' . DS . 'templates' . DS . 'template.lco')):
			copy($this->extDir . DS . 'Latex' . DS . 'templates' . DS .'template.lco', $lco);
		elseif (!file_exists($lco)):
			die('There is no template.lco available! Copy it to /media/latex/');
		endif;
		
		return;
	}



	/**
	 * Prints log message if debug mode is on.
	 *
	 * @param $msg
	 * @return 
	 */
	private function log($msg)
	{
		if ($this->debug == true): 
			echo '<pre>';
			print_r($msg);
			echo '</pre><br /><hr /><br />';
		endif;
	}



	/**
	 * Find the fitting template  for this store,
	 * return it's markup content.
	 *
	 * @param $order
	 * @return String 
	 */
	private function getFittingTemplate($order)
	{
		$storeId = $order->getStoreId();

		// is there a template specified in the config?
		if (isset($this->config[$storeId])):
			$templateFilename = $this->mediaDir . DS . $this->config[$storeId]['filename'] .  '.tex';
		if (file_exists($templateFilename)):
			$markup = file_get_contents($templateFilename);
		else:
			die('Error: Template ' . $templateFilename . ' could not be found! Check config.php.');
		endif;

		// else use media/latex/template.tex if available
		elseif (file_exists($this->mediaDir . DS . 'template.tex')):
			$markup = file_get_contents($this->mediaDir . DS . 'template.tex');

		// else use the one delivered with the extension
		else:
			$path = $this->extDir .DS .'Latex' . DS . 'templates' . DS .'template.tex';
			$markup = file_get_contents($path);
		endif;


		return $markup;
	}



	/**
	 * Finds all elements (with the (prefix:...)
	 *
	 * @return Array
	 */
	private function getAllKeyElements($markup, $prefix)
	{
		$keys = array();
		do {
			$pos1 = strpos($markup, "($prefix:");
			if ($pos1 != false):
				$markup = substr($markup, $pos1, strlen($markup));

				$pos2 = strpos($markup, ')');
				$key = str_replace($prefix.':', '', substr($markup, 1, $pos2 - 1));
				// LaTeX has its problems with underscore ;)
				$key = str_replace('-', '_', $key); 
				$keys[] = $key;
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
	private function replaceForTeX($string)
	{
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
	private function substitute($markup, $dataObj, $prefix, $storeId)
	{
		$substituteArray = $this->getAllKeyElements($markup, $prefix);

		foreach ($substituteArray as $key):
			$data = $dataObj->getData($key);
			//echo $key.'!<br />';

			if (in_array($key, $this->config['standard']['dateFields'])):
				$date = $dataObj->getData($key);
				$date = date($this->config['standard']['date'], strtotime($date)); 
				$data = $date;
			elseif ($key == 'qty_invoiced'):
				$data = round($data, 0);
			elseif ($key == 'price_incl_tax'):
				// currently Magento doesn't support this natively
				$data = ($dataObj->getTaxAmount() + $dataObj->getRowTotal()) / $dataObj->getQtyInvoiced();
				$data = $this->roundPrice($data);				
			elseif ($key == 'row_price_incl_tax'):
				// currently Magento doesn't support this natively
				$data = $dataObj->getTaxAmount() + $dataObj->getRowTotal();
				$data = $this->roundPrice($data);
			endif;

			// this has to be after the checks! 
			if (isset($this->config['standard']) && in_array($key, $this->config['standard']['priceFields']))
				$data = $this->roundPrice($data) . $this->config['standard']['currency'];

			$data = $this->replaceForTeX($data);
			$markup = str_replace("($prefix:$key)", $data, $markup);
			// to make sure that all keys are replaced, LaTeX has its problems with _
			$markup = str_replace("($prefix:".str_replace('_', '-', $key).")", $data, $markup);
		endforeach;

		return $markup;
	}



	/**
	 * Rounds the price.
	 *
	 * @return Price, 2 decimal digits
	 */
	private function roundPrice($p) {
		return number_format($p, 2, null, '');		
	}

}



