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
        Schema::create('daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('completed_tasks')->default(0);
            $table->integer('pending_tasks')->default(0);
            $table->integer('overdue_tasks')->default(0);
            $table->integer('study_minutes')->default(0);
            $table->integer('ai_interactions')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->integer('stress_level')->default(5);
            $table->integer('motivation_level')->default(5);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('user_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_statistics');
    }
};
