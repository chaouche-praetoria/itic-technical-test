<?php

namespace App\Imports;

use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Question;
use App\Models\Theme;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class QuestionsImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Resolve Academic Level
            $levelName = trim($row['niveau'] ?? '');
            $level = AcademicLevel::where('name', 'like', $levelName)->first() 
                  ?? AcademicLevel::where('slug', Str::slug($levelName))->first()
                  ?? AcademicLevel::first(); // Default to first if not found

            // Resolve Domain
            $domainName = trim($row['domaine'] ?? '');
            $domain = Domain::firstOrCreate(
                ['slug' => Str::slug($domainName)],
                ['name' => $domainName, 'slug' => Str::slug($domainName), 'is_active' => true]
            );

            // Create Question
            $question = Question::create([
                'type' => 'mcq',
                'academic_level_id' => $level->id,
                'difficulty' => strtolower(trim($row['difficulte'] ?? 'medium')),
                'statement' => $row['enonce'],
                'points' => $row['points'] ?? 1,
                'explanation' => $row['explication'] ?? null,
                'multiple_answers' => (bool) ($row['reponse_multiple'] ?? false),
                'is_active' => true,
            ]);

            // Sync Domain
            $question->domains()->sync([$domain->id]);

            // Resolve and Sync Themes
            $themesString = $row['themes'] ?? '';
            if (!empty($themesString)) {
                $themeNames = array_map('trim', explode(',', $themesString));
                $themeIds = [];
                foreach ($themeNames as $tName) {
                    $theme = Theme::firstOrCreate(
                        ['slug' => Str::slug($tName)],
                        ['name' => $tName, 'slug' => Str::slug($tName)]
                    );

                    // Ensure theme is associated with the domain
                    if (!$theme->domains()->where('domains.id', $domain->id)->exists()) {
                        $theme->domains()->attach($domain->id);
                    }

                    $themeIds[] = $theme->id;
                }
                $question->themes()->sync($themeIds);
            }

            // Create Choices
            for ($i = 1; $i <= 6; $i++) {
                $choiceText = $row["choix_$i"] ?? null;
                if (!empty($choiceText)) {
                    $isCorrect = (bool) ($row["correct_$i"] ?? false);
                    $question->choices()->create([
                        'text' => $choiceText,
                        'is_correct' => $isCorrect,
                        'order' => $i - 1,
                    ]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'enonce' => 'required|string',
            'domaine' => 'required|string',
            'niveau' => 'required|string',
            'difficulte' => 'nullable|in:easy,medium,hard,facile,intermediaire,avance',
            'points' => 'nullable|integer',
            'choix_1' => 'required|string',
            'correct_1' => 'nullable',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Handle French difficulty labels
        if (isset($data['difficulte'])) {
            $diff = strtolower(trim($data['difficulte']));
            $map = [
                'facile' => 'easy',
                'intermédiaire' => 'medium',
                'intermediaire' => 'medium',
                'moyen' => 'medium',
                'avancé' => 'hard',
                'avance' => 'hard',
                'difficile' => 'hard',
            ];
            $data['difficulte'] = $map[$diff] ?? $diff;
        }

        // Cast text fields to string to avoid validation errors with numeric values from Excel
        $textFields = ['enonce', 'domaine', 'niveau', 'explication'];
        foreach ($textFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = (string) $data[$field];
            }
        }

        // Cast choice fields to string
        for ($i = 1; $i <= 6; $i++) {
            if (isset($data["choix_$i"])) {
                $data["choix_$i"] = (string) $data["choix_$i"];
            }
        }

        return $data;
    }
}
