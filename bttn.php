<?php
	session_start();
	
	// If it's been greater than 10 minutes since the user queried the API ...
    if(isset($_SESSION['tinybttn_last_post'])){
	    if($_SESSION['tinybttn_last_post']->diff(new Datetime('now'))->format("%i") > 10){
		    
		    // ... clear out identifying info and force them to re-submit their identifying info using OneID
		    $_SESSION['tinybttn_id'] = null;
		    $_SESSION['tinybttn_email'] = null;
		    
	    }
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">


<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>TinyBttn Demo</title>
</head>

<style type="text/css">
.tinybttn {
	-moz-box-shadow:inset 0px 1px 0px 0px #fce2c1;
	-webkit-box-shadow:inset 0px 1px 0px 0px #fce2c1;
	box-shadow:inset 0px 1px 0px 0px #fce2c1;
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #ffc477), color-stop(1, #fb9e25) );
	background:-moz-linear-gradient( center top, #ffc477 5%, #fb9e25 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffc477', endColorstr='#fb9e25');
	background-color:#ffc477;
	-moz-border-radius:6px;
	-webkit-border-radius:6px;
	border-radius:6px;
	border:1px solid #eeb44f;
	display:inline-block;
	color:#ffffff;
	font-family:arial;
	font-size:15px;
	font-weight:normal;
	padding:7px 12px;
	text-decoration:none;
	text-shadow:1px 1px 0px #cc9f52;
}.tinybttn:hover {
	background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #fb9e25), color-stop(1, #ffc477) );
	background:-moz-linear-gradient( center top, #fb9e25 5%, #ffc477 100% );
	filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#fb9e25', endColorstr='#ffc477');
	background-color:#fb9e25;
}.tinybttn:active {
	position:relative;
	top:1px;
}
</style>
<body style="font-family: arial; font-size: small;">


<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="https://api.oneid.com/js/includeexternal.js" type="text/javascript"></script>
<script src="https://api.oneid.com/form/form.js" type="text/javascript"></script>


<!-- The javascript to determine if OneID needs to be called to get the user's credentials !-->
<script type="text/javascript">

	<?php 
	
		// I tried using the session variable in the logic directly in the javascript but the js seems to need hard '1' or '0' values,
		//  so used php to generate the correctly value explicitly then echo $tb_id and $tb_em in the javascript
		if(empty($_SESSION['tinybttn_id']))
			$tb_id = '0';
		else 
			$tb_id = '1';
			
		if(empty($_SESSION['tinybttn_email']))
			$tb_em = '0';
		else
			$tb_em = '1';
	?>

	function getTinyBttnDiscounts(){
		
		// *** If the user aleady has OneID-provided data in the session, then don't prompt them to share via OneID again
		
		// Did they have a a TinyBttnID (e.g. are they a fully-registered member)?
		if(<?php echo $tb_id; ?>){
		
			$.post("bttn_result.php", { 1 : '<?php echo $_SESSION['tinybttn_email']; ?>', 2 : '<?php echo $_SESSION['tinybttn_id']; ?>' } )
			.done( function(data) {
				success: {
				
					document.getElementById('response').innerHTML=data;
					$(".tinybttn").hide();
					}
			});

		}
		
		// Did they have a a TinyBttnEMAIL (e.g. are they a member who hasn't completed full registration)?
		else if(<?php echo $tb_em; ?>){
			
			$.post("bttn_result.php", { 1 : '<?php echo $_SESSION['tinybttn_email']; ?>' } )
			.done( function(data) {
				success: {
				
					document.getElementById('response').innerHTML=data;
					$(".tinybttn").hide();
					}
			});
			
		}
		
		// *** The user is either NOT a member -or- they have not shared their details using OneID
		else{
				OneIdExtern.registerApiReadyFunction(
					function(){
						OneId.getUserAttributes(
							{
								attr: "email[email] TinyBttn[ID]",
								authLevel:null,
								selectCards:false,
								forceSelectCards:false
							},
							function(data){
								if(data.attribute_data.TinyBttn){	// If the TinyBttn ID is in their OneID repository, then send that too

									$.post("bttn_result.php", { 1 : data.attribute_data.email.email, 2 : data.attribute_data.TinyBttn.ID } )
									.done( function(data2) {
										success: {
										
											document.getElementById('response').innerHTML=data2;
											$(".tinybttn").hide();
											}
									});
								}
								else {	// If they only have an email in their OneID repository, then just send it
								
									$.post("bttn_result.php", { 1 : data.attribute_data.email.email } )
									.done( function(data2) {
										success: {
										
											document.getElementById('response').innerHTML=data2;
											$(".tinybttn").hide();
											}
									});
								}
							}
						);
					}
				);
		}
	};

</script>
	<div id="wrapper">
		<button class="tinybttn" onclick="getTinyBttnDiscounts()">TinyBttn</button>
		<span class="slogan"></span>
		<p>
	</div>
    <div id="response"></div>
</body>
</html>
