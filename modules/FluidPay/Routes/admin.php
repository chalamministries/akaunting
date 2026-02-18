<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'fluidPay' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('fluidPay', function (): void {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function (): void {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('/', 'Settings@update')->name('update');
    });
});
