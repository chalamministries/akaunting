<?php

namespace Modules\Stripe\Database\Seeds;

use App\Abstracts\Model;
use App\Jobs\Common\CreateItem;
use App\Traits\Jobs;
use Illuminate\Database\Seeder;

class Items extends Seeder
{
    use Jobs;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->create();

        Model::reguard();
    }

    private function create()
    {
        $this->dispatch(new CreateItem([
            'company_id'        => $this->command->argument('company'),
            'name'              => trans('stripe::general.stripe_item'),
            'sale_price'        => 0,
            'purchase_price'    => 0,
            'enabled'           => true,
        ]));
    }
}