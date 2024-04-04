<?php
// Replace 'IpayugController' with your actual controller name

// Routes for Views
Route::group(['middleware' => ['auth']], function () {
    Route::get('/ipayug', 'IpayugController@index');
    Route::post('/ipayug/payment_ipayug_return', 'IpayugController@paymentIpayugReturn');
    Route::get('/ipayug/payment_ipayug_notify', 'IpayugController@paymentIpayugNotify');

});
