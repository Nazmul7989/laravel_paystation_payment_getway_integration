<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('checkout',[PaymentController::class,'checkout'])->name('checkout');
Route::get('verify-payment',[PaymentController::class,'verify'])->name('payment.verify');
