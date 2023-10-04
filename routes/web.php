<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
})->name('home');

//xenon paystation package route
Route::get('payment-process',[PaymentController::class,'processPayment'])->name('payment.process');
Route::get('payment-verify',[PaymentController::class,'verifyPayment'])->name('payment.verify');

//paystation original route
Route::get('checkout',[CheckoutController::class,'checkout'])->name('checkout');
Route::get('store-transaction/{token}',[CheckoutController::class,'storeTransaction'])->name('store-transaction');
Route::get('payment-success',[CheckoutController::class,'paymentSuccess'])->name('payment-success');
