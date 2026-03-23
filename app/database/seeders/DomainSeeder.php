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
            ['name' => 'Master', 'slug' => 'master', 'order' => 3],
        ];

        foreach ($levels as $level) {
            AcademicLevel::firstOrCreate(['slug' => $level['slug']], $level);
        }

        // Domains and Themes
        $domains = [
            [
                'name' => 'Développement',
                'color' => '#3B82F6',
                'themes' => ['JavaScript', 'PHP', 'Python', 'Algorithmes', 'Base de données', 'React', 'Vue.js'],
            ],
            [
                'name' => 'Réseau et Systèmes',
                'color' => '#10B981',
                'themes' => ['Linux', 'TCP/IP', 'Sécurité', 'Administration réseau', 'Cloud'],
            ],
            [
                'name' => 'Ressources Humaines',
                'color' => '#F59E0B',
                'themes' => ['Droit du travail', 'Recrutement', 'Formation', 'Gestion de paie'],
            ],
            [
                'name' => 'Marketing',
                'color' => '#EC4899',
                'themes' => ['Marketing digital', 'SEO', 'Réseaux sociaux', 'Analytics', 'E-commerce'],
            ],
            [
                'name' => 'Communication',
                'color' => '#8B5CF6',
                'themes' => ['Communication écrite', 'Communication orale', 'Relations presse', 'Anglais'],
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
