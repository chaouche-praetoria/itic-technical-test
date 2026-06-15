<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    token: String,
    evaluation: Object,
    expires_at: String,
    questions: Array,
    existingAnswers: Object,
});

// answers keyed by question id -> { answer_text, selected_choice_ids: [] }
const answers = ref({});
props.questions.forEach(q => {
    const existing = props.existingAnswers?.[q.id];
    answers.value[q.id] = {
        answer_text: existing?.answer_text ?? '',
        selected_choice_ids: existing?.selected_choice_ids ?? [],
    };
});

const submitting = ref(false);
const submitted = ref(false);

// Timer
const expiresAt = new Date(props.expires_at).getTime();
const remaining = ref(Math.max(0, Math.floor((expiresAt - Date.now()) / 1000)));
let timer = null;

const timerDisplay = computed(() => {
    const m = Math.floor(remaining.value / 60);
    const s = remaining.value % 60;
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});
const timerColor = computed(() => remaining.value < 60 ? 'text-rose-600' : (remaining.value < 300 ? 'text-amber-500' : 'text-slate-800'));

onMounted(() => {
    timer = setInterval(() => {
        remaining.value = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
        if (remaining.value <= 0) {
            clearInterval(timer);
            submitExam(true);
        }
    }, 1000);
});
onUnmounted(() => timer && clearInterval(timer));

async function saveAnswer(q) {
    const a = answers.value[q.id];
    try {
        await axios.post(route('eval.answer', props.token), {
            question_id: q.id,
            answer_text: q.type === 'text' ? a.answer_text : null,
            selected_choice_ids: q.type === 'mcq' ? a.selected_choice_ids : null,
        });
    } catch (e) {
        if (e.response?.status === 422 && e.response?.data?.expired) {
            window.location.href = route('eval.result', props.token);
        }
    }
}

function toggleChoice(q, choiceId) {
    const arr = answers.value[q.id].selected_choice_ids;
    if (q.multiple_answers) {
        const i = arr.indexOf(choiceId);
        i === -1 ? arr.push(choiceId) : arr.splice(i, 1);
    } else {
        answers.value[q.id].selected_choice_ids = [choiceId];
    }
    saveAnswer(q);
}

const showConfirm = ref(false);
const answeredCount = computed(() =>
    props.questions.filter(q => {
        const a = answers.value[q.id];
        return q.type === 'mcq' ? a.selected_choice_ids.length > 0 : a.answer_text.trim() !== '';
    }).length
);

async function submitExam(auto = false) {
    if (submitting.value || submitted.value) return;
    submitting.value = true;
    submitted.value = true;
    // Flush text answers before submitting
    if (!auto) {
        for (const q of props.questions.filter(q => q.type === 'text')) {
            await saveAnswer(q);
        }
    }
    router.post(route('eval.submit', props.token));
}
</script>

<template>
    <Head :title="evaluation.title" />

    <div class="min-h-screen bg-slate-50 font-sans">
        <!-- Top bar -->
        <header class="sticky top-0 z-20 bg-white/90 backdrop-blur border-b border-slate-100">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="font-black text-slate-900 truncate">{{ evaluation.title }}</h1>
                    <p class="text-[11px] text-slate-400">{{ answeredCount }} / {{ questions.length }} répondues</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="size-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 2" /></svg>
                        <span :class="timerColor" class="font-black text-lg tabular-nums">{{ timerDisplay }}</span>
                    </div>
                    <button @click="showConfirm = true" :disabled="submitting" class="bg-slate-900 text-white px-4 py-2 rounded-lg font-bold text-xs hover:bg-slate-800 disabled:opacity-50">Terminer</button>
                </div>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8 space-y-6">
            <!-- Statement + attachment -->
            <div class="premium-card bg-white p-6">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Énoncé</p>
                <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ evaluation.statement }}</p>

                <div v-if="evaluation.attachment_path" class="mt-5">
                    <img v-if="evaluation.attachment_type === 'image'" :src="`/storage/${evaluation.attachment_path}`" alt="Pièce jointe" class="rounded-xl border border-slate-100 max-h-96" />
                    <a v-else :href="`/storage/${evaluation.attachment_path}`" target="_blank"
                        class="inline-flex items-center gap-2 bg-rose-50 text-rose-600 px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-rose-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        Ouvrir le document PDF
                    </a>
                </div>
            </div>

            <!-- Questions -->
            <div v-for="(q, qi) in questions" :key="q.id" class="premium-card bg-white p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <p class="font-bold text-slate-800"><span class="text-indigo-500">{{ qi + 1 }}.</span> {{ q.statement }}</p>
                    <span class="text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">{{ q.points }} pts</span>
                </div>

                <div v-if="q.type === 'mcq'" class="space-y-2">
                    <button v-for="c in q.choices" :key="c.id" type="button" @click="toggleChoice(q, c.id)"
                        :class="answers[q.id].selected_choice_ids.includes(c.id) ? 'border-indigo-500 bg-indigo-50/60' : 'border-slate-100 hover:border-slate-200'"
                        class="w-full flex items-center gap-3 p-4 rounded-xl border-2 text-left transition-all">
                        <span :class="answers[q.id].selected_choice_ids.includes(c.id) ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300'"
                            class="size-5 rounded-md border-2 flex items-center justify-center shrink-0">
                            <svg v-if="answers[q.id].selected_choice_ids.includes(c.id)" class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        <span class="text-sm font-medium text-slate-700">{{ c.text }}</span>
                    </button>
                    <p v-if="q.multiple_answers" class="text-[11px] text-slate-400 italic">Plusieurs réponses possibles</p>
                </div>

                <textarea v-else v-model="answers[q.id].answer_text" @blur="saveAnswer(q)" rows="5"
                    class="w-full rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Votre réponse…"></textarea>
            </div>
        </main>

        <!-- Confirm modal -->
        <div v-if="showConfirm" class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
                <h3 class="text-lg font-black text-slate-900">Terminer l'évaluation ?</h3>
                <p class="text-slate-500 text-sm mt-2">Vous avez répondu à {{ answeredCount }} question(s) sur {{ questions.length }}. Cette action est définitive.</p>
                <div class="flex gap-3 mt-6">
                    <button @click="showConfirm = false" :disabled="submitting" class="flex-1 bg-slate-100 text-slate-700 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-200 disabled:opacity-50">Annuler</button>
                    <button @click="submitExam(false)" :disabled="submitting" class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-rose-700 disabled:opacity-50">
                        {{ submitting ? 'Envoi…' : 'Soumettre' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
