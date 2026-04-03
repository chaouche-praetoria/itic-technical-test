<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    session: Object,
    candidate: Object,
});
</script>

<template>
    <Head title="Aucune question disponible" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-6 font-sans">
        <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl shadow-slate-200 border border-slate-100 p-12 text-center animate-reveal">
            
            <!-- Icon Decoration -->
            <div class="size-24 bg-rose-50 rounded-3xl flex items-center justify-center mx-auto mb-10 shadow-inner">
                <svg class="size-12 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>

            <h1 class="text-3xl font-black text-slate-900 leading-tight mb-4 text-balance">
                Oups ! Aucune question n'est disponible.
            </h1>
            
            <p class="text-slate-500 text-sm font-medium leading-relaxed mb-10 px-4">
                Il semble qu'une erreur soit survenue lors de la génération de votre test. Notre équipe technique a été informée.
            </p>

            <div class="space-y-4">
                 <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100 text-left">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="size-2 rounded-full bg-slate-300"></div>
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Détails de session</span>
                    </div>
                    <p class="text-xs font-bold text-slate-600">ID: {{ session.token.substring(0, 12) }}...</p>
                    <p class="text-xs font-bold text-slate-600">Candidat: {{ candidate.name }}</p>
                </div>

                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-4">
                    Veuillez contacter votre recruteur pour régénérer un lien.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-reveal {
    animation: reveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes reveal {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
