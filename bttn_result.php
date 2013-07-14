<?php

	// This is the code to where the user's credentials get posted.  It:
	//   A) determines if enough time has elapsed to warrant re-contacting the TinyBttn API
	//   B) uses the results returned from TinyBttn to build discounts

	session_start();
	require('support.php');
	
	// Store user's OneID-shared info into session variable ... this enables the logic 
	//  that determines whether or not to show the OneID prompt when the user initially clicks the TinyBttn
	$_SESSION['tinybttn_email'] = $_POST['1'];
	$_SESSION['tinybttn_id']    = $_POST['2'];
	
	// Encode posted data into JSON
    $to_send = json_encode($_POST);

    // Check if TinyBttn has been contacted in the past five minutes (limits comms with the API)
    if(isset($_SESSION['tinybttn_last_post'])){
	    
	    // If it's been greater than 5 minutes
	    if($_SESSION['tinybttn_last_post']->diff(new Datetime('now'))->format("%i") > 5){
		    
		    // POST data to TinyBttn, save discount information in the session, and log the current time
		    $_SESSION['tinybttn_discounts'] = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '1', $to_send);
		    $_SESSION['tinybttn_last_post'] = new Datetime('now');
		    
	    }
    }
    
    // User hasn't yet contacted the API yet ==> POST data to TinyBttn, save discount info in the session, log current time
    else{
		// POST data to TinyBttn and save discount information in the session
		$_SESSION['tinybttn_discounts'] = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '1', $to_send);
		$_SESSION['tinybttn_last_post'] = new Datetime('now');
	}
	
	
	// ************************* Interpret results!
	
	if(!is_array($_SESSION['tinybttn_discounts']))	// Only successful calls return an array, so if not an array, echo the error
		echo $_SESSION['tinybttn_discounts'];				
	
	else{
	
		// Get the two result arrays
		$product_discounts = $_SESSION['tinybttn_discounts']['product'];
		$general_discounts = $_SESSION['tinybttn_discounts']['general'];
		

		if(!empty($product_discounts)){
			
			// Load the shopper's current cart
			$items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

			// Build an array of just the SKUs (for comparison)
			$users_skus = array();
			foreach($items as $item) {
			    $item_sku = $item->getSku();
			    array_push($users_skus, $item_sku);
			}

			// See if there's a discount that matches an item in the cart (based on SKU)
			foreach($users_skus as $sku){

				// If the item has a matching discount, build it
				if(array_key_exists($sku, $product_discounts)){	// The $product_discounts array's keys are product SKUs
				
					// Create the unique rule ame that will be sent back to TinyBttn if/when the user completes checkout
					$ps_id = 'TBPS-' . $product_discounts[$sku]['ps_id'] . '-' . $sku . '-' . rand(100000, 999999);
					
					// Call the creation function(sku, discount, qty_max, qty_step, free_ship, name)
					createProductDiscount($sku, $product_discounts[$sku]['amt'], $product_discounts[$sku]['qty_max'], $product_discounts[$sku]['qty_step'], $product_discounts[$sku]['free_ship'], $ps_id);
				}
			}
		}
		
		
		// If general (applied to the ENTIRE shopping cart) discounts array was returned, then we need to select the highest value one 
		if(!empty($general_discounts)){
		
			// Set initial values
			$general_discount = 0;
			$limit = null;
			$free_ship = 0;
	      
			foreach ($general_discounts as $gd){
	
				if($gd['discount_amt'] >= $general_discount) {  // If current discount amt is greater than previous ...
					
					$general_discount = $gd['discount_amt'];    // ... set values equal to the current discount's attributes
					
					if(isset($gd['member']))
						$title = $gd['organization'] . ' ' . $gd['member'];
					else
						$title = $gd['crm_desc'];
					
					$gen_id = 'TBME-' . $gd['me_id'] . '-';
					
					if($gd['free_ship'])                		// Check for/set free_shipping
						$free_ship = 1;
					else
						$free_ship = 0;
					
					
					// Limit explanation:  if a shopper is limited to $50 of savings and the discount_amt is 10%, then they'll only reach the limit if their subtotal is > $500.  To determine this $500 value, we simply take the dollar limit and divide by the % discount (e.g. $50/0.1 = $500 )
					if(!empty($gd['discount_limit']))
					    $limit = $gd['discount_limit'] / $general_discount;
		       }
		   }
	   }
	   
	   if($general_discount > 0)
			createGeneralDiscount($general_discount, $limit, $free_ship, $gen_id, $title);
	 }
?>