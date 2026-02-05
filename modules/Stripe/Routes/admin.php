<?php

use Illuminate\Support\Facades\Route;

/**
 * 'admin' middleware and 'stripe' prefix applied to all routes (including names)
 *
 * @see \App\Providers\Route::register
 */

Route::admin('stripe', function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', 'Settings@edit')->name('edit');
        Route::post('/', 'Settings@update')->name('update');
    });

    Route::group(['prefix' => 'sync', 'as' => 'sync.'], function () {
        Route::get('count', 'Sync@count')->name('count');
        Route::post('send/{id}', 'Sync@send')->name('send');
    });
});
