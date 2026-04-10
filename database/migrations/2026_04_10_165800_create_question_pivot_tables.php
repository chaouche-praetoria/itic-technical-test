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
        // 1. Create domain_question pivot table
        Schema::create('domain_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 2. Create question_theme pivot table
        Schema::create('question_theme', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // 3. Migrate existing data
        $questions = DB::table('questions')->select('id', 'domain_id', 'theme_id')->get();
        foreach ($questions as $question) {
            if ($question->domain_id) {
                DB::table('domain_question')->insert([
                    'domain_id' => $question->domain_id,
                    'question_id' => $question->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if ($question->theme_id) {
                DB::table('question_theme')->insert([
                    'theme_id' => $question->theme_id,
                    'question_id' => $question->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 4. Remove old columns
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['domain_id']);
            $table->dropColumn('domain_id');
            $table->dropForeign(['theme_id']);
            $table->dropColumn('theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Re-add columns as nullable first to avoid errors if there's data
            $table->foreignId('domain_id')->after('type')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->after('academic_level_id')->nullable()->constrained()->cascadeOnDelete();
        });

        // Try to restore data (take the first found association)
        $domainQuestions = DB::table('domain_question')->select('question_id', 'domain_id')->get()->groupBy('question_id');
        foreach ($domainQuestions as $questionId => $links) {
            DB::table('questions')->where('id', $questionId)->update(['domain_id' => $links->first()->domain_id]);
        }

        $questionThemes = DB::table('question_theme')->select('question_id', 'theme_id')->get()->groupBy('question_id');
        foreach ($questionThemes as $questionId => $links) {
            DB::table('questions')->where('id', $questionId)->update(['theme_id' => $links->first()->theme_id]);
        }

        Schema::dropIfExists('domain_question');
        Schema::dropIfExists('question_theme');
    }
};
