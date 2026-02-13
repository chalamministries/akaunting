<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluidpay_vaults', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('customer_id');
            $table->string('fluidpay_customer_id');
            $table->string('payment_method_id');
            $table->string('payment_method_type');
            $table->string('card_brand')->nullable();
            $table->string('masked_number')->nullable();
            $table->string('exp_month')->nullable();
            $table->string('exp_year')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'customer_id']);
            $table->unique(['company_id', 'customer_id', 'payment_method_id'], 'fluidpay_vaults_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fluidpay_vaults');
    }
};
