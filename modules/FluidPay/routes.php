<?php

use Illuminate\Support\Facades\Route;
use Modules\FluidPay\Controllers\Portal\InvoiceController as PortalInvoiceController;
use Modules\FluidPay\Controllers\SettingsController;

Route::admin('fluidpay', function (): void {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function (): void {
        Route::get('/', [SettingsController::class, 'edit'])
            ->name('edit');

        Route::post('/', [SettingsController::class, 'update'])
            ->name('update');
    });
});

Route::portal('fluidpay', function (): void {
    Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])
        ->name('invoices.show');

    Route::post('invoices/{invoice}', [PortalInvoiceController::class, 'pay'])
        ->name('invoices.pay');
});

Route::signed('fluidpay', function (): void {
    Route::get('invoices/{invoice}', [PortalInvoiceController::class, 'show'])
        ->name('invoices.show');

    Route::post('invoices/{invoice}', [PortalInvoiceController::class, 'pay'])
        ->name('invoices.pay');
});
