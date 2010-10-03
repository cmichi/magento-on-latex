# Magento on LaTeX

This is an extension for Magento which serves pdf invoices generated from a LaTeX template. 

This means that it is very easy to modify your invoice template, so that it uses your corporate design etc..

This also means that your invoices will stop looking poor and start looking tight :)

  

## Motivation

I was working a lot with LaTeX in the past months and since I had to create invoices  
for the Magento Shopsystem which fit to the german law,  
I decided to take the opportunity and write an extension for this.

No normal web server runs TeX out of the box,  
but since it is really easy to install a TeX distribution like TexLive,  
this shouldn't be a problem if you own a root server.



## Installation

I am working on an extension package for the Mage installer, but until this is done  
you have to download the package and place the directories in you magento folder.

For installation problems see the wiki: [http://github.com/cMichi/magento-on-latex/wiki/Installation](http://github.com/cMichi/magento-on-latex/wiki/Installation).


## Templates

The extension come with three LaTeX templates: 

* German invoice for consumers (german-private-customer.tex)
* German invoice for retailers (german-retailer.tex)
* Very basic english invoice  (template.tex)

You can modify the templates by copying them into /media/latex/,  
then you have to change the /app/code/local/cMichi/Latex/etc/config.php to use your template.

For example add this code to the config.php to use 
the file /medie/latex/retailer-template.tex as a template for the store-id 1.

	$config[1] = array(
		'filename' => 'retailer-template',
		'currency' => '\euro{}',
		'date' => 'd.m.Y',
		'dateFields' => $dateFields,
		'priceFields' => $priceFields			
	);




