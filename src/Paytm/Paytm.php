<?php
// src/Paytm/Paytm.php
namespace App\Paytm;
use App\Lib\Paytm\PaytmChecksum;
use Cake\Http\Client;


class Paytm
{
    public function initiatePayment($userdata)
    {
        // Implement the logic to initiate the payment with Paytm API
        // Use the $orderId, $amount, and other Paytm parameters
        // Return the Paytm payment gateway URL or form data

		/*
		* import checksum generation utility
		* You can get this utility from https://developer.paytm.com/docs/checksum/
		*/

		$paytmParams = array();
		$order_id = time();
		$paytmParams["body"] = array(
		    "requestType"   => "Payment",
		    "mid"           => env('MERCHANT_ID'),
		    "websiteName"   => env('PAYTM_WEBSITE'),
		    "orderId"       => $order_id,
		    "callbackUrl"   => env('PAYTM_CALLBACK_URL'),
		    "txnAmount"     => array(
		        "value"     => $userdata['fee'],
		        "currency"  => "INR",
		    ),
		    "userInfo"      => array(
		        "custId"    => $userdata['email'],
		    ),
		);

		//print_r($paytmParams);die;

		/*
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), env('MERCHANT_KEY'));


		$paytmParams["head"] = array(
		    "signature"    => $checksum
		);
		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);


		/* for Staging */
		$url = "https://securegw-stage.paytm.in/theia/api/v1/initiateTransaction?mid=".env('MERCHANT_ID')."&orderId=".$order_id;

		/* for Production */
		// $url = "https://securegw.paytm.in/theia/api/v1/initiateTransaction?mid=YOUR_MID_HERE&orderId=ORDERID_98765";

		/*$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json")); 
		$response = curl_exec($ch);*/

        $client = new Client();
        $response = $client->post($url, $post_data, ['type' => 'json']);
        $responseData = $response->getJson();
		//print_r($responseData);die;
		//return $responseData; 
		if(!empty($responseData['body']['resultInfo']['resultStatus']) && $responseData['body']['resultInfo']['resultStatus'] == 'S'){
			$txntoken = $responseData['body']['txnToken'];
			return $txntoken;
		}
	}

    public function verifyPaymentResponse($postData)
    {
        // Implement the logic to verify the payment response from Paytm
        // Use the $postData received from Paytm callback
        // Return true if the payment is successful, false otherwise

		/* initialize an array */
		$paytmParams = array();

		/* body parameters */
		$paytmParams["body"] = array(

		    /* Find your MID in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys */
		    "mid" => env('MERCHANT_ID'),

		    /* Enter your order id which needs to be check status for */
		    "orderId" => $postData['ORDERID'],
		);

		/**
		* Generate checksum by parameters we have in body
		* Find your Merchant Key in your Paytm Dashboard at https://dashboard.paytm.com/next/apikeys 
		*/
		$checksum = PaytmChecksum::generateSignature(json_encode($paytmParams["body"], JSON_UNESCAPED_SLASHES), env('MERCHANT_KEY'));

		/* head parameters */
		$paytmParams["head"] = array(

		    /* put generated checksum value here */
		    "signature"	=> $checksum
		);

		/* prepare JSON string for request */
		$post_data = json_encode($paytmParams, JSON_UNESCAPED_SLASHES);

		/* for Staging */
		$url = "https://securegw-stage.paytm.in/v3/order/status";

		/* for Production */
		// $url = "https://securegw.paytm.in/v3/order/status";

		/*$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  
		$response = curl_exec($ch);*/

	    $client = new Client();
        $response = $client->post($url, $post_data, ['type' => 'json']);
        $responseData = $response->getJson();
        return $responseData;


    }
}
