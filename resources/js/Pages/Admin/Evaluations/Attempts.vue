<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ evaluation: Object, attempts: Array });

const expanded = ref(null);
const grades = ref({});

function toggle(attempt) {
    expanded.value = expanded.value === attempt.id ? null : attempt.id;
    if (expanded.value) {
        grades.value = {};
        for (const ans of attempt.answers) {
            grades.value[ans.id] = ans.points_awarded ?? 0;
        }
    }
}

function questionFor(qid) {
    return props.evaluation.questions.find(q => q.id === qid);
}

function answerFor(attempt, qid) {
    return attempt.answers.find(a => a.evaluation_question_id === qid);
}

function choiceText(qid, ids) {
    const q = questionFor(qid);
    if (!q || !ids) return '—';
    return q.choices.filter(c => ids.includes(c.id)).map(c => c.text).join(', ') || '—';
}

function saveGrades(attempt) {
    const textAnswers = attempt.answers.filter(a => questionFor(a.evaluation_question_id)?.type === 'text');
    const payload = textAnswers.map(a => ({ answer_id: a.id, points_awarded: Number(grades.value[a.id]) || 0 }));
    router.post(route('admin.attempts.grade', attempt.id), { grades: payload }, { preserveScroll: true });
}

const statusLabel = {
    pending: 'Non commencée', in_progress: 'En cours', completed: 'Terminée',
    pending_review: 'À corriger', expired: 'Expirée',
};
</script>

<template>
    <Head :title="`Copies — ${evaluation.title}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Copies — {{ evaluation.title }}</h2>
                    <p class="text-xs text-slate-500 font-medium">{{ attempts.length }} tentative(s)</p>
                </div>
                <Link :href="route('admin.evaluations.index')" class="text-xs font-bold text-slate-500 hover:text-slate-800">Retour</Link>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-3">
                <div v-if="attempts.length === 0" class="premium-card p-12 text-center text-slate-300 italic">Aucune tentative pour le moment</div>

                <div v-for="attempt in attempts" :key="attempt.id" class="premium-card overflow-hidden">
                    <button @click="toggle(attempt)" class="w-full flex items-center justify-between px-6 py-4 hover:bg-slate-50/80 transition-all text-left">
                        <div>
                            <span class="font-bold text-slate-800">{{ attempt.student?.first_name }} {{ attempt.student?.last_name }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ attempt.student?.email }}</span>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="text-xs font-bold uppercase tracking-wider"
                                :class="attempt.status === 'pending_review' ? 'text-amber-500' : (attempt.status === 'completed' ? 'text-emerald-600' : 'text-slate-400')">
                                {{ statusLabel[attempt.status] }}
                            </span>
                            <span v-if="attempt.points_total != null" class="text-sm font-black text-slate-700">{{ attempt.points_earned }} / {{ attempt.points_total }} pts</span>
                        </div>
                    </button>

                    <div v-if="expanded === attempt.id" class="border-t border-slate-100 p-6 space-y-5 bg-slate-50/30">
                        <div v-for="q in evaluation.questions" :key="q.id" class="bg-white rounded-xl border border-slate-100 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <p class="text-sm font-bold text-slate-800">{{ q.statement }}</p>
                                <span class="text-[10px] font-bold uppercase text-slate-400 whitespace-nowrap">{{ q.points }} pts</span>
                            </div>

                            <div class="mt-3 text-sm">
                                <template v-if="q.type === 'mcq'">
                                    <p class="text-slate-500"><span class="font-bold text-slate-400 text-[10px] uppercase tracking-wider">Réponse :</span> {{ choiceText(q.id, answerFor(attempt, q.id)?.selected_choice_ids) }}</p>
                                    <p class="text-[11px] mt-1" :class="answerFor(attempt, q.id)?.is_correct ? 'text-emerald-600' : 'text-rose-500'">
                                        {{ answerFor(attempt, q.id)?.points_awarded ?? 0 }} / {{ q.points }} pts (auto)
                                    </p>
                                </template>
                                <template v-else>
                                    <p class="text-slate-600 whitespace-pre-wrap bg-slate-50 rounded-lg p-3">{{ answerFor(attempt, q.id)?.answer_text || '(pas de réponse)' }}</p>
                                    <div v-if="answerFor(attempt, q.id)" class="flex items-center gap-2 mt-2">
                                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Points attribués</label>
                                        <input v-model="grades[answerFor(attempt, q.id).id]" type="number" min="0" :max="q.points"
                                            class="w-20 rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                        <span class="text-xs text-slate-400">/ {{ q.points }}</span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <button @click="saveGrades(attempt)" class="bg-slate-900 text-white px-5 py-2.5 rounded-lg hover:bg-slate-800 font-bold text-xs">
                            Enregistrer la correction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
