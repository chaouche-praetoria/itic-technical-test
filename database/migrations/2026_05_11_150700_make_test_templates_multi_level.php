<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_level_test_template', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_template_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Migrate existing data
        $templates = DB::table('test_templates')->select('id', 'academic_level_id')->get();
        foreach ($templates as $template) {
            if ($template->academic_level_id) {
                DB::table('academic_level_test_template')->insert([
                    'test_template_id' => $template->id,
                    'academic_level_id' => $template->academic_level_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('test_templates', function (Blueprint $table) {
            $table->dropForeign(['academic_level_id']);
            $table->dropColumn('academic_level_id');
        });
    }

    public function down(): void
    {
        Schema::table('test_templates', function (Blueprint $table) {
            $table->foreignId('academic_level_id')->nullable()->after('domain_id')->constrained()->cascadeOnDelete();
        });

        $links = DB::table('academic_level_test_template')->select('test_template_id', 'academic_level_id')->get()->groupBy('test_template_id');
        foreach ($links as $templateId => $items) {
            DB::table('test_templates')->where('id', $templateId)->update(['academic_level_id' => $items->first()->academic_level_id]);
        }

        Schema::dropIfExists('academic_level_test_template');
    }
};
