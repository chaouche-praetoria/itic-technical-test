<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    token: String,
    evaluation: Object,
    student: Object,
});

const starting = ref(false);

function start() {
    starting.value = true;
    router.post(route('eval.begin', props.token));
}
</script>

<template>
    <Head title="Démarrer l'évaluation" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 md:p-8 font-sans">
        <div class="max-w-3xl w-full bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden animate-reveal">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-2/5 bg-slate-900 p-10 text-white flex flex-col justify-between">
                    <div>
                        <div class="size-14 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-xl mb-6 border border-white/20">{{ student.name.charAt(0) }}</div>
                        <h2 class="text-xl font-extrabold leading-tight">{{ student.name }}</h2>
                        <p class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-1">Étudiant</p>
                    </div>
                    <div class="space-y-5 mt-12">
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Temps imparti</p>
                            <p class="text-xl font-bold">{{ evaluation.time_limit_minutes }} minutes</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Questions</p>
                            <p class="text-lg font-bold">{{ evaluation.questions_count }} · {{ evaluation.total_points }} pts</p>
                        </div>
                    </div>
                </div>

                <div class="md:w-3/5 p-10 lg:p-14">
                    <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-wider border border-indigo-100">Prêt à commencer ?</span>
                    <h1 class="text-3xl font-black text-slate-900 mt-4 leading-tight">{{ evaluation.title }}</h1>
                    <p v-if="evaluation.subject" class="text-slate-500 font-medium mt-2">{{ evaluation.subject }}</p>

                    <div class="mt-8 bg-amber-50 border border-amber-100 rounded-2xl p-5 text-sm text-amber-800">
                        <p class="font-bold mb-1">⏱️ Attention</p>
                        Le chronomètre démarre dès que vous cliquez sur « Commencer ». Vous disposez de <strong>{{ evaluation.time_limit_minutes }} minutes</strong>. À la fin du temps imparti, votre copie sera soumise automatiquement.
                    </div>

                    <button @click="start" :disabled="starting"
                        class="mt-8 w-full bg-slate-900 text-white py-4 rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50">
                        {{ starting ? 'Démarrage…' : 'Commencer l\'évaluation' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
