<?php
/**
 * Magento on LaTeX Extension
 *
 * This is the config file.
 *
 * @copyright  Copyright (c) 2010 Michael Mueller (http://micha.elmueller.net)
 * @license    LGPL
 */

/*	****** EXAMPLE CONFIG  ******** 
	The files you specifiy here have to be located at
		media/latex/
*/

/*
$config = array(
	1 => array(
		'filename' => 'privat',
		'currency' => '\euro{}',
		'date' => 'd.m.Y'
	),
	2 => 'gewerblich'
);
*/


$dateFields = array('created_at');
$priceFields = array('base_total', 'subtotal', 'grand_total', 'original_price', 'row_total');
$standardConfig = array(
	'currency' => ' \euro{}',
	'date' => 'd.m.Y',
	'dateFields' => $dateFields,
	'priceFields' => $priceFields	
);