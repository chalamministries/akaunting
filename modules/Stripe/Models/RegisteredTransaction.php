<?php

namespace Modules\Stripe\Models;

use App\Abstracts\Model;

class RegisteredTransaction extends Model
{
    protected $table = 'stripe_registered_transactions';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'invoice_id',
        'stripe_customer_id',
        'stripe_card_id',
    ];
}
