<?php

use Illuminate\Support\Facades\Route;

/**
 * 'signed' middleware and 'signed/fluidPay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('fluidPay', function (): void {
    Route::middleware('fluidpay.csp')->group(function (): void {
        Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
        Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
    });
});
