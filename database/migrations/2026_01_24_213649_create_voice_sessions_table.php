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
        Schema::create('voice_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_datetime')->useCurrent();
            $table->timestamp('end_datetime')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('command_count')->default(0);
            $table->boolean('successful')->default(true);
            $table->text('transcription')->nullable();
            $table->text('ai_response')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('start_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_sessions');
    }
};
