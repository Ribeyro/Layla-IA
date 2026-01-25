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
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name', 100)->nullable()->after('name');
            $table->date('birth_date')->nullable()->after('email_verified_at');
            $table->string('university', 200)->nullable()->after('birth_date');
            $table->string('career', 200)->nullable()->after('university');
            $table->integer('cycle')->nullable()->after('career');
            $table->timestamp('last_connection')->nullable()->after('cycle');
            $table->boolean('active')->default(true)->after('last_connection');

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropColumn([
                'last_name',
                'birth_date',
                'university',
                'career',
                'cycle',
                'last_connection',
                'active'
            ]);
        });
    }
};
