<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StripeV218 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_registered_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->integer('invoice_id');
            $table->string('stripe_customer_id');
            $table->string('stripe_card_id');

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('stripe_registered_transactions');
    }
}
