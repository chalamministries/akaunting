<?php

namespace Modules\Stripe\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    protected $table = 'stripe_customers';

    protected $appends = ['cards'];

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'customer_id',
        'stripe_customer_id',
        'stripe_card_id',
        'brand',
        'country',
        'exp_month',
        'exp_year',
        'funding',
        'card_number',
        'card_contact_name'
    ];

    public function getCardsAttribute()
    {
        return $this->brand . '(' . $this->card_number . ')';
    }

    public function scopeCustomerByCards(Builder $query, int $customer_id)
    {
        return $query->where('customer_id', $customer_id)->get();
    }
}
