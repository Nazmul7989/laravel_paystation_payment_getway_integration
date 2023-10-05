<?php

namespace App\Http\Controllers;

use Xenon\Paystation\Exception\PaystationPaymentParameterException;
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

        if ($request->invoice_number == null && $request->trx_id == null) {
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
            //store payment transaction
            echo 'store payment transaction';
        }else{
            return redirect()->route('home')->with('error', 'Transaction Failed!');
        }


    }


}
