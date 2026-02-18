<?php

namespace Modules\FluidPay\Http\Controllers;

use App\Http\Requests\Portal\InvoiceShow as InvoiceShowRequest;
use App\Models\Document\Document;
use Illuminate\Http\JsonResponse;
use Modules\FluidPay\Http\Controllers\Portal\InvoiceController;

class Payment extends InvoiceController
{
    public function show(InvoiceShowRequest $request, Document $invoice): JsonResponse
    {
        return parent::show($request, $invoice);
    }

    public function confirm(InvoiceShowRequest $request, Document $invoice): JsonResponse
    {
        return parent::pay($request, $invoice);
    }
}
