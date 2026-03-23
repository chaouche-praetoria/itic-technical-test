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
            'default_language'  => $data['language'] ?? 'javascript',
            'unit_tests'        => $data['unit_tests'] ?? null,
        ]);
    }

    // -------------------------------------------------------------------------

    public function run(): void
    {
        $dev  = Domain::where('slug', 'developpement')->first();
        $net  = Domain::where('slug', 'reseau-et-systemes')->first();
        $rh   = Domain::where('slug', 'ressources-humaines')->first();
        $mkt  = Domain::where('slug', 'marketing')->first();
        $com  = Domain::where('slug', 'communication')->first();

        $bts      = AcademicLevel::where('slug', 'bts')->first();
        $bachelor = AcademicLevel::where('slug', 'bachelor')->first();
        $master   = AcademicLevel::where('slug', 'master')->first();

        if (!$dev || !$bts) return;

        // =====================================================================
        // DÉVELOPPEMENT — JavaScript
        // =====================================================================
        if ($js = $this->theme($dev, 'javascript')) {
            $this->mcq([
                'statement'  => 'Quelle est la différence entre `let` et `var` en JavaScript ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['`let` a une portée de bloc, `var` a une portée de fonction', true],
                    ['`let` et `var` sont identiques', false],
                    ['`var` est plus récent que `let`', false],
                    ['`let` ne peut pas être réassigné', false],
                ],
            ], $dev, $js, $bts);

            $this->mcq([
                'statement'  => 'Que retourne `typeof null` en JavaScript ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['"null"', false],
                    ['"object"', true],
                    ['"undefined"', false],
                    ['"boolean"', false],
                ],
            ], $dev, $js, $bts);

            $this->mcq([
                'statement'  => 'Lesquels sont des méthodes natives des tableaux JavaScript ?',
                'difficulty' => 'easy',
                'multiple_answers' => true,
                'choices'    => [
                    ['map()', true],
                    ['filter()', true],
                    ['reduce()', true],
                    ['compute()', false],
                ],
            ], $dev, $js, $bts);

            $this->mcq([
                'statement'  => 'Qu\'est-ce qu\'une Promise en JavaScript ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['Un objet représentant la complétion éventuelle d\'une opération asynchrone', true],
                    ['Une fonction synchrone bloquante', false],
                    ['Un type de boucle spéciale', false],
                    ['Une méthode de gestion d\'erreurs uniquement', false],
                ],
            ], $dev, $js, $bachelor);

            $this->mcq([
                'statement'  => 'Quelle est la sortie de `console.log(0.1 + 0.2 === 0.3)` ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['true', false],
                    ['false', true],
                    ['undefined', false],
                    ['NaN', false],
                ],
            ], $dev, $js, $bachelor);

            $this->text([
                'statement'  => 'Expliquez ce qu\'est une closure en JavaScript et donnez un cas d\'usage concret.',
                'difficulty' => 'medium',
            ], $dev, $js, $bachelor);

            $this->code([
                'statement'  => "Écrivez une fonction `palindrome(str)` qui retourne `true` si la chaîne est un palindrome, `false` sinon.\n\nExemples :\n- `palindrome('racecar')` → true\n- `palindrome('hello')` → false",
                'difficulty' => 'medium',
                'language'   => 'javascript',
                'unit_tests' => <<<'JS'
const assert = (c, m) => console.log(c ? 'PASS' : 'FAIL', m);
assert(palindrome('racecar') === true,  'racecar est un palindrome');
assert(palindrome('hello')   === false, 'hello n\'est pas un palindrome');
assert(palindrome('level')   === true,  'level est un palindrome');
assert(palindrome('a')       === true,  'un seul caractère');
JS,
            ], $dev, $js, $bachelor);
        }

        // =====================================================================
        // DÉVELOPPEMENT — PHP
        // =====================================================================
        if ($php = $this->theme($dev, 'php')) {
            $this->mcq([
                'statement'  => 'Quelle est la différence entre `include` et `require` en PHP ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['`require` génère une erreur fatale si le fichier est absent, `include` génère un avertissement', true],
                    ['Il n\'y a aucune différence', false],
                    ['`include` est plus rapide que `require`', false],
                    ['`require` ne peut être utilisé qu\'une seule fois', false],
                ],
            ], $dev, $php, $bts);

            $this->mcq([
                'statement'  => 'Laquelle de ces fonctions PHP permet d\'empêcher les injections SQL ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['mysql_real_escape_string()', false],
                    ['htmlspecialchars()', false],
                    ['PDO avec requêtes préparées', true],
                    ['strip_tags()', false],
                ],
            ], $dev, $php, $bts);

            $this->mcq([
                'statement'  => 'En PHP, quelle superglobale contient les données envoyées par un formulaire en méthode POST ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['$_GET', false],
                    ['$_POST', true],
                    ['$_REQUEST', false],
                    ['$_FORM', false],
                ],
            ], $dev, $php, $bts);

            $this->text([
                'statement'  => 'Décrivez le pattern MVC (Model-View-Controller) et expliquez le rôle de chaque composant dans une application PHP.',
                'difficulty' => 'medium',
            ], $dev, $php, $bachelor);

            $this->code([
                'statement'  => "Écrivez une fonction PHP `factorielle(\$n)` qui retourne la factorielle d'un entier positif.\n\nExemples :\n- `factorielle(5)` → 120\n- `factorielle(0)` → 1",
                'difficulty' => 'medium',
                'language'   => 'php',
                'unit_tests' => <<<'PHP'
$assert = fn($c, $m) => print(($c ? 'PASS' : 'FAIL') . " $m\n");
$assert(factorielle(0) === 1,   'factorielle(0) === 1');
$assert(factorielle(1) === 1,   'factorielle(1) === 1');
$assert(factorielle(5) === 120, 'factorielle(5) === 120');
$assert(factorielle(6) === 720, 'factorielle(6) === 720');
PHP,
            ], $dev, $php, $bachelor);
        }

        // =====================================================================
        // DÉVELOPPEMENT — Python
        // =====================================================================
        if ($py = $this->theme($dev, 'python')) {
            $this->mcq([
                'statement'  => 'Quelle est la différence entre une liste et un tuple en Python ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Les listes sont mutables, les tuples sont immuables', true],
                    ['Les tuples peuvent contenir plus d\'éléments', false],
                    ['Les listes ne peuvent contenir que des entiers', false],
                    ['Il n\'y a aucune différence pratique', false],
                ],
            ], $dev, $py, $bts);

            $this->mcq([
                'statement'  => 'Que produit cette list comprehension : `[x**2 for x in range(4)]` ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['[0, 1, 4, 9]', true],
                    ['[1, 4, 9, 16]', false],
                    ['[0, 1, 2, 3]', false],
                    ['[1, 2, 3, 4]', false],
                ],
            ], $dev, $py, $bts);

            $this->mcq([
                'statement'  => 'Quel décorateur Python permet de définir une méthode de classe ne recevant pas l\'instance comme premier argument ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['@classmethod', false],
                    ['@staticmethod', true],
                    ['@property', false],
                    ['@abstractmethod', false],
                ],
            ], $dev, $py, $bachelor);

            $this->code([
                'statement'  => "Écrivez une fonction `fibonacci(n)` qui retourne le n-ième terme de la suite de Fibonacci.\n\nExemples :\n- `fibonacci(0)` → 0\n- `fibonacci(1)` → 1\n- `fibonacci(7)` → 13",
                'difficulty' => 'medium',
                'language'   => 'python',
                'unit_tests' => <<<'PY'
def assert_eq(a, b, msg):
    print(('PASS' if a == b else 'FAIL'), msg)
assert_eq(fibonacci(0), 0,  'fibonacci(0) == 0')
assert_eq(fibonacci(1), 1,  'fibonacci(1) == 1')
assert_eq(fibonacci(6), 8,  'fibonacci(6) == 8')
assert_eq(fibonacci(7), 13, 'fibonacci(7) == 13')
PY,
            ], $dev, $py, $bachelor);
        }

        // =====================================================================
        // DÉVELOPPEMENT — Algorithmes
        // =====================================================================
        if ($algo = $this->theme($dev, 'algorithmes')) {
            $this->mcq([
                'statement'  => 'Quelle est la complexité temporelle d\'un algorithme de tri par insertion dans le pire cas ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['O(n)', false],
                    ['O(n log n)', false],
                    ['O(n²)', true],
                    ['O(log n)', false],
                ],
            ], $dev, $algo, $bachelor);

            $this->mcq([
                'statement'  => 'Dans une recherche binaire, combien de comparaisons maximum faut-il pour trouver un élément dans un tableau trié de 1024 éléments ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['10', true],
                    ['512', false],
                    ['1024', false],
                    ['32', false],
                ],
            ], $dev, $algo, $bachelor);

            $this->text([
                'statement'  => 'Expliquez la différence entre un algorithme récursif et un algorithme itératif. Quand préférer l\'un à l\'autre ?',
                'difficulty' => 'medium',
            ], $dev, $algo, $bachelor);

            $this->mcq([
                'statement'  => 'Quelle structure de données utilise le principe LIFO (Last In, First Out) ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['File (Queue)', false],
                    ['Pile (Stack)', true],
                    ['Liste chaînée', false],
                    ['Arbre binaire', false],
                ],
            ], $dev, $algo, $bts);
        }

        // =====================================================================
        // DÉVELOPPEMENT — Base de données
        // =====================================================================
        if ($db = $this->theme($dev, 'base-de-donnees')) {
            $this->mcq([
                'statement'  => 'Quelle clause SQL permet de filtrer des résultats groupés par `GROUP BY` ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['WHERE', false],
                    ['HAVING', true],
                    ['FILTER', false],
                    ['ORDER BY', false],
                ],
            ], $dev, $db, $bts);

            $this->mcq([
                'statement'  => 'Quelle est la différence entre `INNER JOIN` et `LEFT JOIN` ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['`INNER JOIN` retourne toutes les lignes des deux tables, `LEFT JOIN` seulement la table gauche', false],
                    ['`INNER JOIN` retourne seulement les lignes avec correspondance des deux côtés, `LEFT JOIN` retourne toutes les lignes de la table gauche', true],
                    ['Il n\'y a aucune différence', false],
                    ['`LEFT JOIN` est plus performant qu\'`INNER JOIN`', false],
                ],
            ], $dev, $db, $bts);

            $this->mcq([
                'statement'  => 'Que signifie ACID dans le contexte des bases de données relationnelles ?',
                'difficulty' => 'medium',
                'multiple_answers' => true,
                'choices'    => [
                    ['Atomicité', true],
                    ['Cohérence (Consistency)', true],
                    ['Isolation', true],
                    ['Durabilité', true],
                ],
            ], $dev, $db, $bachelor);

            $this->text([
                'statement'  => 'Expliquez ce qu\'est la normalisation d\'une base de données. Décrivez les trois premières formes normales (1NF, 2NF, 3NF).',
                'difficulty' => 'hard',
            ], $dev, $db, $master);
        }

        // =====================================================================
        // RÉSEAU ET SYSTÈMES — Linux
        // =====================================================================
        if ($net && ($linux = $this->theme($net, 'linux'))) {
            $this->mcq([
                'statement'  => 'Quelle commande Linux permet d\'afficher les processus en cours d\'exécution ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['ls', false],
                    ['ps', true],
                    ['top', false],
                    ['kill', false],
                ],
            ], $net, $linux, $bts);

            $this->mcq([
                'statement'  => 'Que signifient les permissions `755` sur un fichier Linux ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Lecture/Écriture/Exécution pour tous', false],
                    ['Propriétaire : rwx, Groupe : r-x, Autres : r-x', true],
                    ['Propriétaire : rw-, Groupe : r-x, Autres : r--', false],
                    ['Lecture seule pour tous', false],
                ],
            ], $net, $linux, $bts);

            $this->mcq([
                'statement'  => 'Quelle commande permet de rechercher du texte dans des fichiers sous Linux ?',
                'difficulty' => 'easy',
                'multiple_answers' => true,
                'choices'    => [
                    ['grep', true],
                    ['find', false],
                    ['awk', true],
                    ['sed', false],
                ],
            ], $net, $linux, $bachelor);

            $this->text([
                'statement'  => 'Expliquez l\'utilité du protocole SSH. Comment sécuriser un accès SSH à un serveur en production ?',
                'difficulty' => 'medium',
            ], $net, $linux, $bachelor);
        }

        // =====================================================================
        // RÉSEAU ET SYSTÈMES — TCP/IP
        // =====================================================================
        if ($net && ($tcpip = $this->theme($net, 'tcp-ip'))) {
            $this->mcq([
                'statement'  => 'Combien de couches comporte le modèle OSI ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['4', false],
                    ['5', false],
                    ['7', true],
                    ['9', false],
                ],
            ], $net, $tcpip, $bts);

            $this->mcq([
                'statement'  => 'Quelle est la principale différence entre TCP et UDP ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['TCP est plus rapide qu\'UDP', false],
                    ['TCP garantit la livraison et l\'ordre des paquets, UDP ne le fait pas', true],
                    ['UDP utilise plus de bande passante que TCP', false],
                    ['TCP ne peut pas transmettre de vidéo', false],
                ],
            ], $net, $tcpip, $bts);

            $this->mcq([
                'statement'  => 'Sur quel port le protocole HTTPS fonctionne-t-il par défaut ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['80', false],
                    ['8080', false],
                    ['443', true],
                    ['22', false],
                ],
            ], $net, $tcpip, $bts);
        }

        // =====================================================================
        // RÉSEAU ET SYSTÈMES — Sécurité
        // =====================================================================
        if ($net && ($secu = $this->theme($net, 'securite'))) {
            $this->mcq([
                'statement'  => 'Qu\'est-ce qu\'une attaque XSS (Cross-Site Scripting) ?',
                'difficulty' => 'medium',
                'choices'    => [
                    ['Une attaque qui intercepte les paquets réseau', false],
                    ['Une injection de scripts malveillants dans des pages web vues par d\'autres utilisateurs', true],
                    ['Une attaque par force brute sur des mots de passe', false],
                    ['Un déni de service distribué', false],
                ],
            ], $net, $secu, $bachelor);

            $this->text([
                'statement'  => 'Expliquez le fonctionnement du protocole HTTPS. Quelles sont les étapes de l\'établissement d\'une connexion TLS/SSL ?',
                'difficulty' => 'hard',
            ], $net, $secu, $master);
        }

        // =====================================================================
        // RESSOURCES HUMAINES — Droit du travail
        // =====================================================================
        if ($rh && ($droit = $this->theme($rh, 'droit-du-travail'))) {
            $this->mcq([
                'statement'  => 'Quelle est la durée légale du travail hebdomadaire en France ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['39 heures', false],
                    ['35 heures', true],
                    ['40 heures', false],
                    ['32 heures', false],
                ],
            ], $rh, $droit, $bts);

            $this->mcq([
                'statement'  => 'Combien de jours ouvrables de congés payés un salarié acquiert-il par mois de travail effectif en France ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['1,5 jours', false],
                    ['2 jours', false],
                    ['2,5 jours', true],
                    ['3 jours', false],
                ],
            ], $rh, $droit, $bts);

            $this->mcq([
                'statement'  => 'Lesquels de ces éléments sont des motifs valables de licenciement pour motif personnel ?',
                'difficulty' => 'medium',
                'multiple_answers' => true,
                'choices'    => [
                    ['Faute grave', true],
                    ['Inaptitude médicale constatée par le médecin du travail', true],
                    ['Grossesse', false],
                    ['Insuffisance professionnelle', true],
                ],
            ], $rh, $droit, $bachelor);

            $this->text([
                'statement'  => 'Décrivez les étapes obligatoires d\'une procédure de licenciement pour motif personnel en France.',
                'difficulty' => 'medium',
            ], $rh, $droit, $bachelor);
        }

        // =====================================================================
        // RESSOURCES HUMAINES — Recrutement
        // =====================================================================
        if ($rh && ($recru = $this->theme($rh, 'recrutement'))) {
            $this->mcq([
                'statement'  => 'Quelle méthode de recrutement consiste à identifier des candidats passifs via leurs profils en ligne ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Sourcing', true],
                    ['Onboarding', false],
                    ['Assessment center', false],
                    ['Cooptation', false],
                ],
            ], $rh, $recru, $bts);

            $this->text([
                'statement'  => 'Quelles sont les étapes clés d\'un processus de recrutement efficace ? Citez au moins 5 étapes et expliquez brièvement chacune.',
                'difficulty' => 'medium',
            ], $rh, $recru, $bachelor);
        }

        // =====================================================================
        // MARKETING — Marketing digital
        // =====================================================================
        if ($mkt && ($mktdig = $this->theme($mkt, 'marketing-digital'))) {
            $this->mcq([
                'statement'  => 'Que mesure le taux de conversion (conversion rate) dans une stratégie e-commerce ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Le pourcentage de visiteurs qui effectuent un achat', true],
                    ['Le nombre total de visiteurs du site', false],
                    ['Le chiffre d\'affaires par visiteur', false],
                    ['Le taux d\'abandon de panier', false],
                ],
            ], $mkt, $mktdig, $bts);

            $this->mcq([
                'statement'  => 'Laquelle de ces métriques mesure le coût pour acquérir un nouveau client ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['CTR', false],
                    ['CAC (Coût d\'Acquisition Client)', true],
                    ['LTV (Lifetime Value)', false],
                    ['CPC (Coût Par Clic)', false],
                ],
            ], $mkt, $mktdig, $bts);

            $this->mcq([
                'statement'  => 'Lesquels de ces leviers font partie du marketing digital ?',
                'difficulty' => 'easy',
                'multiple_answers' => true,
                'choices'    => [
                    ['SEO (référencement naturel)', true],
                    ['Email marketing', true],
                    ['Publicité TV', false],
                    ['Social Media Marketing', true],
                ],
            ], $mkt, $mktdig, $bts);

            $this->text([
                'statement'  => 'Présentez les éléments constitutifs d\'une stratégie de marketing digital pour le lancement d\'un nouveau produit.',
                'difficulty' => 'medium',
            ], $mkt, $mktdig, $bachelor);
        }

        // =====================================================================
        // MARKETING — SEO
        // =====================================================================
        if ($mkt && ($seo = $this->theme($mkt, 'seo'))) {
            $this->mcq([
                'statement'  => 'Quelle balise HTML est la plus importante pour le référencement naturel d\'une page ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['<meta description>', false],
                    ['<title>', true],
                    ['<h2>', false],
                    ['<alt>', false],
                ],
            ], $mkt, $seo, $bts);

            $this->mcq([
                'statement'  => 'Lesquels sont des facteurs de référencement "off-page" ?',
                'difficulty' => 'medium',
                'multiple_answers' => true,
                'choices'    => [
                    ['Backlinks provenant de sites autoritaires', true],
                    ['Mentions de la marque sur d\'autres sites', true],
                    ['Vitesse de chargement de la page', false],
                    ['Balises title et meta description', false],
                ],
            ], $mkt, $seo, $bachelor);
        }

        // =====================================================================
        // COMMUNICATION — Communication écrite
        // =====================================================================
        if ($com && ($comecrit = $this->theme($com, 'communication-ecrite'))) {
            $this->mcq([
                'statement'  => 'Quelle est la structure recommandée pour rédiger un email professionnel efficace ?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['Objet clair → Formule d\'appel → Corps du message → Formule de politesse → Signature', true],
                    ['Signature → Corps du message → Objet', false],
                    ['Corps du message sans formule d\'appel', false],
                    ['Objet long et détaillé → Message court', false],
                ],
            ], $com, $comecrit, $bts);

            $this->text([
                'statement'  => 'Rédigez une note de service informant les employés d\'un changement d\'horaire : à partir du 1er du mois prochain, les locaux ouvriront à 8h30 au lieu de 9h.',
                'difficulty' => 'easy',
            ], $com, $comecrit, $bts);
        }

        // =====================================================================
        // COMMUNICATION — Anglais
        // =====================================================================
        if ($com && ($en = $this->theme($com, 'anglais'))) {
            $this->mcq([
                'statement'  => 'Which sentence is grammatically correct?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['She don\'t know the answer.', false],
                    ['She doesn\'t know the answer.', true],
                    ['She not know the answer.', false],
                    ['She not knowing the answer.', false],
                ],
            ], $com, $en, $bts);

            $this->mcq([
                'statement'  => 'What does the acronym "ASAP" stand for in business communication?',
                'difficulty' => 'easy',
                'choices'    => [
                    ['As Soon As Possible', true],
                    ['As Stated And Prepared', false],
                    ['Always Send A Proposal', false],
                    ['Automated System And Process', false],
                ],
            ], $com, $en, $bts);

            $this->text([
                'statement'  => 'Write a short professional email (8-10 sentences) to a client to schedule a meeting for next week to present your new product.',
                'difficulty' => 'medium',
            ], $com, $en, $bachelor);
        }
    }
}
