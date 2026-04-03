<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('hubspot_id')->unique()->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();

            // Academic & HubSpot
            $table->string('formation_souhaitee')->nullable();
            $table->string('formation_souhaitee_pour_ypareo')->nullable();

            // technical Test
            $table->string('score_test_technique')->nullable();
            $table->string('resultat_test_technique')->nullable();
            $table->string('date_test_technique')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
