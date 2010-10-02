<?php
/**
 * Magento on LaTeX Extension
 *
 * This is the config file.
 *
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

//////// ******************** DONT TOUCH!!! ************************ ////////

$dateFields = array('created_at');
$priceFields = array('base_total', 'subtotal', 'grand_total', 'original_price', 'row_total', 'tax_amount');

$config = array();
$config['standard'] = array(
	'currency' => ' \euro{}',
	'date' => 'd.m.Y',
	'dateFields' => $dateFields,
	'priceFields' => $priceFields	
);
//////// ******************** DONT TOUCH!!! ************************ ////////




/*	****** EXAMPLE CONFIG  ******** 
	The files you specifiy here have to be located at
		media/latex/
*/


$config[1] = array(
	'filename' => 'privatkunde',
	'currency' => '\euro{}',
	'date' => 'd.m.Y',
	'dateFields' => $dateFields,
	'priceFields' => $priceFields			
);

$config[1] = array(
	'filename' => 'gewerblich',
	'currency' => '\euro{}',
	'date' => 'd.m.Y',
	'dateFields' => $dateFields,
	'priceFields' => $priceFields			
);
