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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('hubspot_id')->unique()->nullable()->after('id');
            $table->string('formation_souhaitee')->nullable()->after('phone');
            $table->string('formation_souhaitee_pour_ypareo')->nullable()->after('formation_souhaitee');
            $table->string('score_test_entretien')->nullable()->after('formation_souhaitee_pour_ypareo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn([
                'hubspot_id',
                'formation_souhaitee',
                'formation_souhaitee_pour_ypareo',
                'score_test_entretien'
            ]);
        });
    }
};
