<?php

use Illuminate\Support\Facades\Route;

/**
 * 'portal' middleware and 'portal/stripe' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::signed('stripe', function () {
    Route::get('invoices/{invoice}', 'Payment@signed')->name('invoices.show');
    Route::post('invoices/{invoice}/confirm', 'Payment@confirm')->name('invoices.confirm');
    Route::get('invoices/{invoice}/return', 'Payment@return')->name('invoices.return');
    Route::get('invoices/{invoice}/cancel', 'Payment@cancel')->name('invoices.cancel');
});
