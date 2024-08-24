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
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('entryFee');
            $table->integer('maxPlayers');
            $table->integer('totalRound');
            $table->integer('roundInterval');
            $table->integer('currentRound')->default(0);
            $table->dateTime('registrationStartTime')->useCurrent();
            $table->dateTime('registrationEndTime');
            $table->dateTime('startTime');
            $table->dateTime('nextRoundTime');
            $table->enum('status',['live','upcoming','complete'])->default('live');
            $table->string('1stPrize');
            $table->string('2ndPrize');
            $table->string('3rdPrize');
            $table->string('4thPrize');
            //$table->boolean('is_active')->default(true);
            $table->integer('1stRoundBonus')->default(0);
            $table->integer('2ndRoundWinning')->default(0);
            $table->integer('3rdRoundWinning')->default(0);
            $table->integer('4thRoundWinning')->default(0);
            $table->integer('5thRoundWinning')->default(0);
            $table->integer('6thRoundWinning')->default(0);
            $table->integer('7thRoundWinning')->default(0);
            $table->integer('8thRoundWinning')->default(0);
            $table->integer('9thRoundWinning')->default(0);

          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
