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
        Schema::create('room_details', function (Blueprint $table) {
            $table->id();
            $table->string('roomId');
            $table->integer('playerId');
            $table->enum('roomType', ['tournament', 'classic'])->default('tournament');
            $table->unsignedBigInteger('userId');
            $table->integer(column: 'currentTurn')->default(0);
            $table->enum('createdBy', ['system', 'user'])->default('system');
            $table->unsignedBigInteger('creationId')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_detailes');
    }
};
