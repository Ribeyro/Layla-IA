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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->enum('type', ['study', 'organization', 'wellbeing', 'academic'])->default('academic');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->boolean('viewed')->default(false);
            $table->boolean('applied')->default(false);
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('viewed');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
