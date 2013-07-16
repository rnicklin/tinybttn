<?php

	// This is the code to where the user's credentials get posted.  It:
	//   A) determines if enough time has elapsed to warrant re-contacting the TinyBttn API
	//   B) uses the results returned from TinyBttn to build discounts

	$session = Mage::getSingleton('customer/session');


	// Store user's OneID-shared info into session variable ... this enables the logic 
	//  that determines whether or not to show the OneID prompt when the user initially clicks the TinyBttn
	$session['tinybttn_email'] = $_POST['1'];
	$session['tinybttn_id']    = $_POST['2'];
	
	// Encode posted data into JSON
    $to_send = json_encode($_POST);

    // Check if TinyBttn has been contacted in the past five minutes (limits comms with the API)
    if(isset($session['tinybttn_last_post'])){
	    
	    // If it's been greater than 5 minutes
	    if($session['tinybttn_last_post']->diff(new Datetime('now'))->format("%i") > 5){
		    
		    // POST data to TinyBttn, save discount information in the session, and log the current time
		    $session['tinybttn_discounts'] = Mage::helper("TinyBttn")->post_to_tinybttn('discount', '1', $to_send);
		    $session['tinybttn_last_post'] = new Datetime('now');
		    
	    }
    }
    
    // User hasn't yet contacted the API yet ==> POST data to TinyBttn, save discount info in the session, log current time
    else{
		// POST data to TinyBttn and save discount information in the session
		$session['tinybttn_discounts'] = Mage::helper("TinyBttn")->post_to_tinybttn('discount', '1', $to_send);
		$session['tinybttn_last_post'] = new Datetime('now');
	}
	
	
	// ************************* Interpret results!
	
	if(!is_array($session['tinybttn_discounts']))	// Only successful calls return an array, so if not an array, echo the error
		echo $session['tinybttn_discounts'];				
	
	else{
	
		// Get the two result arrays
		$product_discounts = $session['tinybttn_discounts']['product'];
		$general_discounts = $session['tinybttn_discounts']['general'];
		

		if(!empty($product_discounts)){
			
			// Load the shopper's current cart
			$items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

			// Build an array of just the SKUs (for comparison)
			$users_skus = array();
			foreach($items as $item) {
			    $item_sku = $item->getSku();
			    array_push($users_skus, $item_sku);
			}
			
			// We need to allow the user to click the button multiple times (in case they add a new product that qualifies for
			//  a discount after the initial click), *BUT* if we don't keep track of the rules we've already built, then
			//  we'll start creating duplicates!
			if(!isset($session['existing_ps_ids'])
				 $session['existing_ps_ids'] = array();	// Instantiate as an array if not previously set
			
			// See if there's a discount that matches an item in the cart (based on SKU)
			foreach($users_skus as $sku){

				// If the item has a matching discount, build it
				if(array_key_exists($sku, $product_discounts)){	// The $product_discounts array's keys are product SKUs
				
					// Create the unique rule ame that will be sent back to TinyBttn if/when the user completes checkout
					$ps_id = 'TBPS-' . $product_discounts[$sku]['ps_id'] . '-' . $sku . '-';
					
					
					// If we haven't already built this rule ...
					if(!in_array($ps_id, $session['existing_ps_ids'])){
						// ... call the creation function
						Mage::helper("TinyBttn")->createProductDiscount($sku, $product_discounts[$sku]['amt'], $product_discounts[$sku]['qty_max'], $product_discounts[$sku]['qty_step'], $product_discounts[$sku]['free_ship'], $ps_id);
					
						// Add the rule we just built into our array of existing rules in order to close the logic loop
						array_push($session['existing_ps_ids'], $ps_id);
					}
				}
			}
		}
		
		
		// If general (applied to the ENTIRE shopping cart) discounts array was returned, then we need to select the highest value one 
		if(!empty($general_discounts)){
		
			// Set initial values
			$general_discount = 0;
			$limit = 0;
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
					if(!empty($gd['discount_limit']) && $general_discount != 0)
					    $limit = $gd['discount_limit'] / $general_discount;
		       }
		   }
	   }
	   
		// For products, we've addressed the concern about duplicating discount rules by maintaining an array of previously-built rules
		//  For the general discount, since there's only one, we just need a boolean "Has one been applied?"
		$session['general_applied'] = 0;
	   
		if($general_discount > 0){
			Mage::helper("TinyBttn")->createGeneralDiscount($general_discount, $limit, $free_ship, $gen_id, $title);
			$session['general_applied'] = 1;
		}
	 }
?>