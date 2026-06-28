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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->string('nickname');
            $table->string('session_token')->index();
            $table->unsignedInteger('total_score')->default(0);
            $table->timestamps();

            $table->unique(['game_session_id', 'nickname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
