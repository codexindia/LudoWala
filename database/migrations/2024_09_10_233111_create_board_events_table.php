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
        Schema::create('board_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('userId');
            $table->integer('playerId');
            $table->string('roomId');
            $table->enum('tokenId',['A1','A2','A3','A4','B1','B2','B3','B4','C1','C2','C3','C4','D1','D2','D3','D4']);
            $table->integer('travelCount');
            $table->integer('position');
            $table->enum('isSafe',['0','1'])->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_events');
    }
};
