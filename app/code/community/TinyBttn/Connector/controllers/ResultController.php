<?php

class TinyBttn_Connector_ResultController extends Mage_Core_Controller_Front_Action
{
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function postAction()
    {

        // This is the code to where the user's credentials get posted.  It:
        //   A) determines if enough time has elapsed to warrant re-contacting the TinyBttn API
        //   B) uses the results returned from TinyBttn to build discounts

        // We need to allow the user to click the button multiple times (in case they add a new product that qualifies for
        //  a discount after the initial click), *BUT* if we don't keep track of the rules we've already built, then
        //  we'll start creating duplicates.
        // SO => instantiate an array to track ... this will also be used to later remove them (upon checkout)
        if(!$this->_getSession()->getTinybttnCreated()) $this->_getSession()->setTinybttnCreated(array());
        // Instantiate as an array if not previously set ... have to check isset so that we don't overwrite existing

        // Store user's OneID-shared info into session variable ... this enables the logic
        //  that determines whether or not to show the OneID prompt when the user initially clicks the TinyBttn
        $this->_getSession()->setTinybttnEmail($this->getRequest()->getParam('1'));
        $this->_getSession()->setTinybttnId($this->getRequest()->getParam('2'));

        // Encode posted data into JSON
        $to_send = Mage::helper('core')->jsonEncode($this->getRequest()->getParams());

        // Check if TinyBttn has been contacted in the past five minutes (limits comms with the API)
        if($this->_getSession()->getTinybttnLastPost()){

            // If it's been greater than 5 minutes
            //if($this->_getSession()->getTinybttnLastPost()->diff(new Datetime('now'))->format("%i") > 5){
                // POST data to TinyBttn, save discount information in the session, and log the current time
                $this->_getSession()->setTinybttnDiscounts(Mage::helper("TinyBttn")->post_to_tinybttn('discount', '1', $to_send));
                $this->_getSession()->setTinybttnLastPost(new Datetime('now'));
            //}
        }

        // User hasn't yet contacted the API yet ==> POST data to TinyBttn, save discount info in the session, log current time
        else{
            // POST data to TinyBttn and save discount information in the session
            $this->_getSession()->setTinybttnDiscounts(Mage::helper("TinyBttn")->post_to_tinybttn('discount', '1', $to_send));
            $this->_getSession()->setTinybttnLastPost(new Datetime('now'));
        }


        // ************************* Interpret results!

        if(is_array($this->_getSession()->getTinybttnDiscounts())) {

            // Get the two result arrays
            $tinybttn_discounts = $this->_getSession()->getTinybttnDiscounts();
            $product_discounts = $tinybttn_discounts['product'];
            $general_discounts = $tinybttn_discounts['general'];

            if(!empty($product_discounts)){

                // Unfortunately, each Shopping Cart Rule has to have a unique ID, so we can't use that as a means to track in here
                if(!$this->_getSession()->getTinybttnUsedSkus()) $this->_getSession()->setTinybttnUsedSkus(array());
                // Instantiate as an array if not previously set

                // Load the shopper's current cart
                $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

                // Build an array of just the SKUs (for comparison)
                $users_skus = array();
                foreach($items as $item) {
                    $user_skus[] = $item->getSku();
                }

                // See if there's a discount that matches an item in the cart (based on SKU)
                foreach($users_skus as $sku){

                    // If the item has a matching discount, build it
                    if(array_key_exists($sku, $product_discounts)){	// The $product_discounts array's keys are product SKUs

                        // Create the unique ID that will be sent back to TinyBttn if/when the user completes checkout
                        $ps_id = 'TBPS-' . $product_discounts[$sku]['ps_id'] . '-' . $sku . '-';

                        // If we haven't already built a rule using this SKU ...
                        if(!in_array($sku, $this->_getSession()->getTinybttnUsedSkus())){
                            // ... call the creation function
                            Mage::helper("TinyBttn")->createProductDiscount($sku, $product_discounts[$sku]['amt'], $product_discounts[$sku]['qty_max'], $product_discounts[$sku]['qty_step'], $product_discounts[$sku]['free_ship'], $ps_id);

                            // Add the SKU into our array of used SKUs in order to close the logic loop
                            $stack = $this->_getSession()->getTinybttnUsedSkus();
                            $stack[] = $sku;
                            $this->_getSession()->setTinybttnUsedSkus($stack);
                        }
                    }
                }
            }


            // If general (applied to the ENTIRE shopping cart) discounts array was returned, then we need to select the highest value one
            if(!empty($general_discounts)){

                // For products, we addressed the concern about duplicating rules using an array of previously-built rules
                //  For the general discount, since we basically do the same thing
                if(!$this->_getSession()->getTinybttnUsedGen()) $this->_getSession()->setTinybttnUsedGen(array());
                // Instantiate as an array if not previously set

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

                        if($gd['free_ship'] = 't')        // Check for/set free_shipping
                        $free_ship = 1;
                        else
                            $free_ship = 0;


                        // Limit explanation:  if a shopper is limited to $50 of savings and the discount_amt is 10%,
                        // then they'll only reach the limit if their subtotal is > $500.
                        // To determine this $500 value, we simply take the dollar limit and divide by the % discount (e.g. $50/0.1 = $500 )
                        if(!empty($gd['discount_limit']) && $general_discount != 0)
                            $limit = $gd['discount_limit'] / $general_discount;
                    }
                }
            }

            if($general_discount > 0){
                if($this->_getSession()->getTinybttnGeneralSet() != $gen_id){

                    if(($key = array_search("great", $james)) !== false)
                        unset($james[$key]);

                    Mage::helper("TinyBttn")->createGeneralDiscount($general_discount, $limit, $free_ship, $gen_id, $title);
                    $this->_getSession()->setTinybttnGeneralSet($gen_id);
                }
            }
        }
    }
}