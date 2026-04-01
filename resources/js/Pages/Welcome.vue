<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Bienvenue - TestPlatform" />
    
    <div class="min-h-screen bg-slate-50 selection:bg-indigo-100 selection:text-indigo-700">
        <!-- Premium Navbar -->
        <nav class="fixed top-0 w-full z-50 bg-white/70 backdrop-blur-xl border-b border-slate-200/50">
            <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
                <div class="flex items-center gap-2 group cursor-pointer">
                    <div class="size-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">TestPlatform</span>
                </div>

                <div v-if="canLogin" class="flex items-center gap-4">
                    <Link v-if="$page.props.auth.user" :href="route('admin.dashboard')" class="btn-primary">
                        Tableau de bord
                    </Link>
                    <template v-else>
                        <Link :href="route('login')" class="btn-secondary">
                            Se connecter
                        </Link>
                        <Link v-if="canRegister" :href="route('register')" class="btn-primary">
                            Commencer gratuitement
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-40 pb-20 overflow-hidden">
            <!-- Background Decorative Elements -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[1200px] h-[600px] bg-indigo-100/30 rounded-full blur-3xl pointer-events-none -z-10"></div>
            <div class="absolute -top-20 -right-20 size-96 bg-purple-100/40 rounded-full blur-3xl pointer-events-none -z-10"></div>

            <div class="max-w-5xl mx-auto px-6 text-center animate-reveal">
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 text-indigo-600 text-sm font-bold mb-8 border border-indigo-100/50">
                    <span class="relative flex size-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full size-2 bg-indigo-500"></span>
                    </span>
                    Propulsé par l'excellence technique
                </span>
                
                <h1 class="text-6xl md:text-7xl font-extrabold text-slate-900 mb-8 tracking-tight leading-[1.1]">
                    Évaluez vos talents avec <br/>
                    <span class="gradient-text">précision et élégance</span>
                </h1>
                
                <p class="text-xl text-slate-600 mb-12 max-w-2xl mx-auto leading-relaxed font-medium">
                    La plateforme ultime pour créer, gérer et évaluer des tests techniques. <br class="hidden md:block"/>
                    Simplifiez votre processus de recrutement dès aujourd'hui.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link :href="route('register')" class="btn-primary px-8 py-4 text-lg">
                        Créer un compte
                    </Link>
                    <button class="btn-secondary px-8 py-4 text-lg group">
                        Voir la démo
                        <svg class="size-5 inline-block ml-2 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>

                <!-- Dashboard Preview Mockup -->
                <div class="mt-24 relative p-4 bg-white/50 backdrop-blur-sm border border-slate-200 rounded-[2.5rem] shadow-2xl shadow-slate-200/50">
                    <div class="bg-slate-900 rounded-[2rem] overflow-hidden aspect-video shadow-inner">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2426&auto=format&fit=crop" alt="Interface Preview" class="w-full h-full object-cover opacity-80 mix-blend-luminosity"/>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="py-32 bg-white">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-20 space-y-4">
                    <h2 class="text-4xl font-bold text-slate-900">Tout ce dont vous avez besoin</h2>
                    <p class="text-lg text-slate-500 max-w-xl mx-auto font-medium">Des outils puissants conçus pour les équipes modernes.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="premium-card p-10 group">
                        <div class="size-16 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-8 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-500 shadow-sm">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-4 tracking-tight">Gestion des Questions</h3>
                        <p class="text-slate-500 leading-relaxed font-medium">Créez des bibliothèques de questions par domaine et niveau, avec un support multi-choix complet.</p>
                    </div>

                    <div class="premium-card p-10 group">
                        <div class="size-16 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-600 mb-8 group-hover:bg-purple-600 group-hover:text-white transition-all duration-500 shadow-sm">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-4 tracking-tight">Templates Flexibles</h3>
                        <p class="text-slate-500 leading-relaxed font-medium">Assemblez vos tests en quelques clics. Définissez la durée, les domaines et le niveau de difficulté.</p>
                    </div>

                    <div class="premium-card p-10 group">
                        <div class="size-16 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 mb-8 group-hover:bg-emerald-600 group-hover:text-white transition-all duration-500 shadow-sm">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <h3 class="text-2xl font-bold text-slate-900 mb-4 tracking-tight">Analyses Avancées</h3>
                        <p class="text-slate-500 leading-relaxed font-medium">Suivez les performances détaillées en temps réel. Scores moyens, taux de complétion et par domaine.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing / CTA -->
        <section class="py-32 bg-slate-900 text-white overflow-hidden relative">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/50 to-slate-900 -z-10"></div>
            <div class="max-w-4xl mx-auto px-6 text-center">
                <h2 class="text-4xl md:text-5xl font-bold mb-8">Prêt à transformer votre recrutement ?</h2>
                <p class="text-xl text-indigo-200 mb-12 font-medium opacity-80">Rejoignez les entreprises qui font confiance à TestPlatform.</p>
                <Link :href="route('register')" class="inline-block px-10 py-5 bg-white text-slate-900 font-bold rounded-2xl hover:scale-105 transition-transform shadow-xl shadow-white/10">
                    Commencer l'aventure
                </Link>
            </div>
        </section>

        <footer class="py-12 bg-slate-50 border-t border-slate-200 text-center text-slate-500 font-medium">
            <p>© 2026 TestPlatform. Tous droits réservés.</p>
        </footer>
    </div>
</template>
