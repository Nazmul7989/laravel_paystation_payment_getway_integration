<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});


Route::get('payment-process',[PaymentController::class,'processPayment'])->name('payment.process');
