<?php

class TinyBttn_Connector_Helper_Data extends Mage_Core_Helper_Abstract {

    const CATALOG_API_ENDPOINT_PATH = 'OneID/endpoint/catalog';
	const DISCOUNT_API_ENDPOINT_PATH = 'OneID/endpoint/discount';
	const TRANSACTION_API_ENDPOINT_PATH = 'OneID/endpoint/transaction';

    const API_ID_PATH = 'TinyBttn/TinyBttn_authentication/api_id';
	const API_PASS_PATH = 'TinyBttn/TinyBttn_authentication/api_pass';
    const API_KEY_PATH = 'TinyBttn/TinyBttn_authentication/api_key';
	
    const MIN_IE_VERSION = 9.0;
    
    public function isBrowserOneIdSupported(){
        $supported = true;
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        
        
        if (preg_match("#(msie)[/ ]?([0-9.]*)#", $ua, $match)) { 
            $name = $match[1] ; 
            $version = (float)$match[2];
            $supported = $version >= self::MIN_IE_VERSION;    
        }
        return $supported;
    }


    public function getCatalogEndPoint() {
        return (string)Mage::getStoreConfig(self::CATALOG_API_ENDPOINT_PATH);
    }
    
    public function getDiscountEndPoint() {
        return (string)Mage::getStoreConfig(self::DISCOUNT_API_ENDPOINT_PATH);
    }
	
    public function getTransactionEndPoint() {
        return (string)Mage::getStoreConfig(self::TRANSACTION_API_ENDPOINT_PATH);
    }


    
    public function getApiId() {
        return (string)Mage::getStoreConfig(self::API_ID_PATH);
    }
	
    public function getApiPass() {
        return (string)Mage::getStoreConfig(self::API_PASS_PATH);
    }
    
    public function getApiKey() {
        return (string)Mage::getStoreConfig(self::API_KEY_PATH);
    }
    
	
	
	// Function to convert the JSON-decoded object from stdClass
	public function objectToArray($d) {
		if (is_object($d))
			$d = get_object_vars($d);

		if (is_array($d))
			return array_map(__FUNCTION__, $d);
		else
			return $d;
	}

