<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    classroom: Object,
    student: Object,
    evaluations: Array,
});

const statusLabel = {
    pending: 'À démarrer', in_progress: 'En cours', completed: 'Terminée',
    pending_review: 'En correction', expired: 'Expirée',
};
</script>

<template>
    <Head title="Classe rejointe" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 md:p-8 font-sans">
        <div class="max-w-2xl w-full bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden animate-reveal p-10">
            <div class="text-center mb-8">
                <div class="size-16 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center mx-auto mb-4">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" /><circle cx="12" cy="12" r="9" /></svg>
                </div>
                <h1 class="text-2xl font-black text-slate-900">Bienvenue {{ student.name }} !</h1>
                <p class="text-slate-500 text-sm mt-1">Vous avez rejoint la classe <strong>{{ classroom.name }}</strong>.</p>
            </div>

            <div v-if="evaluations.length" class="space-y-3">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Évaluations disponibles</p>
                <a v-for="ev in evaluations" :key="ev.token" :href="route('eval.start', ev.token)"
                    class="flex items-center justify-between p-4 rounded-2xl border border-slate-100 hover:border-indigo-200 hover:bg-indigo-50/40 transition-all group">
                    <div>
                        <p class="font-bold text-slate-800 group-hover:text-indigo-600">{{ ev.title }}</p>
                        <p class="text-[11px] text-slate-400">{{ ev.subject }} · {{ ev.questions_count }} questions · {{ ev.time_limit_minutes }} min</p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ statusLabel[ev.status] }}</span>
                </a>
            </div>
            <p v-else class="text-center text-slate-400 text-sm italic">Aucune évaluation publiée pour le moment. Revenez plus tard.</p>
        </div>
    </div>
</template>
