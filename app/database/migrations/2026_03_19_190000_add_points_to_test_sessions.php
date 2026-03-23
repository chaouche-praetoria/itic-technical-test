<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_sessions', function (Blueprint $table) {
            $table->integer('points_earned')->nullable()->after('score');
            $table->integer('points_total')->nullable()->after('points_earned');
        });
    }

    public function down(): void
    {
        Schema::table('test_sessions', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_total']);
        });
    }
};
