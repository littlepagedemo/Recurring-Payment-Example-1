<?php

session_start();
include_once("config.php");
include_once("paypal_ecfunctions.php");


/* --------------------------------------------------------
//  PayPal Express Checkout Call - SetExpressCheckout()
//  and redirect to paypal side
//---------------------------------------------------------
*/

		$Plan = $_GET["p"];
		
		// get product information
		$padata = getProductArr($Plan);
				
		// Plan =1 Authorization, Plan =2 Sale
		$paymentType = $_SESSION["paymentType"];	// from getProductArr()
			
		// If there is no associated purchase, set PAYMENTREQUEST_0_AMT to 0.
		$paymentAmount = $_SESSION["Payment_Amount"];
			
									
		//'-------------------------------------------------------------
		//' Calls the SetExpressCheckout API call
		//' Prepares the parameters for the SetExpressCheckout API Call
		//'-------------------------------------------------------------		
		$resArray = CallSetExpressCheckout ($paymentAmount, $padata );
		
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			//print_r($resArray);
			RedirectToPayPal ( $resArray["TOKEN"] );	// redirect to PayPal side to login
		} 
		else  
		{
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			DisplayErrorMessage('SetExpressCheckout', $resArray, $padata);
			
		}
			

	


			
?>
