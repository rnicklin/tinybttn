<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * One page checkout success page
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class TinyBttn_Connector_Block_Checkout_Onepage_Success extends Mage_Checkout_Block_Onepage_Success
{

	// Get the details of the just-completed order, format into an array, and post to TinyBttn
	public function sendOrder(){

		// Get TinyBttnID out of session
		$session = Mage::getSingleton('customer/session');
		$tinybttn_id = $session['tinybttn_id']
			
		// Get the just-completed OrderID
		$order_id = $this->getOrderId();

		if(isset($tinybttn_id)){
			
			// Require the library to encode the JSON Web Token (JWT)
			require("jwt.php");

			// Load the order using its id
			$order = Mage::getModel('sales/order')
		    	->load($order_id);

			// Get the details
			$order_items = $order->getAllItems();

			// Put details into correct format for API
			$trnsx_items = array();
			foreach ($order_items as $itemId => $item) {
    			
				$trnsx_items[$item->getSku()] = array(
													$item->getQtyToInvoice(),
													$item->getPrice()
												);
			}

			// Build the appropriate JSON object
			$payload = json_encode(array("order_id" => $order_id, "tinybttn_id" => $tinybttn_id, "items" => $trnsx_items));

			$tinybttn_api_secret = Mage::helper("TinyBttn")->getApiSecret();
			
			// Encode into JWT using the API_SECRET
			$jwt = JWT::encode($pay, $tinybttn_api_secret);
			
			Mage::helper("TinyBttn")->post_to_tinybttn('transaction', null, $jwt);
			
			// Delete all existing Cart Rules!
			foreach($session['tinybttn_created'] as $rule_id){
				
				$model = Mage::getModel('salesrule/rule')
				        ->getCollection()
				        ->addFieldToFilter('name', array('eq'=>$rule_id))
				        ->getFirstItem();
				
				$model->delete();
				
				unset($session['tinybttn_created'][$rule_id]);	// Remove the rule from the array
			}

		}

		return Mage::getModel("sales/order")->loadByIncrementId($order_id);  
	}
}