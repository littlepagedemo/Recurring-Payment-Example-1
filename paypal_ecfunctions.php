<?php

include_once("config.php");


	/*	
	' Define the PayPal Redirect URLs.  
	' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
	' 	change the URL depending if you are testing on the sandbox or the live PayPal site
	'
	' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
	' For the live site, the URL is     https://www.paypal.com/webscr&cmd=_express-checkout&token=
	*/	
	if ($SandboxFlag == true) 
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	}
	else
	{
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}


	//-----------------------------------------------------------
	// Return product information in array and set session value
	//-----------------------------------------------------------
	function getProductArr ($Plan) {
	
		global $paymentType;
		
		$ItemName=$ItemDesc=$paymentAmount='';
		
		$_SESSION["paymentType"] = $paymentType;
		
		// Signup fee includes the initial month's payment
		if($Plan == "1"){
			$ItemName = "Basic Plan";
			$ItemDesc = "Basic Plan $3.00 Signup & $5.00 per month";
			$paymentAmount = 10.00;
			
			$_SESSION['PLAN'] = '1';
			$_SESSION["Payment_Amount"] = 10.00;	// one time purchase item
			$_SESSION["Recurr_Amount"] = 5.00;
			
		}

		// start today, and bill today
		if($Plan == "2"){
			$ItemName = "Standard Plan";
			$ItemDesc = "Standard Plan $15.00 per month";
			$paymentAmount = 15.00;
			
			$_SESSION['PLAN'] = '2';
			$_SESSION["Payment_Amount"] = 15.00;
			
		}			

		// one month free trial 
		if($Plan == "3"){
			$ItemName = "One month free trial Plan";
			$ItemDesc = "One month free trial $3.00 Signup & $6.00 per month";
			$paymentAmount = 6.00;
			
			$_SESSION['PLAN'] = '3';
			$_SESSION["Payment_Amount"] = 6.00;
			
		}

		// one month free trial 
		if($Plan == "4"){
			$ItemName = "Two years Plan. 6 months trial";
			$ItemDesc = "Two years Plan. $3.00 Setup & $6.00 / mo. 6 months trial $2.00";
			$paymentAmount = 6.00;
			
			$_SESSION['PLAN'] = '4';
			$_SESSION["Payment_Amount"] = 6.00;
			
		}
				
		// Cart items
		$padata = array('name' => $ItemName, 
						'desc' => $ItemDesc,
						'qty' => 1,
						'amt' => $paymentAmount);	
		
		return $padata;			
			
	}
	
	
	/* An express checkout transaction starts with a token, that
	   identifies to PayPal your transaction
	   In this example, when the script sees a token, the script
	   knows that the buyer has already authorized payment through
	   paypal.  If no token was found, the action is to send the buyer
	   to PayPal to first authorize payment
	   */

	/*   
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
	' From:		Checkout from Shopping Cart
	' Inputs:  
	'		paymentAmount:  	Total value of the shopping cart
	'		currencyCodeType: 	Currency code value the PayPal API
	'		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
	'		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
	'		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
	'       padata:				cart items details
	'
	'		SetExpressCheckout Fields for Recurring Payments
	'		L_BILLINGTYPEn					Type of billing agreement. This field must be RecurringPayments
	'		L_BILLINGAGREEMENTDESCRIPTIONn	Description of goods or services associated with the billing agreement.
	'		You must include these same values as part of the CreateRecurringPaymentsProfile request.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function CallSetExpressCheckout( $paymentAmount, $padata) 
	{
		global $PayPalCurrencyCode, $paymentType, $PayPalReturnURL, $PayPalCancelURL;
		
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation
		
		$nvpstr="&PAYMENTREQUEST_0_AMT=". urlencode("$paymentAmount");
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . urlencode("$paymentType");
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . urlencode("$PayPalCurrencyCode");	
			
		// append the plan id
		$PayPalReturnURL .='?p='.$_SESSION['PLAN'];	
		
		$nvpstr = $nvpstr . "&RETURNURL=" . urlencode("$PayPalReturnURL");
		$nvpstr = $nvpstr . "&CANCELURL=" . urlencode("$PayPalCancelURL");

	
		//----------------------------------
		// Create Recurring Payment in SetEC		
		$billingtype='RecurringPayments';		
		
		$nvpstr = $nvpstr . "&DESC=" . urlencode($padata["desc"]);
		$nvpstr = $nvpstr . "&L_BILLINGTYPE0=" . urlencode("$billingtype");
		$nvpstr = $nvpstr . "&L_BILLINGAGREEMENTDESCRIPTION0=" . urlencode($padata["desc"]);	
					
		//echo $nvpstr;
		//exit();
		
		//'--------------------------------------------------------------------------------------------------------------- 
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.  
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
	    $resArray=hash_call("SetExpressCheckout", $nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{
			$token = urldecode($resArray["TOKEN"]);
			$_SESSION['TOKEN']=$token;
		}
		   
	    return $resArray;
	}
	



	/*
	'-------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
	' After Come back from PayPal
	'
	' Inputs:  
	'		None
	' Returns: 
	'		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
	'-------------------------------------------------------------------------------------------
	*/
	function GetExpressCheckoutDetails( $token )
	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------
	   
	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
	    $nvpstr="&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.  
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.  
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
	    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
	    $ack = strtoupper($resArray["ACK"]);
		if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
		{	
			$_SESSION['payer_id'] =	$resArray['PAYERID'];
			$_SESSION['email'] =	$resArray['EMAIL'];
			$_SESSION['firstName'] = $resArray["FIRSTNAME"]; 
			$_SESSION['lastName'] = $resArray["LASTNAME"]; 
			$_SESSION['shipToName'] = $resArray["SHIPTONAME"]; 
			$_SESSION['shipToStreet'] = $resArray["SHIPTOSTREET"]; 
			$_SESSION['shipToCity'] = $resArray["SHIPTOCITY"];
			$_SESSION['shipToState'] = $resArray["SHIPTOSTATE"];
			$_SESSION['shipToZip'] = $resArray["SHIPTOZIP"];
			$_SESSION['shipToCountry'] = $resArray["SHIPTOCOUNTRYCODE"];			
		} 
		return $resArray;
	}
		
	

	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the DoExpressCheckoutPayment API Call.
	'			- Completed the Authorization for Payment
	' Inputs:  
	'		Note that the "DESC" is set to the same value of the "L_BILLINGAGREEMENTDESCRIPTION0" variable in previous calls. 
	
	' Returns: 
	'		The NVP Collection object of the DoExpressCheckoutPayment Call Response.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/
	function ConfirmPayment( $FinalPaymentAmt, $padata)
	{
		global $PayPalCurrencyCode;
		
		/* 	Gather the information to make the final call tofinalize the PayPal payment. 
			The variable nvpstr holds the name value pairs		  
		*/

		//Format the other parameters that were stored in the session from the previous calls			
		$token 				= urlencode($_SESSION['TOKEN']);
		$payerID 			= urlencode($_SESSION['payer_id']);		
		$paymentType 		= urlencode($_SESSION["paymentType"]);	// Plan =1 Authorization, Plan =2 Sale
		$currencyCodeType 	= urlencode($PayPalCurrencyCode);
	
		
		$nvpstr  =  '&TOKEN=' . $token . 
					'&PAYERID=' . $payerID . 
					'&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . 
					'&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
		$nvpstr .=  '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType; 


		//----------------------------------
		// Create Recurring Payment in DoEC		
		$billingtype='RecurringPayments';		
		
		$nvpstr = $nvpstr . "&DESC=" . urlencode($padata["desc"]);
		$nvpstr = $nvpstr . "&L_BILLINGTYPEn=" . urlencode("$billingtype");
		$nvpstr = $nvpstr . "&L_BILLINGAGREEMENTDESCRIPTIONn=" . urlencode($padata["desc"]);


		 /* Make the call to PayPal to finalize payment
		    If an error occured, show the resulting errors
		*/
		$resArray=hash_call("DoExpressCheckoutPayment",$nvpstr);

		/* Display the API response back to the browser.
		   If the response from PayPal was a success, display the response parameters'
		   If the response was an error, display the errors received using APIError.php.
		   */
		$ack = strtoupper($resArray["ACK"]);

		return $resArray;
	}


	/*
	'-------------------------------------------------------------------------------------------------------------------------------------------
	' Purpose: 	Prepares the parameters for the CreateRecurringPaymentsProfile API Call.
	'
	' Inputs:  
	'		PROFILESTARTDATE	The date when billing for this profile begins.
	'		BILLINGPERIOD		The unit of measure for the billing cycle. Must be one of: Day / Week / SemiMonth / Month / Year
	'		BILLINGFREQUENCY	Number of billing periods that make up one billing cycle.
	'		AMT					Amount to bill for each billing cycle. This amount does not include shipping and tax amounts.
	'		DESC				You must ensure that this field matches the corresponding billing agreement description included in the SetExpressCheckout request.
	'		TOTALBILLINGCYCLES  If no value is specified or the value is 0, the regular payment period continues until the profile is canceled or deactivated.

	' Returns: 
	'		The NVP Collection object of the CreateRecurringPaymentsProfile Call Response.
	'
	' If the transaction includes a mixture of a one-time purchase and recurring payments profiles, 
	' call DoExpressCheckoutPayment to complete the one-time purchase transaction, 
	' and then call CreateRecurringPaymentsProfile for each recurring payment profile to be created.
	'--------------------------------------------------------------------------------------------------------------------------------------------	
	*/			
	
	function CreateRecurringPaymentsProfile( $FinalPaymentAmt, $padata )
	{
		global $PayPalCurrencyCode, $SetupPaymentAmt;
		
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------
		$token 			= urlencode($_SESSION['TOKEN']);
		$email 			= urlencode($_SESSION['email']);
		$shipToName		= urlencode($_SESSION['shipToName']);
		$shipToStreet	= urlencode($_SESSION['shipToStreet']);
		$shipToCity		= urlencode($_SESSION['shipToCity']);
		$shipToState	= urlencode($_SESSION['shipToState']);
		$shipToZip		= urlencode($_SESSION['shipToZip']);
		$shipToCountry	= urlencode($_SESSION['shipToCountry']);
	   
	    $currencyCodeType = urlencode($PayPalCurrencyCode); 
	    $fullname = $firstname.' '.$lastname;
		
		$startDate = date('Y-m-d').'T0:0:0';	 			   
		
		$billingPeriod = urlencode("Month");				// or "Day", "Week", "SemiMonth", "Year"
		$billingFreq = urlencode("1");						// combination of this and billingPeriod must be at most a year

	   
	    //'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------	
	
		$nvpstr="&TOKEN=".$token;
		$nvpstr.="&EMAIL=".$email;
		$nvpstr.="&SHIPTONAME=".$shipToName;
		$nvpstr.="&SHIPTOSTREET=".$shipToStreet;
		$nvpstr.="&SHIPTOCITY=".$shipToCity;
		$nvpstr.="&SHIPTOSTATE=".$shipToState;
		$nvpstr.="&SHIPTOZIP=".$shipToZip;
		$nvpstr.="&SHIPTOCOUNTRY=".$shipToCountry;

		$nvpstr.='&CURRENCYCODE='. $currencyCodeType;
		$nvpstr.='&AMT='. $FinalPaymentAmt;					// Amount to bill for each billing cycle				
		$nvpstr.="&SUBSCRIBERNAME=".$shipToName;
		
		$nvpstr.="&PROFILESTARTDATE=".urlencode($startDate);	//Billing date start, in UTC/GMT format			
		$nvpstr.="&DESC=".urlencode($padata["desc"]);	//Profile description - same as billing agreement description
		$nvpstr.="&BILLINGPERIOD=".$billingPeriod;		//Period of time between billings
		$nvpstr.="&BILLINGFREQUENCY=".$billingFreq;		//Frequency of charges - every 1 month
		
		
		//Indicates whether you would like PayPal to automatically bill the outstanding balance amount in the next billing cycle. 
		$nvpstr.="&AUTOBILLOUTAMT=".urlencode("AddToNextBilling");	
		$nvpstr.="&MAXFAILEDPAYMENTS=".urlencode("3");    //Maximum failed payments before suspension of the profile
		
		// Initial payment
		if( $_SESSION['PLAN']!=2) 
			$nvpstr.="&INITAMT=". $SetupPaymentAmt;			//Initial non-recurring payment amount due immediately upon profile creation. 
		
		// Free trial
		if( $_SESSION['PLAN']==3) {
			$nvpstr.="&TRIALBILLINGPERIOD=".urlencode("Month");	//Period of time in one trial period
			$nvpstr.="&TRIALBILLINGFREQUENCY=".urlencode("1");	//Frequency of charges, if any, during the trial period
			$nvpstr.="&TRIALTOTALBILLINGCYCLES=".urlencode("1");//Length of trial period
			$nvpstr.="&TRIALAMT=".urlencode("0");				//Payment amount (can be 0) during the trial period
		}
		
		// 2 year plan and cheaper price for first 6 months
		if( $_SESSION['PLAN']==4) {
			$nvpstr.="&TRIALBILLINGPERIOD=".urlencode("Month");	//Period of time in one trial period
			$nvpstr.="&TRIALBILLINGFREQUENCY=".urlencode("1");	//Frequency of charges, if any, during the trial period
			$nvpstr.="&TRIALTOTALBILLINGCYCLES=".urlencode("6");//Length of trial period
			$nvpstr.="&TRIALAMT=".urlencode("2.00");			//Payment amount (can be 0) during the trial period
			$nvpstr.="&TOTALBILLINGCYCLES=".urlencode("18");	//Payment period 
		}
		
		$nvpstr.='&L_PAYMENTREQUEST_0_ITEMCATEGORY0=Digital'; 
		$nvpstr.='&L_PAYMENTREQUEST_0_NAME0='.urlencode($padata["name"]);  
		$nvpstr.='&L_PAYMENTREQUEST_0_AMT0='.urlencode($FinalPaymentAmt);  // insert the final payment
		$nvpstr.='&L_PAYMENTREQUEST_0_QTY0=1';		
		
		$nvpstr.="&IPADDRESS=" . $_SERVER['REMOTE_ADDR'];

		
		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.  
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.  
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
		$resArray=hash_call("CreateRecurringPaymentsProfile",$nvpstr);
		$ack = strtoupper($resArray["ACK"]);
		return $resArray;
	}
	
		

	/**
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	  * hash_call: Function to perform the API call to PayPal using API signature
	  * @methodName is name of API  method.
	  * @nvpStr is nvp string.
	  * returns an associtive array containing the response from the server.
	  '-------------------------------------------------------------------------------------------------------------------------------------------
	*/
	function hash_call($methodName,$nvpStr)
	{
		//declaring of global variables
		global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
		global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
		global $gv_ApiErrorURL;
		global $sBNCode;

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POST, 1);
		
	    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	    //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php 
		if($USE_PROXY)
			curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT); 

		//NVPRequest for submitting to server
		$nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);


		//var_dump($nvpreq);
		$nvparr = deformatNVP($nvpreq);
		$resRecurrData = reformat_arr($nvparr); 
		echo '<p style="font-size:10px;padding:2%"><b>API CAll</b>'.$resRecurrData.'</p>';
		
		
		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray=deformatNVP($response);
		$nvpReqArray=deformatNVP($nvpreq);
		$_SESSION['nvpReqArray']=$nvpReqArray;

		if (curl_errno($ch)) 
		{
			// moving to display page to display curl errors
			  $_SESSION['curl_error_no']=curl_errno($ch) ;
			  $_SESSION['curl_error_msg']=curl_error($ch);

			  //Execute the Error handling module to display errors. 
		} 
		else 
		{
			 //closing the curl
		  	curl_close($ch);
		}

		return $nvpResArray;
	}


	/*'----------------------------------------------------------------------------------
	 Purpose: Redirects to PayPal.com site.
	 Inputs:  NVP string.
	 Returns: 
	----------------------------------------------------------------------------------
	*/
	function RedirectToPayPal ( $token )
	{
		global $PAYPAL_URL;
			
		// Redirect to paypal.com here
		$payPalURL = $PAYPAL_URL . $token;
		
		header("Location: ".$payPalURL);
		exit;
	}

	
	/*'----------------------------------------------------------------------------------
	 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
	  * It is usefull to search for a particular key and displaying arrays.
	  * @nvpstr is NVPString.
	  * @nvpArray is Associative Array.
	   ----------------------------------------------------------------------------------
	*/
	function deformatNVP($nvpstr)
	{
		$intial=0;
	 	$nvpArray = array();

		while(strlen($nvpstr))
		{
			//postion of Key
			$keypos= strpos($nvpstr,'=');
			//position of value
			$valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval=substr($nvpstr,$intial,$keypos);
			$valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] =urldecode( $valval);
			$nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
	     }
		return $nvpArray;
	}
	
	
	//-------------------------------------------	
	// reformat PayPal responses data
	//-------------------------------------------	
	function reformat_arr($data_arr) {
	
		$result ='';
		foreach ($data_arr as $key => $value) {
			$result .='<br>'.$key.'='.$value;
		}
		return $result;		
	}
	
	



	//-------------------------------------------
	// Display error from EC return result
	//-------------------------------------------
	function DisplayErrorMessage($ECAction,$resArray,$padata) {
	
			$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
			$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
			$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
			$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
	
			echo "<b>$ECAction API</b> call failed.";
			print_r($padata);
			echo "<br>Detailed Error Message: " . $ErrorLongMsg;
			echo "<br>Short Error Message: " . $ErrorShortMsg;
			echo "<br>Error Code: " . $ErrorCode;
			echo "<br>Error Severity Code: " . $ErrorSeverityCode;	
	
	}

	
	
	
	
	//------------------------------------------------
	// Save Checkout information (SetExressCheckout)
	// Recommend to save it to track the drop off rate
	//------------------------------------------------	
	function SaveCheckoutInfo($padata){
		
		$res_arr = explode("&",$padata);
		$r = count($res_arr);
		for ($i=1;$i<$r;$i++) {
			$resdata = explode("=",$res_arr[$i]);
			$resArray[$resdata[0]] = $resdata[1];
		}
		//print_r($resArray);


		// Setup your DB connection to save it
			
	}
	
	
	
	//-----------------------------------------------------------
	// Save Shipping Addr information (GetExpressCheckoutDetails)
	//-----------------------------------------------------------	
	function SaveShipping_addr($resArray){

		/*
		' The information that is returned by the GetExpressCheckoutDetails call should be integrated by the partner 
		' into his Order Review page		
		*/
		$email 				= $resArray["EMAIL"]; // ' Email address of payer.
		$payerId 			= $resArray["PAYERID"]; // ' Unique PayPal customer account identification number.
		$payerStatus		= $resArray["PAYERSTATUS"]; // ' Status of payer. Character length and limitations: 10 single-byte alphabetic characters.
		$salutation			= $resArray["SALUTATION"]; // ' Payer's salutation.
		$firstName			= $resArray["FIRSTNAME"]; // ' Payer's first name.
		$middleName			= $resArray["MIDDLENAME"]; // ' Payer's middle name.
		$lastName			= $resArray["LASTNAME"]; // ' Payer's last name.
		$suffix				= $resArray["SUFFIX"]; // ' Payer's suffix.
		$cntryCode			= $resArray["COUNTRYCODE"]; // ' Payer's country of residence in the form of ISO standard 3166 two-character country codes.
		$business			= $resArray["BUSINESS"]; // ' Payer's business name.
		$shipToName			= $resArray["PAYMENTREQUEST_0_SHIPTONAME"]; // ' Person's name associated with this address.
		$shipToStreet		= $resArray["PAYMENTREQUEST_0_SHIPTOSTREET"]; // ' First street address.
		$shipToStreet2		= $resArray["PAYMENTREQUEST_0_SHIPTOSTREET2"]; // ' Second street address.
		$shipToCity			= $resArray["PAYMENTREQUEST_0_SHIPTOCITY"]; // ' Name of city.
		$shipToState		= $resArray["PAYMENTREQUEST_0_SHIPTOSTATE"]; // ' State or province
		$shipToCntryCode	= $resArray["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"]; // ' Country code 
		$shipToCntryName	= $resArray["PAYMENTREQUEST_0_SHIPTOCOUNTRYNAME"]; // ' Country Name
		$shipToZip			= $resArray["PAYMENTREQUEST_0_SHIPTOZIP"]; // ' U.S. Zip code or other country-specific postal code.
		$addressStatus 		= $resArray["ADDRESSSTATUS"]; // ' Status of street address on file with PayPal   
		$invoiceNumber		= $resArray["INVNUM"]; // ' Your own invoice or tracking number, as set by you in the element of the same name in SetExpressCheckout request .
		$phonNumber			= $resArray["PHONENUM"]; // ' Payer's contact telephone number. Note:  PayPal returns a contact telephone number only if your Merchant account profile settings require that the buyer enter one. 
		


		// setup your DB connection to save it


	}	
	
	
	//-----------------------------------------------------------
	// Save Transaction information (DoExpressCheckoutPayment)
	//-----------------------------------------------------------		
	function SaveTransaction($resArray){
	
		/*
		'********************************************************************************************************************
		'
		' THE PARTNER SHOULD SAVE THE KEY TRANSACTION RELATED INFORMATION LIKE 
		'                    transactionId & orderTime 
		'  IN THEIR OWN  DATABASE
		' AND THE REST OF THE INFORMATION CAN BE USED TO UNDERSTAND THE STATUS OF THE PAYMENT 
		'
		'********************************************************************************************************************
		*/

		$transactionId		= $resArray["PAYMENTINFO_0_TRANSACTIONID"]; // ' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs. 
		$transactionType 	= $resArray["PAYMENTINFO_0_TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout 
		$paymentType		= $resArray["PAYMENTINFO_0_PAYMENTTYPE"];  //' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant 
		$orderTime 			= $resArray["PAYMENTINFO_0_ORDERTIME"];  //' Time/date stamp of payment
		$amt				= $resArray["PAYMENTINFO_0_AMT"];  //' The final amount charged, including any shipping and taxes from your Merchant Profile.
		$currencyCode		= $resArray["PAYMENTINFO_0_CURRENCYCODE"];  //' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD. 
		$feeAmt				= $resArray["PAYMENTINFO_0_FEEAMT"];  //' PayPal fee amount charged for the transaction
		$settleAmt			= $resArray["PAYMENTINFO_0_SETTLEAMT"];  //' Amount deposited in your PayPal account after a currency conversion.
		$taxAmt				= $resArray["PAYMENTINFO_0_TAXAMT"];  //' Tax charged on the transaction.
		$exchangeRate		= $resArray["PAYMENTINFO_0_EXCHANGERATE"];  //' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer's account.
		
		/*
		' Status of the payment: 
				'Completed: The payment has been completed, and the funds have been added successfully to your account balance.
				'Pending: The payment is pending. See the PendingReason element for more information. 
		*/
		
		$paymentStatus	= $resArray["PAYMENTINFO_0_PAYMENTSTATUS"]; 

		/*
		'The reason the payment is pending:
		'  none: No pending reason 
		'  address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile. 
		'  echeck: The payment is pending because it was made by an eCheck that has not yet cleared. 
		'  intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview. 		
		'  multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment. 
		'  verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment. 
		'  other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service. 
		*/
		
		$pendingReason	= $resArray["PAYMENTINFO_0_PENDINGREASON"];  

		/*
		'The reason for a reversal if TransactionType is reversal:
		'  none: No reason code 
		'  chargeback: A reversal has occurred on this transaction due to a chargeback by your customer. 
		'  guarantee: A reversal has occurred on this transaction due to your customer triggering a money-back guarantee. 
		'  buyer-complaint: A reversal has occurred on this transaction due to a complaint about the transaction from your customer. 
		'  refund: A reversal has occurred on this transaction because you have given the customer a refund. 
		'  other: A reversal has occurred on this transaction due to a reason not listed above. 
		*/
		
		$reasonCode		= $resArray["PAYMENTINFO_0_REASONCODE"];  
		
		
		// setup your DB connection to save it
		
			
	}
	

	//----------------------------------------------------------
	// Save Profile information (CreateRecurringPaymentsProfile)
	//----------------------------------------------------------	
	function SaveProfile($resArray){

		/*
		' The information that is returned by the CreateRecurringPaymentsProfile call should be integrated by the partner 
		' into his Order Review page		
		*/
		$profileid 				= $resArray["PROFILEID"]; 		// ' A unique identifier for future reference to the details of this recurring payment.
		$profilestatus 			= $resArray["PROFILESTATUS"];   // ActiveProfile – The recurring payment profile has been successfully created and activated for scheduled 
																// PendingProfile – The system is in the process of creating the recurring payment profile. 
		
		
		// setup your DB connection to save it
		
			
	}
	
?>