<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_orders', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            // $table->string('product_id')->nullable();
            // $table->enum('product_type',['coins','subscription']);
            //   $table->string('session_id')->nullable();
            $table->string('order_id');
            $table->string('amount');
            $table->string('description')->nullable();
            $table->enum('type', ['payment', 'subscription']);
            // $table->string('subscription_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('webhookResp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_orders');
    }
};
