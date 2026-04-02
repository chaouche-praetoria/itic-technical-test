<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we can use a raw statement to update the ENUM
        DB::statement("ALTER TABLE test_sessions MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'expired', 'pending_review') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        // Note: Any 'pending_review' sessions will be lost or cause error if not handled
        DB::statement("ALTER TABLE test_sessions MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'expired') NOT NULL DEFAULT 'pending'");
    }
};
