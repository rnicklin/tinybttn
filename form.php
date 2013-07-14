<?php
	require('support.php');


	echo '<div style="padding-left: 5%; padding-top: 5px;">';
	echo '<h2>TinyBttn</h2>';


	// Split out the three arrays
	$member_discounts  = $cached_data['member_discounts'];
	$mem_ver_join 	   = $cached_data['mem_ver_join'];
	$verification_reqs = $cached_data['verification_reqs'];
	
	foreach($member_discounts as $md){
		if($md['member_id'] == $_POST['member_id']){
			$organization = $md['organization'];
			$message = $md['message'];
			$logo_url = $md['logo_url'];
		}
			
	}
	echo '<img src="'. $logo_url .'" alt="logo" height="42" width="42">';
	echo '<p><h4>Please verify your affiliation with the '. $organization .'</h4>';
	
	// Message about the discount
	echo $message;
	
	echo '<p>';

	// The first three inputs (first, last, email) are always drawn
	echo '<form action="result.php" method="post" required>';
	echo '<input type="hidden" name="member_id" value="'. $_POST['member_id'] .'">';  
	echo '<input type="text" name="first_name" placeholder="Legal First Name" required><br>';
	echo '<input type="text" name="last_name" placeholder="Legal Last Name" required><br>';
	echo '<input type="email" name="email" placeholder="Email" required><br>';
	
	echo '<p>';
	
	// Dynamic part of the form
	$requirements = $mem_ver_join[$_POST['member_id']];
	foreach($requirements as $r){

		// Description of the requirement
		echo $verification_reqs[$r]['desc'] . ': ';
		
		// The input box, as defined by the data from my server
		// The NAME is the verification_requirement_ID
		
		echo '<br><input type="' . $verification_reqs[$r]['input_type'] . '" name="'. $r .'" min="'. $verification_reqs[$r]['min_value'] .'" max="'. $verification_reqs[$r]['max_value'] .'" required><p>';
	}
	
	echo '<input type="submit">';
	echo '</form>';
	
	echo '</div>';

?>