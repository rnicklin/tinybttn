<?php

	if(file_exists('tinybttn.sync')) {
		$last_sync = file_get_contents('tinybttn.sync');
		$last_sync = unserialize($last_sync);
	}
	else
		$last_sync = array();

	$mage_catalog = Mage::getModel('catalog/product')
							->getCollection()
							->addAttributeToSelect('sku')
							->addAttributeToSelect('price')
							->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
							->load();

	// Convert full catalog array into a much smaller array in the form of:  "SKU" => Pri.Ce for each product
	$current_catalog = array();
	foreach ($mage_catalog as $product) {
			$current_catalog[$product->getSku()] = number_format($product->getPrice(), 2, '.', '');
	}

	$new_or_update_items = array_diff_assoc($current_catalog, $last_sync);
	$old_items = array_diff_assoc($last_sync, $current_catalog);


	// If an item is in both the TO-ADD *and* the TO-REMOVE array, then the item is simply being UPDATED.
	//  If we put it in both add: and remove: then the API would update the item and then disable it ... that'd be
	//  dumb.  So, unset it from the remove array to prevent this from happening.
	foreach($old_items as $k => $v){
		if(!empty($new_or_update_items[$k]))
			unset($old_items[$k]);
	}


	// We only post SKUs for the to-remove items, so build a SKU-only array
	$to_remove = array();
	foreach($old_items as $sku => $price){
		array_push($to_remove, $sku);
	}


	$to_add = array();
	foreach($new_or_update_items as $sku => $price){ // could put array_diff_assoc($current, $data_from_server)

		// Load the details of any product that we need to add
		$product =  Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

		// Create an array where SKU is the key that has a sub-array of the product's details
		$item_to_add = array($sku => array($product['name'], $product['price'], $product['description'], $product['url_path'], $product['image']));

		// Push the item into the $to_add array
		array_push($to_add, $item_to_add);
	}
		

	if(!empty($to_add) && !empty($to_remove))
		$changes = json_encode(array("add" => $to_add, "remove" => $to_remove));
	elseif(!empty($to_add))
		$changes = json_encode(array("add" => $to_add));
	elseif(!empty($to_remove))
		$changes = json_encode(array("remove" => $to_remove));

	// If necessary, post data to the API and write the response to disk
	if ($changes !== null){

		$response = Mage::helper("TinyBttn")->post_to_tinybttn('catalog', null, $changes);

		// Error handling.  This prevents an error message from being written into rallyribbon.sync; this would
		//  disrupt the next run of the code (since the error message would be compared against the product catalog).
		if(!empty($response['error']))
			Mage::getSingleton('adminhtml/session')->addError($response['error']);
		else {
			// Serialze the array (so we can reconstitute it later) and write it to disk
			$to_write = serialize($response);
			file_put_contents('tinybttn.sync', $to_write, 0);
			Mage::getSingleton('adminhtml/session')->addSuccess('All enabled products successfully synced with TinyBttn.');
			$date_time = date_create();
			$date_time = date_format($date_time, 'Y-m-d H:i:s');
		}
	
	}

?>