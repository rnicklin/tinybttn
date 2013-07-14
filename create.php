<?php

// This file contains the two principle discount creation functions.


public function createProductDiscount($sku, $discount = 0, $max = null, $step = 0, $free_ship = 0, $id = null) {
  if ($id != null $discount != 0){
    $rule = Mage::getModel('salesrule/rule');
    $customer_groups = array(0, 1, 2, 3);
    $rule->setName($id)
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





public function createGeneralDiscount($discount = 0, $limit = null, $free_ship = 0, $id = null, $title = '') {
  if ($id != null $discount != 0){ 
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
 
      
      // If there is a discount limit, then we need to:
      //	1. Add a condition to the first rule such that it only applies if the subtotal is < limit
      //	2. Create a second rule that is simply the discount limit if subtotal is > limit
      
	if (!is_null($limit)){
	
		// *************************************************** Add the conditions to the first rule
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
		
		
		// *************************************************** Create Second Rule
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
		  ->setDiscountAmount($discount*100)
		  ->setSimpleFreeShipping($free_ship)		// This originally had quotes like '0'
		  ->setApplyToShipping('0')
		  ->setIsRss(0)
		  ->setStoreLabels(array($title . ' discount'))			// I added this, so it needs to be checked
		  ->setWebsiteIds(array(1));
		  
		// *************************************************** Add the conditions to the second rule  
		$item_found2 = Mage::getModel('salesrule/rule_condition_product_found')
		  ->setType('salesrule/rule_condition_product_found')
		  ->setValue(1) 			// 1 == FOUND
		  ->setAggregator('all'); 	// match ALL conditions
		
		$rule2->getConditions()->addCondition($item_found);
		
		$conditions2 = Mage::getModel('salesrule/rule_condition_product')
		  ->setType('salesrule/rule_condition_product')
		  ->setAttribute('sku')
		  ->setOperator('==')
		  ->setValue($sku);
		
		$item_found2->addCondition($conditions);
		
		$actions2 = Mage::getModel('salesrule/rule_condition_product')
		  ->setType('salesrule/rule_condition_product')
		  ->setAttribute('sku')
		  ->setOperator('==')
		  ->setValue($sku);
		
		$rule2->getActions()->addCondition($actions);
		$rule2->save();
	}
	
	// If there isn't a limit, then just save the first rule without any conditions
	else
		$rule->save();
  }
}


?>