<?php
	
	// Get TinyBttnID out of session
	$tinybttn_id = $_SESSION['tinybttn_id']

	if($tinybttn_id !== null){
	
		// Get the just-completed OrderID
		$order_id = Mage::getSingleton('checkout/session')->getLastOrderId();

		// Load the order using its id
		$order = Mage::getModel('sales/order')
	    	->load($order_id);

		// Get the details
		$order_items = $order->getAllItems();
	
		// Put details into correct format for API
		$trnsx_items = array();
		foreach ($order_items as $itemId => $item) {
	    
			$purch_item->sku = $item->getSku();
		    $purch_item->qty = $item->getQtyToInvoice();
			$purch_item->price = $item->getPrice();
		
			array_push($trnsx_items, $purch_item);
		}
	
		// Build the appropriate JSON object
		$payload = json_encode(array("order_id" => $order_id, "tinybttn_id" => $tinybttn_id, "items" => $trnsx_items));
	
		// Encode into JWT using the API_SECRET
		$jwt = JWT::encode($pay, $api_secret);
	
		post_to_tinybttn(TRANSACTION_API_ENDPOINT, null, $jwt);
	}
	
?>