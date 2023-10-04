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
            $redirect_url = url('/');

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
                'callback_url' => "{$redirect_url}",
                // 'checkout_items' => "orderItems"
            ]);

            $pay->payNow(); //will automatically redirect to gateway payment page

            $status  = $pay->verifyPayment($invoice_no,"trx_id"); //this will retrieve response as json

            dd($status);

        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }


}
