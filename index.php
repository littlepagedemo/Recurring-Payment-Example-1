<?php

session_start();
include_once("config.php");
include("header.php");

?>
  	
	<div id="content-container">
	
		<div id="content">
			<h2>
				PayPal Recurring Payment.
			</h2>
			<br>You are now beginning the registration process
			
			<br><br>
			<div class="thumbnail">
			    <p>Use Case 1:<br>Setup Fee $3.00 and $5.00 every month</p>
				
				<br><b>Basic Plan <?php echo $PayPalCurrencyCode; ?> $3.00 Setup & $5.00 / month</b>
				<form method="post" action="checkout.php?p=1">
        			<br><input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
    			</form>
    			(It include one-time purchase $10.00)
			</div>
			
			
			<div class="thumbnail">
				<p>Use Case 2:<br>Recurring payment start today $15 every month.</p>

				<br><b>Standard Plan <?php echo $PayPalCurrencyCode; ?> $15.00 / month</b>
				<form method="post" action="checkout.php?p=2">
        			<br><input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
    			</form>
    			<br>
			</div>

			<div class="thumbnail">
				<p>Use Case 3:<br>Subscription with a one month free trial. $6.00 / month</p>

				<br><b>One month free trial Plan <?php echo $PayPalCurrencyCode; ?> $3.00 Setup & $6.00 / month</b>
				<form method="post" action="checkout.php?p=3">
        			<br><input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
    			</form>
			</div>	

			<div class="thumbnail">
				<p>Use Case 4:<br>Two years Plan $6.00/mo. Trial Period: $2.00/mo for the first 6 months. </p>

				<br><b>Two years Plan <?php echo $PayPalCurrencyCode; ?> $3.00 Setup & $6.00 / mo. 6 months trial $2.00</b>
				<form method="post" action="checkout.php?p=4">
        			<br><input type='image' name='submit' src='https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif' border='0' align='top' alt='Check out with PayPal'/>
    			</form>
			</div>	
								
		</div>
		


<?php
	include("footer.php");
?>