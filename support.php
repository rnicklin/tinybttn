<?php

	// Load JWT library
	require("jwt.php");
	
	
	// These need to be pulled from an administative section
	const DISCOUNT_API_ENDPOINT = 'https://rallyribbon.herokuapp.com/discount';
	const CATALOG_API_ENDPOINT = 'https://rallyribbon.herokuapp.com/catalog';
	const TRANSACTION_API_ENDPOINT = 'https://rallyribbon.herokuapp.com/transaction';
	const API_ID = 'z9NuFZUP12';
	const API_PASS = 'jFgNvXBDQm';
	const API_SECRET = 'Wa1SKO0tI36duv';
	
	
	// Gets rid of stdClass on the JSON-decoded object
	function objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object with get_object_vars function
			$d = get_object_vars($d);
		}
 
		if (is_array($d)) {
			// Return array converted to object Using __FUNCTION__ (Magic constant) for recursive call
			return array_map(__FUNCTION__, $d);
		}
		else {
			// Return array
			return $d;
		}
	}
	

	function post_to_tinybttn($endpoint, $type=null, $data=null) {

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

		$ch = curl_init($endpoint);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, API_ID . ':' . API_PASS);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);

		// Response interpretation & error handling
		if(substr($response, 0, 2) == 'ey'){
			return objectToArray(JWT::decode($response, API_SECRET));	// JWT retrieved, output Array
		}
		
		else {
			if(substr($response, 2, 5) == 'error') {
				$fail = objectToArray(json_decode($response));
				return $fail['error'];									// A proper error was returned, output string
			}
			else
				return 'Something went wrong';							// Unexpected error was returned, output string
		}	
	}
	
	// Load the cached for data
	if(file_exists('tinybttn.form')) {
		$cached_data = file_get_contents('tinybttn.form');
		$cached_data = unserialize($cached_data);
	}
		else {	
		$cached_data = post_to_tinybttn(DISCOUNT_API_ENDPOINT, '2');		// ... this also happens every six hours...
	
		// Write data to disk
		$to_write = serialize($cached_data);
		file_put_contents('tinybttn.form', $to_write, 0);	
	}

?>