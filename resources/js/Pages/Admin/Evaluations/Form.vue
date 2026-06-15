<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ evaluation: Object, classes: Array });

const isEdit = !!props.evaluation;

function mapQuestion(q) {
    return {
        type: q.type,
        statement: q.statement,
        points: q.points,
        multiple_answers: q.multiple_answers ?? false,
        choices: (q.choices ?? []).map(c => ({ text: c.text, is_correct: c.is_correct })),
    };
}

const form = useForm({
    classroom_id: props.evaluation?.classroom_id ?? '',
    title: props.evaluation?.title ?? '',
    subject: props.evaluation?.subject ?? '',
    statement: props.evaluation?.statement ?? '',
    time_limit_minutes: props.evaluation?.time_limit_minutes ?? 30,
    available_until: props.evaluation?.available_until?.substring(0, 16) ?? '',
    attachment: null,
    questions: props.evaluation?.questions?.map(mapQuestion) ?? [
        { type: 'mcq', statement: '', points: 1, multiple_answers: false, choices: [{ text: '', is_correct: false }, { text: '', is_correct: false }] },
    ],
});

const totalPoints = computed(() => form.questions.reduce((sum, q) => sum + (Number(q.points) || 0), 0));

function addQuestion() {
    form.questions.push({ type: 'mcq', statement: '', points: 1, multiple_answers: false, choices: [{ text: '', is_correct: false }, { text: '', is_correct: false }] });
}
function removeQuestion(i) {
    form.questions.splice(i, 1);
}
function addChoice(qi) {
    form.questions[qi].choices.push({ text: '', is_correct: false });
}
function removeChoice(qi, ci) {
    form.questions[qi].choices.splice(ci, 1);
}
function onFile(e) {
    form.attachment = e.target.files[0] ?? null;
}

function submit() {
    if (isEdit) {
        form.transform(data => ({ ...data, _method: 'PUT' })).post(route('admin.evaluations.update', props.evaluation.id));
    } else {
        form.post(route('admin.evaluations.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Éditer l\'évaluation' : 'Nouvelle évaluation'" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ isEdit ? 'Éditer l\'évaluation' : 'Nouvelle évaluation' }}</h2>
        </template>

        <div class="py-6 animate-reveal">
            <form @submit.prevent="submit" class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- General -->
                <div class="premium-card p-8 space-y-5">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Sujet de l'évaluation</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Titre</label>
                            <input v-model="form.title" type="text" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.title" class="text-rose-500 text-xs mt-1">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Matière / Sujet</label>
                            <input v-model="form.subject" type="text" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Classe</label>
                            <select v-model="form.classroom_id" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="" disabled>Sélectionnez une classe</option>
                                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }} — {{ c.academic_level?.name }}</option>
                            </select>
                            <p v-if="form.errors.classroom_id" class="text-rose-500 text-xs mt-1">{{ form.errors.classroom_id }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Temps imparti (minutes)</label>
                            <input v-model="form.time_limit_minutes" type="number" min="1" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.time_limit_minutes" class="text-rose-500 text-xs mt-1">{{ form.errors.time_limit_minutes }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Énoncé</label>
                        <textarea v-model="form.statement" rows="5" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Consignes générales, contexte de l'examen…"></textarea>
                        <p v-if="form.errors.statement" class="text-rose-500 text-xs mt-1">{{ form.errors.statement }}</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Pièce jointe (image ou PDF)</label>
                        <input type="file" accept="image/*,application/pdf" @change="onFile" class="text-sm" />
                        <p v-if="isEdit && evaluation.attachment_path" class="text-[11px] text-slate-400 mt-1">
                            Fichier actuel : <a :href="`/storage/${evaluation.attachment_path}`" target="_blank" class="text-indigo-600 font-bold">voir</a> (téléverser un nouveau fichier le remplacera)
                        </p>
                        <p v-if="form.errors.attachment" class="text-rose-500 text-xs mt-1">{{ form.errors.attachment }}</p>
                    </div>
                </div>

                <!-- Questions -->
                <div class="premium-card p-8 space-y-6">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Questions ({{ totalPoints }} pts au total)</p>
                        <button type="button" @click="addQuestion" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Ajouter une question</button>
                    </div>
                    <p v-if="form.errors.questions" class="text-rose-500 text-xs">{{ form.errors.questions }}</p>

                    <div v-for="(q, qi) in form.questions" :key="qi" class="border border-slate-100 rounded-2xl p-5 space-y-4 bg-slate-50/40">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400">Question {{ qi + 1 }}</span>
                            <button type="button" @click="removeQuestion(qi)" class="text-xs font-bold text-rose-500 hover:text-rose-700">Retirer</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Type</label>
                                <select v-model="q.type" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="mcq">QCM</option>
                                    <option value="text">Réponse libre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Points</label>
                                <input v-model="q.points" type="number" min="1" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>
                            <div v-if="q.type === 'mcq'" class="flex items-end pb-1">
                                <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                                    <input type="checkbox" v-model="q.multiple_answers" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                    Plusieurs bonnes réponses
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Énoncé de la question</label>
                            <textarea v-model="q.statement" rows="2" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>

                        <div v-if="q.type === 'mcq'" class="space-y-2">
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-slate-400">Choix de réponse</label>
                            <div v-for="(c, ci) in q.choices" :key="ci" class="flex items-center gap-2">
                                <input type="checkbox" v-model="c.is_correct" title="Bonne réponse" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                <input v-model="c.text" type="text" placeholder="Texte du choix" class="flex-1 rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                <button type="button" @click="removeChoice(qi, ci)" class="text-slate-300 hover:text-rose-500 font-bold px-1">&times;</button>
                            </div>
                            <button type="button" @click="addChoice(qi)" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">+ Ajouter un choix</button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="form.processing" class="bg-slate-900 text-white px-6 py-3 rounded-lg hover:bg-slate-800 font-bold text-xs disabled:opacity-50">
                        {{ isEdit ? 'Enregistrer' : 'Créer l\'évaluation' }}
                    </button>
                    <Link :href="route('admin.evaluations.index')" class="text-xs font-bold text-slate-500 hover:text-slate-800">Annuler</Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
