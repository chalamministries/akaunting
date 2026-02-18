<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/fluidPay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::portal('fluidPay', function (): void {
    Route::middleware('fluidpay.csp')->group(function (): void {
        Route::get('invoices/{invoice}', 'Payment@show')->name('invoices.show');
        Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
    });
});
