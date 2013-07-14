<?php
	require("support.php");
	
	// Load the cached for data
	if(file_exists('tinybttn.form')) {
		$cached_data = file_get_contents('tinybttn.form');
		$cached_data = unserialize($cached_data);
	}
		else {	
		$cached_data = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '2');	// This also needs to happen every six hours
	
		// Write data to disk
		$to_write = serialize($cached_data);
		file_put_contents('tinybttn.form', $to_write, 0);	
	}
	
	
	echo '<div style="padding-left: 5%; padding-top: 5px;">';
	echo '<h2>TinyBttn</h2>';

	// Split out the member discount array loaded from the file (or obtained from TinyBttn API)
	$member_discounts = $cached_data['member_discounts'];

	if(!empty($member_discounts)){		// Don't show dropdown if there aren't any point-of-sale verifications
		echo '<form action="form.php" method="post">';
		
		
		echo '<select name="member_id">';	// The user is selecting which member_id they want to verify
	
		echo '<option value="">Select an affiliation</option>';  // Instructional / Default message
		
		
		foreach($member_discounts as $md)						// Dynamic options
			echo '<option value="'. $md['member_id'] .'">'. $md['organization'] .'</option>';
	
		echo '</select>';
		echo '<input type="submit"></form>';
		echo '</div>';
	}

?>