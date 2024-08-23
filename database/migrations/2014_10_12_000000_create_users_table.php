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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('refBy')->nullable();
            $table->string('referCode')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('mobileNumber');
            $table->decimal('deposit_wallet',10,2)->default(0);
            //$table->decimal('cashback_wallet',10,2)->default(0);
            $table->decimal('bonus_wallet',10,2)->default(0);
            $table->decimal('winning_wallet',10,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
