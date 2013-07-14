<?php
	require("support.php");
	
	
	echo '<div style="padding-left: 5%; padding-top: 5px;">';
	echo '<h2>TinyBttn</h2>';

	// Split out the member discount array
	$member_discounts = $cached_data['member_discounts'];

	if(!empty($member_discounts)){		// Don't show dropdown if there aren't any point-of-sale verifications
		echo '<form action="form.php" method="post">';
		
		
		// The user is selecting which member_id they want to verify
		echo '<select name="member_id">';
	
	
		echo '<option value="">Select an affiliation</option>';
		
		
		// Dynamic options
		foreach($member_discounts as $md)
			echo '<option value="'. $md['member_id'] .'">'. $md['organization'] .'</option>';
	
		echo '</select>';
		echo '<input type="submit"></form>';
		echo '</div>';
	}

?>