	// The POST function
	public function post_to_tinybttn($endpoint, $type=null, $data=null) {
		
		// Require the library to decode the JSON Web Token (JWT)
		require("jwt.php");
		
        $tinybttn_api_id = $this->getApiId();
        $tinybttn_api_pass = $this->getApiPass();
		$tinybttn_api_key = $this->getApiKey();
		
		// Set the correct endpoint, depending on the function call
		switch ($endpoint) {
			case 'catalog':
				$tinybttn_endpoint = $this->getCatalogEndPoint();
				break;
			case 'discount':
				$tinybttn_endpoint = $this->getDiscountEndPoint();
				break;
			case 'transaction':
				$tinybttn_endpoint = $this->getTransactionEndPoint();
				break;
		}

		// Set the corrected header for requests sent to the Discount API Endpoint 
		switch ($type) {
			case 1:
				$header = array("Request-Type: Discount/1");
				break;
			case 2:
	        	$header = array("Request-Type: Discount/2");
	        	break;
	        case 3:
	        	$header = array("Request-Type: Discount/3");
	        	break;
	        case 4:
	        	$header = array("Request-Type: Discount/4");
	        	break;
	    }

		$ch = curl_init($tinybttn_endpoint);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $tinybttn_api_id . ':' . $tinybttn_api_pass);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);

		// Response interpretation & error handling
		if (substr($response, 0, 2) == 'ey')
			return $this->objectToArray(JWT::decode($response, $tinybttn_api_key));
		else {
			if (substr($response, 2, 5) == 'error') {
				$fail = $this->objectToArray(json_decode($response));
				return $fail['error'];	// A proper error was returned, output string
			}
			else
				return 'Something went wrong';	// Unexpected error was returned, output string
		}
	}
	
	// This function creates SKU-specific Shopping Cart Rules
	public function createProductDiscount($sku, $discount = 0, $max = null, $step = 0, $free_ship = 0, $id = null) {
	  if ($id != null && $discount != 0){
	    $rule = Mage::getModel('salesrule/rule');
	    $customer_groups = array(0, 1, 2, 3);
	    $rule->setName($id)
	      ->setDescription($id . rand(100000, 999999))
	      ->setFromDate('')
	      ->setCouponType(2)
	      ->setCouponCode(Mage::helper('core')->getRandomString(16))
	      ->setUsesPerCustomer(1)
	      ->setCustomerGroupIds($customer_groups) 			// An array of customer grou pids
	      ->setIsActive(1)
	      ->setConditionsSerialized('')
	      ->setActionsSerialized('')
	      ->setStopRulesProcessing(1)
	      ->setIsAdvanced(1)
	      ->setProductIds('')
	      ->setSortOrder(0)
	      ->setSimpleAction('by_percent')
	      ->setDiscountAmount($discount*100)				// Multiply by 100 because the value from the API is a decimal
	      ->setDiscountQty($max)
	      ->setDiscountStep($step)
	      ->setSimpleFreeShipping($free_ship)				// This originally had quotes like '0'
	      ->setApplyToShipping('0')
	      ->setIsRss(0)
	      ->setStoreLabels(array('TinyBttn discount'))		// I added this, so it needs to be checked
	      ->setWebsiteIds(array(1));
 
	    $item_found = Mage::getModel('salesrule/rule_condition_product_found')
	      ->setType('salesrule/rule_condition_product_found')
	      ->setValue(1) 			// 1 == FOUND
	      ->setAggregator('all'); 	// match ALL conditions
    
	    $rule->getConditions()->addCondition($item_found);
    
	    $conditions = Mage::getModel('salesrule/rule_condition_product')
	      ->setType('salesrule/rule_condition_product')
	      ->setAttribute('sku')
	      ->setOperator('==')
	      ->setValue($sku);
    
	    $item_found->addCondition($conditions);
 
	    $actions = Mage::getModel('salesrule/rule_condition_product')
	      ->setType('salesrule/rule_condition_product')
	      ->setAttribute('sku')
	      ->setOperator('==')
	      ->setValue($sku);
    
	    $rule->getActions()->addCondition($actions);
	    $rule->save();
	  }
	}




	// This function creates applicable-to-the-whole-cart Shopping Cart Rules (usually just one, but if there's a upper-limit to savings, then it needs to create two (see note).
	public function createGeneralDiscount($discount = 0, $limit = 0, $free_ship = 0, $id = null, $title = '') {
	  if ($id != null && $discount != 0){ 
		$rule = Mage::getModel('salesrule/rule');
		$customer_groups = array(0, 1, 2, 3);
		$rule->setName($id . rand(100000, 999999))	// We add on the randomized six digits here
		  ->setDescription($id)
		  ->setFromDate('')
		  ->setCouponType(2)
		  ->setCouponCode(Mage::helper('core')->getRandomString(16))
		  ->setUsesPerCustomer(1)
		  ->setCustomerGroupIds($customer_groups) 	// An array of customer grou pids
		  ->setIsActive(1)
		  ->setConditionsSerialized('')
		  ->setActionsSerialized('')
		  ->setStopRulesProcessing(1)
		  ->setIsAdvanced(1)
		  ->setProductIds('')
		  ->setSortOrder(0)
		  ->setSimpleAction('by_percent')
		  ->setDiscountAmount($discount*100)		// Multiply by 100 because the value from the API is a decimal
		  ->setSimpleFreeShipping($free_ship)		// This originally had quotes like '0'
		  ->setApplyToShipping('0')
		  ->setIsRss(0)
		  ->setStoreLabels(array($title . ' discount'))			// I added this, so it needs to be checked
		  ->setWebsiteIds(array(1));
 
      
	      // NOTE: If there is a discount limit, then we need to:
	      //	1. Add a condition to the first rule such that it only applies if the subtotal is < limit
	      //	2. Create a second rule that gives a fixed discount (the limit value) if subtotal is > limit
      
		if ($limit !== 0)){
	
			// *************************************************** Add the conditions to the first rule
			$conditions = array(
				"1" => array(
					"type" => "salesrule/rule_condition_combine",
					"aggregator" => "all",
					"value" => "1",
					"new_child" => null
					),
				"1--1" => array(
					"type" => "salesrule/rule_condition_address",
					"attribute" => "base_subtotal",
					"operator" => "<",
					"value" => $limit
					)
				);
		
			$rule->setData("conditions", $conditions);
			$rule->save();		
		
			// *************************************************** Create the Second Rule the fixed amount for subtotal > limit
			$rule2 = Mage::getModel('salesrule/rule');
			$rule2->setName($id . rand(100000, 999999))
			  ->setDescription($id)
			  ->setFromDate('')
			  ->setCouponType(2)
			  ->setCouponCode(Mage::helper('core')->getRandomString(16))
			  ->setUsesPerCustomer(1)
			  ->setCustomerGroupIds($customer_groups) 			// An array of customer grou pids
			  ->setIsActive(1)
			  ->setConditionsSerialized('')
			  ->setActionsSerialized('')
			  ->setStopRulesProcessing(1)
			  ->setIsAdvanced(1)
			  ->setProductIds('')
			  ->setSortOrder(0)
			  ->setSimpleAction('cart_fixed')			// Verify this ... by_fixed versus cart_fixed??
			  ->setDiscountAmount($limit)
			  ->setSimpleFreeShipping($free_ship)		// This originally had quotes like '0'
			  ->setApplyToShipping('0')
			  ->setIsRss(0)
			  ->setStoreLabels(array($title . ' discount'))			// I added this, so it needs to be checked
			  ->setWebsiteIds(array(1));
		  
			// *************************************************** Add the conditions to the second rule  
			$conditions = array(
				"1" => array(
					"type" => "salesrule/rule_condition_combine",
					"aggregator" => "all",
					"value" => "1",
					"new_child" => null
					),
				"1--1" => array(
					"type" => "salesrule/rule_condition_address",
					"attribute" => "base_subtotal",
					"operator" => ">=",
					"value" => $limit
					)
				);
		
			$rule->setData("conditions", $conditions);
			$rule2->save();
		}
	
		// If there isn't a limit, then just save the first rule without any conditions
		else
			$rule->save();
	  }
	}
}
