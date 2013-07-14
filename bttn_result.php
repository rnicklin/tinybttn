<?php

	session_start();
	require('support.php');
	
	// Store user's OneID-shared info into session variable ... this enables the logic that determines whether or not to show the OneID prompt
	$_SESSION['tinybttn_email'] = $_POST['1'];
	$_SESSION['tinybttn_id']    = $_POST['2'];
	
	// Encode posted data into JSON
    $to_send = json_encode($_POST);

    // Check if TinyBttn has been contacted in the past five minutes (limit how much someone can hammer my API)
    if(isset($_SESSION['tinybttn_last_post'])){
	    
	    // If it's been greater than 5 minutes
	    if($_SESSION['tinybttn_last_post']->diff(new Datetime('now'))->format("%i") > 5){
		    
		    // POST data to TinyBttn and save discount information in the session
		    $_SESSION['tinybttn_discounts'] = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '1', $to_send);
		    $_SESSION['tinybttn_last_post'] = new Datetime('now');
		    
	    }
    }
    
    // last_post isn't set, so they haven't contacted in this session 
    else{
		// POST data to TinyBttn and save discount information in the session
		$_SESSION['tinybttn_discounts'] = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '1', $to_send);
		$_SESSION['tinybttn_last_post'] = new Datetime('now');
	}
	
	
	
	
	
	
	
	
	
	
	
	echo '<div style="padding-left: 5%; padding-top: 5px;"><h2>TinyBttn</h2>';
	
	if(!is_array($_SESSION['tinybttn_discounts']))	// Only successful calls return an array, so if not an array, echo the error
		echo $_SESSION['tinybttn_discounts'];				
	
	else{
	
		// Get the two result arrays
		$product_discounts = $_SESSION['tinybttn_discounts']['product'];
		$general_discounts = $_SESSION['tinybttn_discounts']['general'];
		
		
		
		if(!empty($product_discounts)){
			
//			// Load the shopper's cart
//			$items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
//
//			// Build an array of just the SKUs (for comparison)
//			$users_skus = array();
//			foreach($items as $item) {
//			    $item_sku = $item->getSku();
//			    array_push($users_skus, $item_sku);
//			}
//
//				// See if there's a discount that matches an item in the cart (based on SKU)
//				foreach($users_skus as $sku){
//
//					// If the item has a matching discount, build it
//					if(array_key_exists($sku, $product_discounts)){	// The $product_discounts array's keys are product SKUs
//					
//						// Create the unique name that will be passed back to TinyBttn
//						$name = 'TBPS-' . $product_discounts[$sku]['ps_id'] . '-' . $sku . '-' . rand(100000, 999999); // Rule's unique identifer?

//						// Call the creation function(sku, discount, qty_max, qty_step, free_ship, name)
//						createProductDiscount($sku, $product_discounts[$sku]['amt'], $product_discounts[$sku]['qty_max'], $product_discounts[$sku]['qty_step'], $product_discounts[$sku]['free_ship'], $name);
//					}
//				}


			echo '<h4>Product Discounts</h4>';
			foreach ($product_discounts as $sku => $details){


				echo '<p><span style="font-family: arial; color: #2233ff; font-size: small;">SKU:' . $sku . '</span> <span style="font-family: arial; color: #ff00ff; font-size: small;">';
				print_r($details);
				echo '</span></p>';
				echo '<p>';
			}

			

		}
		
		
		// If general (applied to the ENTIRE shopping cart) discounts array was returned, then we need to select the highest value one 
		if(!empty($general_discounts)){
		
			// Set initial values
			$general_discount = 0;
			$limit = null;
			$free_ship = FALSE;
	      
			foreach ($general_discounts as $gd){
	
				if($gd['discount_amt'] >= $general_discount) {  // If current discount amt is greater than previous ...
					
					$general_discount = $gd['discount_amt'];    // ... set values equal to the current discount's attributes
					
					if(isset($gd['member']))
						$title = $gd['organization'] . ' ' . $gd['member'];
					else
						$title = $gd['crm_desc'];
					
					$id = 'TBME-' . $gd['me_id'] . '-'; 		// Base of the rule's unique identifer ... we'll add random digits in function
					
					if($gd['free_ship'])                		// Check for/set free_shipping
						$free_ship = 1;
					else
						$free_ship = 0;
					
					if(!empty($gd['discount_limit']))
					    $limit = $gd['discount_limit'] / $general_discount;		// If, for example, a shopper is limited to $50 of savings and 					    															//  the discount_amt is 10%, then they'll only reach
					    														//  the limit when purchasing > $500 worth of stuff
		       }
		   }
	   }
	   
	   if($general_discount > 0){
//			createGeneralDiscount($general_discount, $limit, $free_ship, $id, $title);


		   echo '<h4>General Discounts</h4><p>';
		   echo 'You qualify for a '. $title . ' discount of ' . $general_discount*100 .'%';
		   
	   }
	 }
	 
	 echo '</div>';
?>