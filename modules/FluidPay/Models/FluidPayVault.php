<?php

namespace Modules\FluidPay\Models;

use App\Abstracts\Model;
use Illuminate\Database\Eloquent\Builder;

class FluidPayVault extends Model
{
    protected $table = 'fluidpay_vaults';

    protected $fillable = [
        'company_id',
        'customer_id',
        'fluidpay_customer_id',
        'payment_method_id',
        'payment_method_type',
        'card_brand',
        'masked_number',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function scopeForCustomer(Builder $query, int $companyId, int $customerId): Builder
    {
        return $query->where('company_id', $companyId)
            ->where('customer_id', $customerId);
    }

    public function scopeDefaultForCustomer(Builder $query, int $companyId, int $customerId): Builder
    {
        return $this->scopeForCustomer($query, $companyId, $customerId)->where('is_default', true);
    }
}
