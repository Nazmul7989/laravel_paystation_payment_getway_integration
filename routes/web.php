<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});


Route::post('payment-process',[PaymentController::class,'process'])->name('payment.process');
