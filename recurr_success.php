<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");

include("header.php");
echo '<div id="content-container">';

/* ==================================================================
'  Order Review Page
'
'  User come back from Paypal site after login - return URL
'  PayPal Express Checkout Call - GetExpressCheckoutDetails()
   ===================================================================
*/


$token = $_REQUEST['token'];
$Plan = $_REQUEST["p"];
		
// get product information
$padata = getProductArr($Plan);


// If the Request object contains the variable 'token' then it means that the user is coming from PayPal site.	
if ( $token != "" )
{

		if ($_SESSION["PLAN"] ==1) 
			$RecurrPaymentAmount = $_SESSION["Recurr_Amount"];
		else
			$RecurrPaymentAmount = $_SESSION["Payment_Amount"];


		/*
		'-------------------------------------------------
		' (3) Calls the CreateRecurringPaymentsProfile API call
		'
		' The CreateRecurringPaymentsProfile function is defined in the file paypal_ecfunctions.php,
		' that is included at the top of this file.
		'-------------------------------------------------
		*/
    	$resRecurrArray = CreateRecurringPaymentsProfile ( $RecurrPaymentAmount, $padata );		
		$ack = strtoupper($resRecurrArray["ACK"]);
		if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" )
		{		
			//Getting transaction ID from API responce. 
            $PROFILEID = urldecode($resRecurrArray["PROFILEID"]);
            $PROFILESTATUS = urldecode($resRecurrArray["PROFILESTATUS"]);
            
					
			//---------------------------------------
			// Save Profile Information into DB
			//--------------------------------------- 
			SaveProfile($resRecurrArray);
						
					
			// Clear Session
			$_SESSION = array();
		
?>
	
			<div id="content">		
			<h2>Success</h2>
			<BR>Thank you for your payment.
			<br><br> Profile ID: <?php echo $PROFILEID; ?>
			<br><br>							
			<?php 				
				$resRecurrData = reformat_arr($resRecurrArray); 
				echo '<b>CreateRecurringPaymentsProfile Responses:</b><p style="font-size:12px">'.$resRecurrData.'</p>';					
			?>					

<?php			
		}
		else  
		{
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			DisplayErrorMessage('CreateRecurringPaymentsProfile', $resRecurrArray, $token);
		}	

?>	
	
		</div>
		<!-- content -->
		

<?php

		include("footer.php");

 } 

 // no token
 else {
	
		header("Location: index.php"); // back to cart if don't have cart items 
		exit;

 }
	




?>
