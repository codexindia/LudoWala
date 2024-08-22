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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('userId');
            $table->decimal('amount',10,2);
            $table->decimal('charge',10,2)->default(0.00);
            $table->enum('trxType',['+','-']);
            $table->string('trx');
            $table->string('description')->nullable();
            $table->string('remark')->nullable();
            $table->enum('walletType',['deposit_wallet']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
