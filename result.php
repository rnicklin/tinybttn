<?php
	require('support.php');
	

	// Build the payload of user and verification-data
	$user = array();
	$ver_data = array();
	
	foreach($_POST as $k => $v) {
		if($k == 'first_name')
			$user['first_name'] = $v;
		elseif($k == 'last_name')
			$user['last_name'] = $v;
		elseif($k == 'email')
			$user['email'] = $v;
		elseif($k == 'member_id')
			$user['member_id'] = $v;
		else
			$ver_data[$k] = $v;
	}
	
	// Build the payload
	$payload = json_encode(array("user" => $user, "ver_data" => $ver_data));
	
	// Encode the payload into a JWT
	$jwt = JWT::encode($payload, API_SECRET);
	
	
	// Show something before we POST so the user knows something happened!
	echo '<div style="padding-left: 5%; padding-top: 5px;">';
	echo '<h2>TinyBttn</h2>';
	
	
	// Post the JWT to RallyRibbon
	$response = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '3', $jwt);

	if(!is_array($response)){
		echo $response;				// The two error cases return strings, so echo them here
	}
	else{
		$general = $response['general'][0];
		if(!empty($general))
			echo 'You qualify for a ' . $general['organization'] . ' ' . $general['member'] . ' discount of ' . $general['discount_amt']*100 . '%';
	}
	
	
	echo '</div>';



//			$first_letter = (string)$general['member'][0];
//
//			if(in_array('A', array('A','E','I','O','U')))
//				echo 'an ';
//			else
//				echo 'a ';

?>

