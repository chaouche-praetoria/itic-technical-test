<?php

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\Domain;
use App\Models\Question;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    private function theme(Domain $domain, string $slug): ?Theme
    {
        return Theme::where('domain_id', $domain->id)->where('slug', $slug)->first();
    }

    private function mcq(array $data, Domain $domain, Theme $theme, AcademicLevel $level): void
    {
        if (Question::where('statement', $data['statement'])->exists()) {
            return;
        }
        $q = Question::create([
            'type'               => 'mcq',
            'domain_id'          => $domain->id,
            'theme_id'           => $theme->id,
            'academic_level_id'  => $level->id,
            'difficulty'         => $data['difficulty'],
            'statement'          => $data['statement'],
            'multiple_answers'   => $data['multiple_answers'] ?? false,
        ]);
        foreach ($data['choices'] as $i => $choice) {
            $q->choices()->create(['text' => $choice[0], 'is_correct' => $choice[1], 'order' => $i]);
        }
    }

    private function text(array $data, Domain $domain, Theme $theme, AcademicLevel $level): void
    {
        if (Question::where('statement', $data['statement'])->exists()) {
            return;
        }
        Question::create([
            'type'              => 'text',
            'domain_id'         => $domain->id,
            'theme_id'          => $theme->id,
            'academic_level_id' => $level->id,
            'difficulty'        => $data['difficulty'],
            'statement'         => $data['statement'],
        ]);
    }

    private function code(array $data, Domain $domain, Theme $theme, AcademicLevel $level): void
    {
        if (Question::where('statement', $data['statement'])->exists()) {
            return;
        }
        Question::create([
            'type'              => 'code',
            'domain_id'         => $domain->id,
            'theme_id'          => $theme->id,
            'academic_level_id' => $level->id,
            'difficulty'        => $data['difficulty'],
            'statement'         => $data['statement'],
            'unit_tests'        => json_encode($data['unit_tests']),
            'default_language'  => $data['default_language'] ?? 'javascript',
        ]);
    }

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

        if (!$info || !$bts) return;

        // =====================================================================
        // INFORMATIQUE & NUMÉRIQUE
        // =====================================================================
        if ($prog = $this->theme($info, 'programmation-php-js-python')) {
            $this->mcq([
                'statement'  => 'En PHP 8+, quelle est l\'utilité de l\'opérateur nullsafe `?->` ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['Il permet d\'appeler une méthode ou d\'accéder à une propriété sur un objet potentiellement nul sans générer d\'erreur', true],
                    ['Il permet de comparer deux valeurs pour voir si elles sont nulles', false],
                    ['C\'est un raccourci pour l\'opérateur ternaire', false],
                    ['Il sert à définir une variable nullable', false],
                ],
            ], $info, $prog, $bachelor);

            $this->mcq([
                'statement'  => 'En JavaScript, quelle est la différence majeure entre `forEach` et `map` ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['`map` retourne un nouveau tableau, `forEach` retourne `undefined`', true],
                    ['`forEach` est plus rapide que `map`', false],
                    ['`map` ne peut pas modifier les éléments originaux', false],
                    ['`forEach` ne peut être utilisé que sur des objets', false],
                ],
            ], $info, $prog, $bts);

            // Real Code Questions
            $this->code([
                'statement'  => 'Écrivez une fonction `fizzbuzz(n)` qui retourne "Fizz" si n est divisible par 3, "Buzz" si n est divisible par 5, et "FizzBuzz" si n est divisible par les deux.',
                'difficulty' => 'medium',
                'default_language' => 'javascript',
                'unit_tests' => [
                    'javascript' => "console.log(fizzbuzz(3) === 'Fizz' ? 'PASS' : 'FAIL');\nconsole.log(fizzbuzz(5) === 'Buzz' ? 'PASS' : 'FAIL');\nconsole.log(fizzbuzz(15) === 'FizzBuzz' ? 'PASS' : 'FAIL');\nconsole.log(fizzbuzz(2) === 2 || fizzbuzz(2) === undefined ? 'PASS' : 'FAIL');",
                    'python' => "print('PASS' if fizzbuzz(3) == 'Fizz' else 'FAIL')\nprint('PASS' if fizzbuzz(5) == 'Buzz' else 'FAIL')\nprint('PASS' if fizzbuzz(15) == 'FizzBuzz' else 'FAIL')",
                    'php' => "echo fizzbuzz(3) === 'Fizz' ? 'PASS' : 'FAIL';\necho fizzbuzz(5) === 'Buzz' ? 'PASS' : 'FAIL';\necho fizzbuzz(15) === 'FizzBuzz' ? 'PASS' : 'FAIL';",
                    'java' => "public class Main { public static void main(String[] args) {\n Solution s = new Solution();\n System.out.println(s.fizzbuzz(3).equals(\"Fizz\") ? \"PASS\" : \"FAIL\");\n System.out.println(s.fizzbuzz(5).equals(\"Buzz\") ? \"PASS\" : \"FAIL\");\n System.out.println(s.fizzbuzz(15).equals(\"FizzBuzz\") ? \"PASS\" : \"FAIL\");\n}}"
                ]
            ], $info, $prog, $bachelor);

            $this->code([
                'statement'  => 'Créez une fonction `sumArray(arr)` qui calcule la somme de tous les nombres d\'un tableau.',
                'difficulty' => 'easy',
                'default_language' => 'javascript',
                'unit_tests' => [
                    'javascript' => "console.log(sumArray([1, 2, 3]) === 6 ? 'PASS' : 'FAIL');\nconsole.log(sumArray([]) === 0 ? 'PASS' : 'FAIL');\nconsole.log(sumArray([-1, 1]) === 0 ? 'PASS' : 'FAIL');",
                    'python' => "print('PASS' if sumArray([1, 2, 3]) == 6 else 'FAIL')\nprint('PASS' if sumArray([]) == 0 else 'FAIL')",
                    'php' => "echo sumArray([1, 2, 3]) === 6 ? 'PASS' : 'FAIL';\necho sumArray([]) === 0 ? 'PASS' : 'FAIL';",
                ]
            ], $info, $prog, $bts);
        }

        if ($cyber = $this->theme($info, 'cybersecurite')) {
            $this->mcq([
                'statement'  => 'Qu\'est-ce que le "Phishing" (Hameçonnage) ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Une technique visant à obtenir des informations confidentielles en se faisant passer pour un tiers de confiance', true],
                    ['Une attaque par déni de service', false],
                    ['Un virus qui crypte les données de l\'utilisateur', false],
                    ['Une faille matérielle dans les processeurs', false],
                ],
            ], $info, $cyber, $bts);

            $this->mcq([
                'statement'  => 'Dans le cadre de la cryptographie asymétrique, à quoi sert la clé publique ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['À chiffrer un message que seul le détenteur de la clé privée pourra déchiffrer', true],
                    ['À déchiffrer tous les messages reçus', false],
                    ['À signer numériquement un document pour prouver son identité', false],
                    ['À générer des mots de passe aléatoires', false],
                ],
            ], $info, $cyber, $bachelor);
        }

        // =====================================================================
        // RESSOURCES HUMAINES
        // =====================================================================
        if ($rh && ($droit = $this->theme($rh, 'droit-du-travail'))) {
            $this->mcq([
                'statement'  => 'Quelle est la durée maximale d\'une période d\'essai pour un cadre (renouvellement compris) en France ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['4 mois', false],
                    ['6 mois', false],
                    ['8 mois', true],
                    ['12 mois', false],
                ],
            ], $rh, $droit, $bachelor);

            $this->mcq([
                'statement'  => 'Qu\'est-ce que le CSE (Comité Social et Économique) ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['L\'instance unique de représentation du personnel dans l\'entreprise', true],
                    ['Un syndicat externe à l\'entreprise', false],
                    ['Un organisme de formation professionnelle', false],
                    ['La mutuelle de l\'entreprise', false],
                ],
            ], $rh, $droit, $bts);
        }

        // =====================================================================
        // MARKETING & COMMUNICATION
        // =====================================================================
        if ($mkt && ($mktdig = $this->theme($mkt, 'marketing-digital'))) {
            $this->mcq([
                'statement'  => 'Que signifie l\'acronyme ROI en marketing ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Return On Investment (Retour sur Investissement)', true],
                    ['Rate Of Interest', false],
                    ['Regions Of Interest', false],
                    ['Return On Information', false],
                ],
            ], $mkt, $mktdig, $bts);

            $this->mcq([
                'statement'  => 'En SEO, qu\'est-ce qu\'un "backlink" ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Un lien hypertexte pointant vers une page web depuis un autre site', true],
                    ['Un lien interne vers une autre page du même site', false],
                    ['Un lien mort (erreur 404)', false],
                    ['Un mot-clé caché dans le code source', false],
                ],
            ], $mkt, $mktdig, $bts);
        }

        // =====================================================================
        // COMMERCE & MANAGEMENT
        // =====================================================================
        if ($com && ($strat = $this->theme($com, 'strategie-dentreprise'))) {
            $this->mcq([
                'statement'  => 'Que permet d\'analyser la matrice SWOT ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Forces, Faiblesses, Opportunités, Menaces', true],
                    ['Ventes, Coûts, Profits, Pertes', false],
                    ['Produit, Prix, Place, Promotion', false],
                    ['Segmentation, Ciblage, Positionnement', false],
                ],
            ], $com, $strat, $bts);
        }

        // =====================================================================
        // COMPTABILITÉ & GESTION
        // =====================================================================
        if ($cpt && ($cptgen = $this->theme($cpt, 'comptabilite-generale'))) {
            $this->mcq([
                'statement'  => 'Dans quel document comptable figurent les actifs et les passifs d\'une entreprise à une date donnée ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Le Bilan', true],
                    ['Le Compte de Résultat', false],
                    ['Le Grand Livre', false],
                    ['Le Journal', false],
                ],
            ], $cpt, $cptgen, $bts);
        }
    }
}
