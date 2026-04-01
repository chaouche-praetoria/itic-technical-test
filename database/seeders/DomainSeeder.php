<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DomainSeeder extends Seeder
{
    public function run(): void
    {
        // Academic Levels
        $levels = [
            ['name' => 'BTS', 'slug' => 'bts', 'order' => 1],
            ['name' => 'Bachelor', 'slug' => 'bachelor', 'order' => 2],
            ['name' => 'Mastère', 'slug' => 'mastere', 'order' => 3],
        ];

        foreach ($levels as $level) {
            AcademicLevel::firstOrCreate(['slug' => $level['slug']], $level);
        }

        // Domains and Themes
        $domains = [
            [
                'name' => 'Informatique & Numérique',
                'color' => '#3B82F6',
                'themes' => [
                    'Programmation (PHP, JS, Python)',
                    'Réseaux & Systèmes',
                    'Cybersécurité',
                    'Cloud (AWS, Azure)',
                    'Base de données (SQL, NoSQL)',
                    'Intelligence Artificielle'
                ],
            ],
            [
                'name' => 'Marketing & Communication',
                'color' => '#EC4899',
                'themes' => [
                    'Marketing Digital',
                    'SEO & SEM',
                    'Réseaux Sociaux',
                    'Communication Interne & Externe',
                    'Événementiel'
                ],
            ],
            [
                'name' => 'Ressources Humaines',
                'color' => '#F59E0B',
                'themes' => [
                    'Droit du travail',
                    'Gestion de la Paie',
                    'Recrutement & Sourcing',
                    'GPEC',
                    'Relations Sociales'
                ],
            ],
            [
                'name' => 'Commerce & Management',
                'color' => '#10B981',
                'themes' => [
                    'Négociation Commerciale',
                    'Management d\'équipe',
                    'Stratégie d\'entreprise',
                    'Relation Client (CRM)',
                    'Entrepreneuriat'
                ],
            ],
            [
                'name' => 'Comptabilité & Gestion',
                'color' => '#8B5CF6',
                'themes' => [
                    'Comptabilité Générale',
                    'Contrôle de Gestion',
                    'Finance d\'entreprise',
                    'Fiscalité',
                    'Audit & Conseil'
                ],
            ],
        ];

        foreach ($domains as $domainData) {
            $themes = $domainData['themes'];
            unset($domainData['themes']);

            $domain = Domain::firstOrCreate(
                ['slug' => Str::slug($domainData['name'])],
                [...$domainData, 'slug' => Str::slug($domainData['name'])]
            );

            foreach ($themes as $themeName) {
                Theme::firstOrCreate(
                    ['slug' => Str::slug($themeName), 'domain_id' => $domain->id],
                    ['name' => $themeName, 'slug' => Str::slug($themeName), 'domain_id' => $domain->id]
                );
            }
        }
    }
}
