<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    evaluation: Object,
    student: Object,
    status: String,
    score: [Number, String],
    points_earned: Number,
    points_total: Number,
});

const pendingReview = computed(() => props.status === 'pending_review');
</script>

<template>
    <Head title="Évaluation terminée" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 md:p-8 font-sans">
        <div class="max-w-lg w-full bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden animate-reveal p-10 text-center">
            <div class="size-16 rounded-2xl mx-auto mb-5 flex items-center justify-center"
                :class="pendingReview ? 'bg-amber-50 text-amber-500' : 'bg-emerald-50 text-emerald-500'">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" /><circle cx="12" cy="12" r="9" /></svg>
            </div>

            <h1 class="text-2xl font-black text-slate-900">Évaluation terminée</h1>
            <p class="text-slate-500 text-sm mt-1">{{ evaluation.title }} — {{ student.name }}</p>

            <div v-if="pendingReview" class="mt-8 bg-amber-50 border border-amber-100 rounded-2xl p-5 text-sm text-amber-800">
                Votre copie a bien été enregistrée. Certaines questions nécessitent une correction manuelle par votre enseignant. Votre note finale sera disponible prochainement.
            </div>
            <div v-else class="mt-8">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Votre score</p>
                <p class="text-5xl font-black text-slate-900 mt-2">{{ Math.round(Number(score)) }}<span class="text-2xl text-slate-300">/100</span></p>
                <p class="text-slate-500 text-sm mt-2">{{ points_earned }} / {{ points_total }} points</p>
            </div>

            <p class="text-[11px] text-slate-300 mt-10">Vous pouvez fermer cette fenêtre.</p>
        </div>
    </div>
</template>
