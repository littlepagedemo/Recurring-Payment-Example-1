<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");

include("header.php");
echo '<div id="content-container">';
		
/* ==================================================================
'  Order Review Page - Confirm the recurring payment
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

	/*
	'-------------------------------------------------
	' (1) Calls the GetExpressCheckoutDetails API call
	'
	' The GetShippingDetails function is defined in paypal_ecfunctions.php
	' included at the top of this file.
	'-------------------------------------------------
	*/	

	$resGetArray = GetExpressCheckoutDetails( $token );
	$ack = strtoupper($resGetArray["ACK"]);
	if( $ack == "SUCCESS" || $ack == "SUCESSWITHWARNING") 
	{
			
		$payer_id = $_SESSION['payer_id'];
		
		//---------------------------------------
		// Save user's shipping address into DB
		//--------------------------------------- 
		SaveShipping_addr($resGetArray);
		
		
		/*
		'-------------------------------------------------------------------------
		' The paymentAmount is the total value of the shopping cart, that was set 
		' earlier in a session variable by the shopping cart page
		'-------------------------------------------------------------------------
		*/
	
		$finalPaymentAmount =  $_SESSION["Payment_Amount"]; 

		$resArray = array();
		
		// Initial Payment - process Authorization 
		if ($_SESSION["PLAN"] ==1) {
		
			/*
			'-------------------------------------------------
			' (2) Calls the DoExpressCheckoutPayment API call  
			'
			' The ConfirmPayment function is defined in the file paypal_ecfunctions.php,
			' that is included at the top of this file.
			'-------------------------------------------------
			*/
			$resArray = ConfirmPayment ( $finalPaymentAmount, $padata );
			$ack = strtoupper($resArray["ACK"]);
			if( $ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" )
			{		
				//Getting transaction ID from API responce. 
            	$TransactionID = urldecode($resArray["PAYMENTINFO_0_TRANSACTIONID"]);
            
				//---------------------------------------
				// Save Transaction Information into DB
				//--------------------------------------- 
				SaveTransaction($resArray);
				
			}
			else  
			{
				//Display a user friendly Error on the page using any of the following error information returned by PayPal
				DisplayErrorMessage('DoExpressCheckoutDetails', $resArray, $token);
				exit();
			}	
				
		}	// Plan == 1 initial payment								
				
?>

	
		<div id="content">		
		<h2>Confirm your informations</h2>
												
<?php

//---------------------------		
// Plan 1 - initial payment
//---------------------------		
		$recurr= $_SESSION["Recurr_Amount"];
		
		if($_SESSION["PLAN"] ==1) 
		{
				echo '<BR>Completed the Authorization for 10.00 signup fee.';
				echo '<br><br> Transaction ID:'. $TransactionID;
			
		}else
			$recurr= $_SESSION["Payment_Amount"];	
		
				
//-------------------------------
// Plan 1 & 2				
// Review recurring payment Form
//-------------------------------
?>						
		<br><br><b>You will pay $<?php echo $recurr;?> on the first day of every month.</b>
		<br><br><table border='1'>
		<tbody>
			<tr>
				<td>Name:
				<td><?php echo $_SESSION['lastName']." ".$_SESSION['firstName']; ?>
			</tr>
			<tr>
				<td>Email:
				<td><?php echo $_SESSION['email']; ?>
			</tr>
		<tbody>
		</table>
		<form action='recurr_success.php' METHOD='POST'>
			<input type="hidden" name="token" value="<?php echo $token; ?>">
			<input type="hidden" name="p" value="<?php echo $_SESSION[PLAN]; ?>">
			<input type="submit" class='chkbtn' value="Confirm"/>
		</form>
		
									
<?php					
		// Display API response
		if ($_SESSION["PLAN"] ==1) {
		
			echo '<br>This transaction includes a mixture of a one-time purchase and recurring payments profiles. 
			      <br>Call DoExpressCheckoutPayment to complete the one-time purchase transaction. 
			      <br>Then call CreateRecurringPaymentsProfile for each recurring payment profile you plan to create.
				  <br><br>(Right now there is no Recurring Profile yet, but you have completed the one time purchase $10.00)';
				
			$resData = reformat_arr($resArray); 
			echo '<br><br><br><b>DoEC Return results:</b><p style="font-size:10px">'.$resData.'</p>';											
		}
		
		$resGetData = reformat_arr($resGetArray); 
		echo '<br><br><b>GetEC Return results:</b><p style="font-size:10px">'.$resGetData.'</p>';				
							
	} 
	else  
	{
		//Display a user friendly Error on the page using any of the following error information returned by PayPal
		DisplayErrorMessage('GetExpressCheckoutDetails',$resArray, $token);
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
