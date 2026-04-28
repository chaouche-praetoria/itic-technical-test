<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const activeSection = ref('introduction');
let observer = null;
let isScrollingManually = false;

const sections = [
    { id: 'introduction',   label: 'Introduction',             icon: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'dashboard',      label: 'Tableau de bord',          icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { id: 'candidates',     label: 'Candidats',                icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { id: 'questions',      label: 'Banque de questions',      icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'code-questions', label: 'Rédaction questions Code',  icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { id: 'templates',      label: 'Templates de test',        icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z' },
    { id: 'sessions',       label: 'Sessions & Notation',      icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
    { id: 'scoring',        label: 'Système de notation',      icon: 'M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z' },
    { id: 'levels',         label: 'Niveaux académiques',      icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { id: 'domains',        label: 'Domaines & Thèmes',        icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { id: 'hubspot',        label: 'Intégration HubSpot',      icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z' },
    { id: 'antichat',       label: 'Anti-triche',              icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
    { id: 'rbac',           label: 'Rôles & Permissions',      icon: 'M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z' },
    { id: 'api',            label: 'API REST',                 icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4' },
    { id: 'support',        label: 'Support',                  icon: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z' },
];

function scrollTo(id) {
    isScrollingManually = true;
    activeSection.value = id;
    const el = document.getElementById(id);
    if (el) {
        window.scrollTo({
            top: el.offsetTop - 120,
            behavior: 'smooth'
        });
    }
    
    // Resume scroll spy after animation
    setTimeout(() => {
        isScrollingManually = false;
    }, 800);
}

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (isScrollingManually) return;
        
        entries.forEach((entry) => {
            if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                activeSection.value = entry.target.id;
            }
        });
    }, {
        threshold: [0, 0.5, 1],
        rootMargin: '-10% 0px -80% 0px' // Focus on the top part of the viewport
    });

    sections.forEach((s) => {
        const el = document.getElementById(s.id);
        if (el) observer.observe(el);
    });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});
</script>

<template>
    <Head title="Documentation" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Documentation</h1>
        </template>

        <div class="flex gap-8 items-start">
            <!-- Sommaire latéral -->
            <aside class="hidden xl:block sticky top-28 w-64 shrink-0">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Sommaire</p>
                    </div>
                    <nav class="p-3 space-y-0.5">
                        <button
                            v-for="s in sections"
                            :key="s.id"
                            @click="scrollTo(s.id)"
                            :class="[
                                'w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-left text-xs font-semibold transition-all',
                                activeSection === s.id
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50'
                            ]"
                        >
                            <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="s.icon" />
                            </svg>
                            {{ s.label }}
                        </button>
                    </nav>
                </div>
            </aside>

            <!-- Contenu principal -->
            <div class="flex-1 min-w-0 space-y-10">

                <!-- INTRODUCTION -->
                <section id="introduction" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-6 bg-gradient-to-r from-indigo-600 to-purple-600">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="size-10 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <h2 class="text-xl font-black text-white">Bienvenue sur TestPlatform ITIC</h2>
                        </div>
                        <p class="text-indigo-100 text-sm leading-relaxed">
                            Plateforme d'évaluation technique des candidats ITIC Paris — gestion des tests, notation automatique et synchronisation HubSpot.
                        </p>
                    </div>
                    <div class="px-8 py-6 space-y-4">
                        <p class="text-slate-600 text-sm leading-relaxed">
                            TestPlatform permet à l'équipe ITIC Paris de créer des tests techniques personnalisés, de les envoyer aux candidats via un lien unique, et de récupérer automatiquement les résultats dans HubSpot CRM.
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div v-for="item in [
                                { label: 'Types de questions', value: '3', sub: 'QCM · Texte · Code' },
                                { label: 'Notation', value: 'Auto', sub: 'QCM & Code' },
                                { label: 'Anti-triche', value: 'Actif', sub: 'Logs + blocages' },
                                { label: 'Intégration', value: 'HubSpot', sub: 'Sync bi-directionnelle' },
                            ]" :key="item.label" class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                <p class="text-lg font-black text-indigo-600">{{ item.value }}</p>
                                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ item.label }}</p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ item.sub }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TABLEAU DE BORD -->
                <section id="dashboard" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Tableau de bord</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Le tableau de bord est la page d'accueil de l'interface d'administration. Il présente une vue synthétique de l'activité de la plateforme.</p>
                        <div class="space-y-2">
                            <p class="font-semibold text-slate-700">Indicateurs affichés :</p>
                            <ul class="space-y-1.5 pl-4">
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><span><strong>Total candidats</strong> — nombre de candidats enregistrés dans la plateforme.</span></li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><span><strong>Sessions complétées</strong> — tests soumis avec un score calculé.</span></li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><span><strong>En attente de correction</strong> — sessions contenant des questions à corriger manuellement.</span></li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><span><strong>Score moyen</strong> — moyenne de tous les scores finaux.</span></li>
                            </ul>
                        </div>
                        <p>Des graphiques de répartition par domaine et par niveau académique sont également disponibles, ainsi qu'un tableau des dernières sessions récentes.</p>
                    </div>
                </section>

                <!-- CANDIDATS -->
                <section id="candidates" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Gestion des candidats</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Créer un candidat manuellement</p>
                            <ol class="space-y-1.5 pl-4 list-decimal">
                                <li>Accédez à <strong>Candidats</strong> dans le menu latéral.</li>
                                <li>Cliquez sur le bouton <strong>+ Nouveau candidat</strong>.</li>
                                <li>Renseignez le prénom, le nom et l'e-mail (obligatoires).</li>
                                <li>Validez. Le candidat apparaît dans la liste.</li>
                            </ol>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Importer depuis HubSpot</p>
                            <p>Cliquez sur <strong>Synchroniser HubSpot</strong> pour importer en masse les contacts dont le statut correspond aux filtres configurés. Les candidats déjà présents (même e-mail) sont mis à jour, pas dupliqués.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Profil d'un candidat</p>
                            <p>En cliquant sur un candidat, vous accédez à son profil qui affiche :</p>
                            <ul class="space-y-1.5 pl-4 mt-2">
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span>L'historique de toutes ses sessions de test avec scores et statuts.</li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span>Les informations de formation et coordonnées.</li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span>Les boutons pour générer un lien de test ou synchroniser avec HubSpot.</li>
                            </ul>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Générer un lien de test</p>
                            <ol class="space-y-1.5 pl-4 list-decimal">
                                <li>Depuis la fiche candidat, cliquez sur <strong>Nouveau test</strong>.</li>
                                <li>Sélectionnez un template de test.</li>
                                <li>Choisissez la méthode d'invitation :
                                    <ul class="pl-4 mt-1 space-y-1 text-xs list-disc">
                                        <li><strong>HubSpot</strong> : Le lien est envoyé directement sur la fiche contact HubSpot.</li>
                                        <li><strong>Email</strong> : Une invitation est envoyée par mail via TestPlatform.</li>
                                    </ul>
                                </li>
                                <li>Validez. L'action choisie est exécutée immédiatement.</li>
                                <li>Vous pouvez renvoyer le lien ou resynchroniser HubSpot à tout moment depuis l'historique des sessions.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- BANQUE DE QUESTIONS -->
                <section id="questions" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Banque de questions</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <p>La banque de questions centralise toutes les questions disponibles pour la génération de tests. Chaque question appartient à un <strong>domaine</strong>, un <strong>thème</strong> et un <strong>niveau académique</strong>.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                                <p class="font-black text-slate-800 text-xs uppercase tracking-widest mb-2">QCM</p>
                                <p class="text-xs text-slate-500">Question à choix multiples. Une ou plusieurs bonnes réponses. Corrigée automatiquement : 100 % si toutes les bonnes réponses sont cochées, 0 % sinon.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                                <p class="font-black text-slate-800 text-xs uppercase tracking-widest mb-2">Texte libre</p>
                                <p class="text-xs text-slate-500">Réponse ouverte rédigée par le candidat. Nécessite une correction manuelle par un administrateur depuis la fiche session.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                                <p class="font-black text-slate-800 text-xs uppercase tracking-widest mb-2">Code</p>
                                <p class="text-xs text-slate-500">Exercice de programmation exécuté via Judge0. La note est calculée en fonction du pourcentage de tests unitaires passés.</p>
                            </div>
                        </div>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Niveaux de difficulté</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">Facile</span>
                                <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">Moyen</span>
                                <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold border border-rose-200">Difficile</span>
                            </div>
                        </div>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Ajouter une question</p>
                            <ol class="space-y-1.5 pl-4 list-decimal">
                                <li>Cliquez sur <strong>+ Nouvelle question</strong>.</li>
                                <li>Choisissez le type, le domaine, le thème, le niveau et la difficulté.</li>
                                <li>Pour les QCM : ajoutez les choix et cochez les bonnes réponses.</li>
                                <li>Pour le code : renseignez les tests unitaires et le langage par défaut.</li>
                                <li>Enregistrez. La question est immédiatement disponible dans les templates.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- RÉDACTION QUESTIONS CODE -->
                <section id="code-questions" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Rédaction des questions de code</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <p>Les questions de code permettent d'évaluer les capacités de programmation réelle. Le candidat doit écrire une fonction qui répond à des critères précis, validée par des tests unitaires exécutés via Judge0.</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                                <p class="font-bold text-slate-700 mb-2">1. Starter Code (Code initial)</p>
                                <p class="text-xs text-slate-500 mb-3">C'est le code que le candidat voit en ouvrant la question. Il contient généralement la signature de la fonction et des commentaires.</p>
                                <div class="bg-slate-900 rounded-xl p-3 font-mono text-[10px] text-slate-300">
                                    def addition(a, b):<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;# Écrivez votre code ici<br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;pass
                                </div>
                            </div>
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200">
                                <p class="font-bold text-slate-700 mb-2">2. Unit Tests (Tests unitaires)</p>
                                <p class="text-xs text-slate-500 mb-3">Code interne qui valide la solution. Chaque test doit lever une erreur (Exception ou Assert) si la réponse est fausse.</p>
                                <div class="bg-slate-900 rounded-xl p-3 font-mono text-[10px] text-slate-300">
                                    assert addition(1, 2) == 3<br>
                                    assert addition(-1, 1) == 0<br>
                                    assert addition(0, 0) == 0
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-6 bg-amber-50 border border-amber-100 rounded-2xl italic">
                                <p class="font-bold text-amber-800 mb-2 text-xs uppercase tracking-widest">Fonctionnement du Scoring Code</p>
                                <p class="text-xs text-amber-700 leading-relaxed">
                                    Le système sépare vos tests unitaires ligne par ligne. Chaque ligne est exécutée indépendamment. La note finale de la question est le pourcentage de lignes de test qui ont réussi sans erreur.
                                </p>
                            </div>
                            <div class="p-6 bg-indigo-50 border border-indigo-100 rounded-2xl">
                                <p class="font-bold text-indigo-800 mb-2 text-xs uppercase tracking-widest">Validation des tests</p>
                                <p class="text-xs text-indigo-700 leading-relaxed">
                                    Pour que les tests soient comptabilisés dans l'interface, utilisez <code>print("PASS")</code>. Le système compte le nombre de marqueurs <strong>PASS</strong> présents dans la console pour calculer le score final.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-8">
                            <!-- PYTHON -->
                            <div>
                                <p class="font-bold text-slate-700 mb-3 flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-blue-500"></span> Exemple complet (Python)
                                </p>
                                <div class="space-y-4">
                                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Énoncé</p>
                                        <p class="text-xs">Créez une fonction <code>is_even(n)</code> qui retourne <code>True</code> si le nombre est pair.</p>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Starter Code</p>
                                            <div class="bg-slate-900 rounded-xl p-4 font-mono text-[10px] text-blue-300">
                                                def is_even(n):<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;return
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Unit Tests</p>
                                            <div class="bg-emerald-950 rounded-xl p-4 font-mono text-[10px] text-emerald-300 border border-emerald-500/30">
                                                if is_even(2) == True: print("PASS")<br>
                                                if is_even(3) == False: print("PASS")
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- JAVASCRIPT -->
                            <div>
                                <p class="font-bold text-slate-700 mb-3 flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-yellow-400"></span> Exemple complet (JavaScript)
                                </p>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Starter Code</p>
                                            <div class="bg-slate-900 rounded-xl p-4 font-mono text-[10px] text-yellow-200">
                                                function isEven(n) {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;// Votre code<br>
                                                }
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Unit Tests</p>
                                            <div class="bg-emerald-950 rounded-xl p-4 font-mono text-[10px] text-emerald-300 border border-emerald-500/30">
                                                if (isEven(2) === true) console.log("PASS");<br>
                                                if (isEven(3) === false) console.log("PASS");
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PHP -->
                            <div>
                                <p class="font-bold text-slate-700 mb-3 flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-indigo-400"></span> Exemple complet (PHP)
                                </p>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Starter Code</p>
                                            <div class="bg-slate-900 rounded-xl p-4 font-mono text-[10px] text-indigo-300">
                                                function isEven($n) {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;// Votre code<br>
                                                }
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Unit Tests</p>
                                            <div class="bg-emerald-950 rounded-xl p-4 font-mono text-[10px] text-emerald-300 border border-emerald-500/30">
                                                if (isEven(2) === true) echo "PASS";<br>
                                                if (isEven(3) === false) echo "PASS";
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- JAVA -->
                            <div>
                                <p class="font-bold text-slate-700 mb-3 flex items-center gap-2">
                                    <span class="size-2 rounded-full bg-red-500"></span> Exemple complet (Java)
                                </p>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Starter Code</p>
                                            <div class="bg-slate-900 rounded-xl p-4 font-mono text-[10px] text-red-300">
                                                public class Solution {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;public static boolean isEven(int n) {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;// Votre code<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;}<br>
                                                }
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 pl-2">Unit Tests</p>
                                            <div class="bg-emerald-950 rounded-xl p-4 font-mono text-[10px] text-emerald-300 border border-emerald-500/30">
                                                public class Main {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;public static void main(String[] args) {<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if (Solution.isEven(2)) System.out.println("PASS");<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;if (!Solution.isEven(3)) System.out.println("PASS");<br>
                                                &nbsp;&nbsp;&nbsp;&nbsp;}<br>
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                            <p class="font-bold text-xs text-indigo-700 mb-1">Conseil d'expert</p>
                            <p class="text-xs text-indigo-600">Évitez d'utiliser des bibliothèques externes complexes dans vos tests. Restez sur des assertions simples (<code>assert</code> ou <code>throw new Error()</code>) pour garantir la compatibilité entre les langages.</p>
                        </div>
                    </div>
                </section>

                <!-- TEMPLATES -->
                <section id="templates" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Templates de test</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <p>Un template définit la <strong>structure d'un test</strong> : durée, règles de sélection des questions, options de surveillance. Chaque lien de test est généré à partir d'un template.</p>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Paramètres d'un template</p>
                            <ul class="space-y-1.5 pl-4">
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><strong>Durée</strong> — durée totale du test en minutes (minimum 5 min).</li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><strong>Minuteur par question</strong> — temps limite optionnel par question (30 s, 1 min, 2 min, 3 min, 5 min).</li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><strong>Tentative unique</strong> — si activé, le candidat ne peut passer le test qu'une seule fois.</li>
                                <li class="flex items-start gap-2"><span class="mt-1.5 size-1.5 rounded-full bg-indigo-500 shrink-0"></span><strong>Expiration du lien</strong> — délai en heures après lequel le lien devient invalide.</li>
                            </ul>
                        </div>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Règles de sélection</p>
                            <p>Chaque règle précise : <em>thème</em>, <em>type de question</em>, <em>difficulté</em> et <em>nombre de questions</em>. Le générateur pioche aléatoirement dans la banque les questions correspondantes à chaque règle.</p>
                            <div class="mt-3 bg-slate-50 rounded-xl border border-slate-200 p-4 font-mono text-xs text-slate-600">
                                Exemple : 2 QCM faciles "Python OOP" + 1 Code moyen "Algorithmes" + 1 Texte difficile "Architecture"
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SESSIONS & NOTATION -->
                <section id="sessions" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Sessions & Notation</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Cycle de vie d'une session</p>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                                <span class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600">En attente</span>
                                <span class="text-slate-300">→</span>
                                <span class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700">En cours</span>
                                <span class="text-slate-300">→</span>
                                <span class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-700">En attente de correction</span>
                                <span class="text-slate-300">→</span>
                                <span class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700">Terminée</span>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Une session reste en <em>attente de correction</em> si elle contient des questions texte libres non notées. La finalisation synchronise automatiquement le score dans HubSpot.</p>
                        </div>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Corriger une question texte</p>
                            <ol class="space-y-1.5 pl-4 list-decimal">
                                <li>Ouvrez la session depuis la fiche candidat.</li>
                                <li>Repérez les réponses texte marquées <strong>À noter</strong>.</li>
                                <li>Saisissez un score de 0 à 100 et validez.</li>
                                <li>Une fois toutes les questions notées, cliquez sur <strong>Finaliser la session</strong>.</li>
                            </ol>
                        </div>
                    </div>
                </section>

                <!-- SCORING -->
                <section id="scoring" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Système de notation</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Chaque question rapporte un nombre de points selon son type et sa difficulté. Le score final est exprimé en pourcentage (points obtenus / points possibles × 100).</p>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-2.5 text-left font-black text-slate-600 border border-slate-200">Type</th>
                                        <th class="px-4 py-2.5 text-center font-black text-slate-600 border border-slate-200">Facile</th>
                                        <th class="px-4 py-2.5 text-center font-black text-slate-600 border border-slate-200">Moyen</th>
                                        <th class="px-4 py-2.5 text-center font-black text-slate-600 border border-slate-200">Difficile</th>
                                        <th class="px-4 py-2.5 text-left font-black text-slate-600 border border-slate-200">Méthode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2.5 border border-slate-200 font-semibold text-slate-700">QCM</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">10 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">20 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">30 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-slate-500">Tout ou rien (toutes les bonnes réponses = 100 %)</td>
                                    </tr>
                                    <tr class="bg-slate-50/50">
                                        <td class="px-4 py-2.5 border border-slate-200 font-semibold text-slate-700">Texte</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">20 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">30 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">40 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-slate-500">Note manuelle de 0 à 100 %</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2.5 border border-slate-200 font-semibold text-slate-700">Code</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">20 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">40 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">60 pts</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-slate-500">% de tests unitaires passés via Judge0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex gap-4">
                            <div class="flex-1 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-xs font-black text-emerald-700 uppercase tracking-widest mb-1">Admis</p>
                                <p class="text-2xl font-black text-emerald-600">≥ 70 %</p>
                                <p class="text-xs text-emerald-600 mt-1">Candidat maintenu au niveau testé</p>
                            </div>
                            <div class="flex-1 rounded-xl border border-rose-200 bg-rose-50 p-4">
                                <p class="text-xs font-black text-rose-700 uppercase tracking-widest mb-1">Échec — Repli</p>
                                <p class="text-2xl font-black text-rose-600">&lt; 70 %</p>
                                <p class="text-xs text-rose-600 mt-1">Orientation vers le niveau inférieur configuré</p>
                            </div>
                        </div>

                        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 mt-4">
                            <p class="font-bold text-slate-800 mb-2">Orientation dynamique (Logic de repli)</p>
                            <p class="text-xs text-slate-600 leading-relaxed mb-4">
                                Pour chaque niveau académique, un "niveau de repli" peut être configuré. En cas d'échec (score < 70%), le système propose automatiquement le niveau inférieur défini.
                            </p>
                            <div class="bg-white rounded-xl p-4 border border-indigo-100 text-xs text-slate-500 font-mono italic">
                                Exemple : Bachelor (70%) -> Repli sur BTS (Orientation proposée).
                            </div>
                            <p class="text-[10px] text-slate-400 mt-4 leading-tight italic">
                                * Si aucun repli n'est configuré, l'échec est simplement noté sans proposition de réorientation.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- DOMAINES & THÈMES -->
                <section id="domains" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Domaines & Thèmes</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Les domaines et thèmes structurent la banque de questions. Chaque question et chaque template sont rattachés à un domaine.</p>
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs font-mono text-slate-600 space-y-1">
                            <p>Domaine : <span class="text-indigo-600 font-bold">Python</span></p>
                            <p class="pl-4">└── Thème : <span class="text-purple-600 font-bold">Programmation orientée objet</span></p>
                            <p class="pl-4">└── Thème : <span class="text-purple-600 font-bold">Gestion des exceptions</span></p>
                            <p class="pl-4">└── Thème : <span class="text-purple-600 font-bold">Algorithmes & structures de données</span></p>
                        </div>
                        <p>Accédez à <strong>Domaines</strong> dans le menu pour créer, modifier ou désactiver des domaines. Les thèmes s'ajoutent directement depuis la fiche du domaine concerné.</p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-700">
                            <strong>Attention :</strong> la suppression d'un domaine est bloquée s'il contient des questions ou est utilisé dans des templates actifs.
                        </div>
                    </div>
                </section>

                <!-- NIVEAUX ACADÉMIQUES -->
                <section id="levels" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Niveaux académiques</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <p>Les niveaux académiques (ex : BTS, Bachelor, Mastère) permettent de segmenter les questions par difficulté pédagogique et de gérer les parcours d'orientation.</p>

                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6">
                            <p class="font-bold text-slate-700 mb-3 text-xs uppercase tracking-widest">Configuration du Repli (Fallback)</p>
                            <p class="text-xs mb-4">
                                Pour chaque niveau, vous pouvez définir un <strong>niveau de repli</strong>. C'est ce niveau qui sera proposé au candidat s'il n'atteint pas le score minimal (70%) lors de son test.
                            </p>
                            
                            <div class="space-y-3">
                                <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100">
                                    <div class="size-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">1</div>
                                    <p class="text-xs">Allez dans <strong>Niveaux</strong> dans le menu latéral.</p>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100">
                                    <div class="size-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">2</div>
                                    <p class="text-xs">Modifiez un niveau existant.</p>
                                </div>
                                <div class="flex items-center gap-3 p-3 bg-white rounded-xl border border-slate-100">
                                    <div class="size-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-[10px] font-bold">3</div>
                                    <p class="text-xs">Sélectionnez le <strong>Niveau de repli</strong> dans la liste déroulante.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <p class="font-black text-xs text-indigo-600 uppercase tracking-widest mb-2">Usage : Questions</p>
                                <p class="text-xs text-slate-500">Chaque question doit être rattachée à un niveau académique pour garantir la cohérence pédagogique du test généré.</p>
                            </div>
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                                <p class="font-black text-xs text-indigo-600 uppercase tracking-widest mb-2">Usage : Scoring</p>
                                <p class="text-xs text-slate-500">Le système calcule la réussite par rapport au niveau testé et utilise le repli pour suggérer une orientation alternative dans HubSpot.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- HUBSPOT -->
                <section id="hubspot" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-orange-50 flex items-center justify-center">
                            <svg class="size-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Intégration HubSpot</h2>
                    </div>
                    <div class="px-8 py-6 space-y-6 text-sm text-slate-600 leading-relaxed">
                        <p>La plateforme est connectée à HubSpot CRM pour synchroniser les données candidats dans les deux sens.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                                <p class="font-black text-slate-800 text-xs uppercase tracking-widest mb-2">← Pull (import)</p>
                                <p class="text-xs text-slate-500">Importe les contacts HubSpot vers la plateforme. Lance depuis le bouton <strong>Synchroniser HubSpot</strong> sur la liste des candidats ou depuis une fiche candidat individuelle.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4 bg-slate-50">
                                <p class="font-black text-slate-800 text-xs uppercase tracking-widest mb-2">→ Push (export)</p>
                                <p class="text-xs text-slate-500">Met à jour le contact HubSpot avec le score, le résultat (Admis/Échec) et la date du test. Déclenché automatiquement à la finalisation ou manuellement depuis la fiche candidat.</p>
                            </div>
                        </div>

                        <div>
                            <p class="font-semibold text-slate-700 mb-2">Propriétés HubSpot synchronisées</p>
                            <div class="bg-slate-900 rounded-xl p-4 text-xs font-mono text-slate-300 space-y-1">
                                <p><span class="text-indigo-400">score_test_technique</span>      <span class="text-slate-500">→ Score en % (ex: 85)</span></p>
                                <p><span class="text-indigo-400">resultat_test_technique</span>   <span class="text-slate-500">→ "Admis" ou "Echec - Repli"</span></p>
                                <p><span class="text-indigo-400">orientation_proposee</span>      <span class="text-slate-500">→ Nom du niveau de repli si échec</span></p>
                                <p><span class="text-indigo-400">lien_test_technique</span>       <span class="text-slate-500">→ URL unique pour passer le test</span></p>
                                <p><span class="text-indigo-400">date_test_technique</span>       <span class="text-slate-500">→ Date de soumission (ISO 8601)</span></p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ANTI-TRICHE -->
                <section id="antichat" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Dispositif anti-triche</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Plusieurs mécanismes sont actifs pendant la passation d'un test pour limiter les comportements frauduleux.</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <svg class="size-4 text-indigo-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                <div>
                                    <p class="font-semibold text-slate-700">Copier-coller bloqué</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Les raccourcis Ctrl+C, Ctrl+V et le clic droit sont désactivés sur toutes les pages de test.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <svg class="size-4 text-amber-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <div>
                                    <p class="font-semibold text-slate-700">Changement d'onglet détecté</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Chaque fois que le candidat quitte la page (changement d'onglet, minimisation), l'événement est enregistré avec un horodatage.</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <svg class="size-4 text-emerald-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                                <div>
                                    <p class="font-semibold text-slate-700">Journal d'activité consultable</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Tous les événements sont visibles dans la fiche session sous forme de journal horodaté.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- RBAC -->
                <section id="rbac" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Rôles & Permissions</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>L'accès aux fonctionnalités est contrôlé par un système de rôles et permissions. Seul un <strong>super-admin</strong> peut gérer les rôles et les administrateurs.</p>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th class="px-4 py-2.5 text-left font-black text-slate-600 border border-slate-200">Permission</th>
                                        <th class="px-4 py-2.5 text-center font-black text-slate-600 border border-slate-200">Super-admin</th>
                                        <th class="px-4 py-2.5 text-center font-black text-slate-600 border border-slate-200">Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(row, i) in [
                                        ['Gérer les administrateurs', true, false],
                                        ['Gérer les rôles', true, false],
                                        ['Gérer les candidats', true, true],
                                        ['Gérer les questions', true, true],
                                        ['Gérer les templates', true, true],
                                        ['Consulter les résultats', true, true],
                                    ]" :key="i" :class="i % 2 === 1 ? 'bg-slate-50/50' : ''">
                                        <td class="px-4 py-2.5 border border-slate-200 font-medium text-slate-700">{{ row[0] }}</td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">
                                            <svg v-if="row[1]" class="size-4 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </td>
                                        <td class="px-4 py-2.5 border border-slate-200 text-center">
                                            <svg v-if="row[2]" class="size-4 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <svg v-else class="size-4 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-slate-500">Le rôle <strong>super-admin</strong> est protégé : il ne peut être ni modifié ni supprimé. Un super-admin ne peut pas se supprimer lui-même.</p>
                    </div>
                </section>

                <!-- API -->
                <section id="api" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">API REST</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Une API REST est disponible pour intégrer la plateforme avec des systèmes tiers. Toutes les requêtes nécessitent un token Sanctum valide.</p>

                        <div class="space-y-3">
                            <div v-for="ep in [
                                { method: 'POST', path: '/api/v1/candidates', desc: 'Créer un candidat par API' },
                                { method: 'POST', path: '/api/v1/generate-link', desc: 'Générer un lien de test' },
                                { method: 'GET',  path: '/api/v1/results/{session}', desc: 'Récupérer les résultats d\'une session' },
                            ]" :key="ep.path" class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <span :class="[
                                    'px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest shrink-0',
                                    ep.method === 'GET' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'
                                ]">{{ ep.method }}</span>
                                <span class="font-mono text-xs text-slate-700">{{ ep.path }}</span>
                                <span class="text-xs text-slate-400 ml-auto">{{ ep.desc }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SUPPORT -->
                <section id="support" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-5 border-b border-slate-100 flex items-center gap-3">
                        <div class="size-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                            <svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <h2 class="text-base font-black text-slate-800">Support</h2>
                    </div>
                    <div class="px-8 py-6 space-y-4 text-sm text-slate-600 leading-relaxed">
                        <p>Pour toute question technique ou signalement d'anomalie, contactez l'équipe support ITIC Paris.</p>
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5 flex items-center gap-4">
                            <div class="size-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
                                <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-indigo-700">support@iticparis.com</p>
                                <p class="text-xs text-indigo-500 mt-0.5">Réponse sous 24 h ouvrées</p>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
