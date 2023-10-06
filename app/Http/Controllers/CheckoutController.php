<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    //Create Token
    public function checkout(Request $request)
    {
        // add checkout functionality

        $merchantId = config('paystation.merchant_id');
        $password   = config('paystation.merchant_password');

        $header=array(
            "merchantId:{$merchantId}",
            "password:{$password}"
        );

        $url = curl_init("https://api.paystation.com.bd/grant-token");
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $tokenData=curl_exec($url);
        curl_close($url);

        $token_res = json_decode($tokenData, true);


        if ($token_res['status_code'] == 200 && $token_res['status'] == 'success') {
           $response =  $this->createPayment($token_res, $request);
           return redirect()->away($response['payment_url']); //Redirect to paystation payment page
        }else{
            return redirect()->route('home');
        }

    }



    //Create Payment Url
    protected function createPayment($token_res, Request $request)
    {
        $token = $token_res['token'];

        $header=array(
            "token:{$token}"
        );

        $invoice_no = rand(11111111, 99999999);

        $body=array(
            'invoice_number' => "{$invoice_no}",
            'currency' => "BDT",
            'payment_amount' => "1",
            'reference' => "102030",
            'cust_name' => "Md Nazmul Hasan",
            'cust_phone' => "01700000001",
            'cust_email' => "nazmul@gmail.com",
            'cust_address' => "Dhaka, Bangladesh",
            'callback_url' => route('store-transaction', $token),
            'checkout_items' => "orderItems"
        );

        $url = curl_init("https://api.paystation.com.bd/create-payment");
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($url,CURLOPT_POSTFIELDS, $body);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $responseData=curl_exec($url);
        curl_close($url);

        $response = json_decode($responseData, true);

        return $response;

    }

    //Verify transaction and store payment details
    public function storeTransaction(Request $request, $token)
    {

        if ( $request->trx_id == null) {
            //redirect to cart page or dashboard page
            return redirect()->route('home')->with('error', 'Order failed');
        }

        //get transaction information

        $header=array(
            "token:{$token}"
        );

        $body=array(
            'invoice_number' => $request->invoice_number,
            'trx_id' => $request->trx_id
        );

        $url = curl_init("https://api.paystation.com.bd/retrive-transaction");
        curl_setopt($url,CURLOPT_HTTPHEADER, $header);
        curl_setopt($url,CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($url,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($url,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($url,CURLOPT_POSTFIELDS, $body);
        curl_setopt($url,CURLOPT_FOLLOWLOCATION, 1);
        $responseData=curl_exec($url);
        curl_close($url);

        $response = json_decode($responseData, true);

        if ($response['data']['trx_status'] == 'Failed' && $response['data']['trx_id'] == null) {
            //redirect to cart page or dashboard page
            return redirect()->route('home')->with('error', 'Order failed');
        }

        //Store Transaction Information and redirect to success page
        echo 'store payment transaction';
        return redirect()->route('home')->with('success','Order placed successfully');


    }




}
