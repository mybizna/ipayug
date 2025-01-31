<?php
// Replace 'IpayugController' with your actual controller name

use Illuminate\Support\Facades\Route;
use Modules\Ipayug\Http\Controllers\IpayugController;

// Routes for Views
Route::group(['middleware' => ['auth']], function () {
    Route::get('/ipayug', [IpayugController::class, 'index']);
    Route::post('/ipayug/payment_ipayug_return', [IpayugController::class, 'paymentIpayugReturn']);
    Route::get('/ipayug/payment_ipayug_notify', [IpayugController::class, 'paymentIpayugNotify']);

});
