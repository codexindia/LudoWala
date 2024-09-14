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
        Schema::create('dice_rollings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->integer(column: 'diceValue')->default(1);
            $table->string(column: 'roomId');
            $table->integer(column: 'playerId');
            $table->string(column: 'gameType')->default('tournament');
            $table->integer(column: 'currentTurn');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dice_rollings');
    }
};
