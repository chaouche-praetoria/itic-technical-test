<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\TestTemplate;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class TestTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $info = Domain::where('slug', 'informatique-numerique')->first();
        $mkt  = Domain::where('slug', 'marketing-communication')->first();
        $rh   = Domain::where('slug', 'ressources-humaines')->first();
        $com  = Domain::where('slug', 'commerce-management')->first();
        $cpt  = Domain::where('slug', 'comptabilite-gestion')->first();

        $bts      = AcademicLevel::where('slug', 'bts')->first();
        $bachelor = AcademicLevel::where('slug', 'bachelor')->first();
        $master   = AcademicLevel::where('slug', 'mastere')->first();

        $filières = [
            [
                'name' => 'BTS SIO (SLAM)',
                'description' => 'Test d\'admission pour le BTS Services Informatiques aux Organisations - Option SLAM',
                'domain_id' => $info->id,
                'academic_level_id' => $bts->id,
                'duration_minutes' => 30,
                'rules' => [
                    ['theme' => 'programmation-php-js-python', 'count' => 10],
                    ['theme' => 'base-de-donnees-sql-nosql', 'count' => 5],
                ]
            ],
            [
                'name' => 'Bachelor Développeur Web',
                'description' => 'Test d\'admission pour le Bachelor Développeur Web & Mobile',
                'domain_id' => $info->id,
                'academic_level_id' => $bachelor->id,
                'duration_minutes' => 45,
                'rules' => [
                    ['theme' => 'programmation-php-js-python', 'count' => 15],
                    ['theme' => 'cybersecurite', 'count' => 5],
                ]
            ],
            [
                'name' => 'Mastère Cybersécurité',
                'description' => 'Test d\'admission pour le Mastère Expert en Cybersécurité',
                'domain_id' => $info->id,
                'academic_level_id' => $master->id,
                'duration_minutes' => 60,
                'rules' => [
                    ['theme' => 'cybersecurite', 'count' => 20],
                    ['theme' => 'reseaux-systemes', 'count' => 10],
                ]
            ],
            [
                'name' => 'Bachelor Ressources Humaines',
                'description' => 'Test d\'admission pour le Bachelor Chargé des RH',
                'domain_id' => $rh->id,
                'academic_level_id' => $bachelor->id,
                'duration_minutes' => 45,
                'rules' => [
                    ['theme' => 'droit-du-travail', 'count' => 10],
                    ['theme' => 'recrutement-sourcing', 'count' => 10],
                ]
            ],
            [
                'name' => 'BTS MCO',
                'description' => 'Test d\'admission pour le BTS Management Commercial Opérationnel',
                'domain_id' => $com->id,
                'academic_level_id' => $bts->id,
                'duration_minutes' => 30,
                'rules' => [
                    ['theme' => 'negociation-commerciale', 'count' => 10],
                    ['theme' => 'relation-client-crm', 'count' => 5],
                ]
            ],
        ];

        foreach ($filières as $filièreData) {
            $rules = $filièreData['rules'];
            unset($filièreData['rules']);

            $template = TestTemplate::firstOrCreate(
                ['name' => $filièreData['name']],
                $filièreData
            );

            foreach ($rules as $ruleData) {
                $theme = Theme::where('slug', $ruleData['theme'])->first();
                if ($theme) {
                    $template->rules()->create([
                        'theme_id' => $theme->id,
                        'count' => $ruleData['count'],
                        'question_type' => 'mcq', // Par défaut
                    ]);
                }
            }
        }
    }
}
