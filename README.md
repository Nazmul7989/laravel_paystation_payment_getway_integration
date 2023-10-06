# Integrate by using xenon/paystation package


### Installation

```
composer require xenon/paystation
```
## Sample Code

### Update .env file
```
PAYSTATION_MERCHANT_ID= your merchnat id
PAYSTATION_MERCHANT_PASSWORD= your merchant password
```
### Add file in config/paystation.php
```
<?php

return [
    'merchant_id' => env('PAYSTATION_MERCHANT_ID'),
    'merchant_password' => env('PAYSTATION_MERCHANT_PASSWORD')
];
```

### Update routes/web.php file
```
<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;


Route::get('payment-process',[PaymentController::class,'processPayment'])->name('payment.process');
Route::get('payment-verify',[PaymentController::class,'verifyPayment'])->name('payment.verify');

```

### Update PaymentController.php file

```
<?php

namespace App\Http\Controllers;

use Xenon\Paystation\Paystation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function processPayment()
    {

        try {
            $config = [
                'merchantId' => config('paystation.merchant_id'),
                'password' => config('paystation.merchant_password')
            ];

            $invoice_no = rand(11111111, 99999999);

            $pay = new Paystation($config);
            $pay->setPaymentParams([
                'invoice_number' => $invoice_no,
                'currency' => "BDT",
                'payment_amount' => 1,
                'reference' => "102030",
                'cust_name' => "Nazmul",
                'cust_phone' => "01700000001",
                'cust_email' => "nazmul@gmail.com",
                'cust_address' => "Dhaka, Bangladesh",
                'callback_url' => route('payment.verify'),
                 'checkout_items' => "orderItems"
            ]);

            $pay->payNow(); //will automatically redirect to gateway payment page


        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function verifyPayment(Request $request)
    {

        if ($request->trx_id == null) {
            return redirect()->route('home')->with('error', 'Transaction Failed!');
        }

        $config = [
            'merchantId' => config('paystation.merchant_id'),
            'password' => config('paystation.merchant_password')
        ];

        $pay = new Paystation($config);

        $responseData  = $pay->verifyPayment($request->invoice_number,$request->trx_id); //this will retrieve response as json

        $response = json_decode($responseData, true);

        if ($response['status'] == 'success' && $response['status_code'] == 200) {
            //store payment transaction and order information
            echo 'store payment transaction';
            return redirect()->route('home')->with('success', 'Order Placed successfully');
        }else{
            return redirect()->route('home')->with('error', 'Transaction Failed!');
        }

    }


}

```

### sample json response for transaction verification(Success)
<pre>
    {
        "status_code": "200",
        "status": "success",
        "message": "Transaction found",
        "data": {
            "invoice_number": "ddsf648feebc415138XXXXX",
            "trx_status": "Success",
            "trx_id": "AFJ7IXXX",
            "payment_amount": 1,
            "order_date_time": "2023-06-19 11:57:04",
            "payer_mobile_no": "01750XXXX",
            "payment_method": "bKash",
            "reference": "102030",
            "checkout_items": null,
            "cust_phone": "01700000001"
        }
    }
</pre>

### sample json response for transaction verification(Failed)
<pre>
{
    "status_code": "1006",
    "status": "failed",
    "message": "Transaction not found in system"
}
</pre>


#### Important Methods
* setPaymentParams()
* payNow()
* verifyPayment()


# Integrate by using curl

## Sample code

### update routes/web.php

```
//paystation original route
Route::get('checkout',[CheckoutController::class,'checkout'])->name('checkout');
Route::get('store-transaction/{token}',[CheckoutController::class,'storeTransaction'])->name('store-transaction');

```

### update CheckoutController.php 
```
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

```

# Sample Response for above three request

### Sample response for create token

<pre>
1. SUCCESS RESPONSE (json format):
{
    "status_code":"200",
    "status":"success",
    "message":"Token generated successfully.",
    "token":"lGxWsAwfLa1oHuTAWQOhubunmZmmpVQltKJJoznC7ntrO58CeJOnDspKd1xT"
}

2. FAILED RESPONSE (json format):
{
    "status_code":"1001",
    "status":"failed",
    "message":"Invalid Credential."
}	
</pre>


### Sample response for create payment
<pre>
1. SUCCESS RESPONSE (json format):
{
    "status_code":"200",
    "status":"success",
    "message":"Payment Created Successfully.",
    "payment_url":"https://api.paystation.com.bd/checkout/121301028928655/2jWsXVEsi1waq33QEbN0MxZjm7axBnZEIkZmeHoQFdLb7tz57h"
}

2. FAILED RESPONSE (json format):
{
    "status_code":"2001",
    "status":"failed",
    "message":"Invalid Token."
}	
</pre>

### Sample response for Transaction Retrive
<pre>
1. SUCCESS RESPONSE (json format):
{
    "status_code":"200",
    "status":"success",
    "message":"Transaction found.",
    "data":
    {
       "invoice_number":"2021252525", 
       "trx_status":"Success",
       "trx_id":"10XB9900",
       "payment_amount":"120",
       "order_date_time":"2022-12-25 10:25:30",
       "payer_mobile_no":"01700000001",
       "payment_method":"bkash",
       "reference":"102030",
       "checkout_items":"orderItems",
    }
}

2. SUCCESS RESPONSE (BUT TRANSACTION FAILED):
{
    "status_code": "200",
    "status": "success",
    "message": "Transaction found",
    "data": {
        "invoice_number": "2021252525",
        "trx_status": "Failed",
        "trx_id": "",
        "payment_amount": "120.00",
        "order_date_time": "2023-01-14 11:04:42",
        "payer_mobile_no": "",
        "payment_method": "",
        "reference": "102030",
        "checkout_items": "orderItems"
    }
}

3. FAILED RESPONSE (json format):
{
    "status_code":"2001",
    "status":"failed",
    "message":"Invalid Token."
}
</pre>


