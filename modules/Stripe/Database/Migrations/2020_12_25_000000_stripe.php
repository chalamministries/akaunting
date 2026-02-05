<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Stripe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->integer('customer_id');
            $table->string('stripe_customer_id');
            $table->string('stripe_card_id');
            $table->integer('card_number');
            $table->string('card_contact_name');
            $table->string('brand');
            $table->string('country');
            $table->integer('exp_month');
            $table->integer('exp_year');
            $table->string('funding');

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
        Schema::drop('stripe_customers');
    }
}
