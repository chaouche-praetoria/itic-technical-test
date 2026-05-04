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
        // 1. Create domain_theme pivot table
        Schema::create('domain_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Migrate existing data from themes table
        $themes = DB::table('themes')->select('id', 'domain_id')->get();
        foreach ($themes as $theme) {
            if ($theme->domain_id) {
                DB::table('domain_theme')->insert([
                    'domain_id' => $theme->domain_id,
                    'theme_id' => $theme->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Remove old domain_id column from themes table
        Schema::table('themes', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropColumn('domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->foreignId('domain_id')->after('slug')->nullable()->constrained()->cascadeOnDelete();
        });

        // Restore domain_id (take the first one found in pivot table)
        $domainThemes = DB::table('domain_theme')->select('theme_id', 'domain_id')->get()->groupBy('theme_id');
        foreach ($domainThemes as $themeId => $links) {
            DB::table('themes')->where('id', $themeId)->update(['domain_id' => $links->first()->domain_id]);
        }

        Schema::dropIfExists('domain_theme');
    }
};
