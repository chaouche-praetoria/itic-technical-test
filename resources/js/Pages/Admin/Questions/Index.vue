<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import CodeEditor from '@/Components/CodeEditor.vue';

const props = defineProps({
    questions: Object,
    domains: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const domainId = ref(props.filters.domain_id || '');
const difficulty = ref(props.filters.difficulty || '');
const filtering = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const showDetails = ref(false);
const showPreview = ref(false);
const selectedQuestion = ref(null);

// Preview state
const previewAnswers = ref({});
const previewCode = ref('');
const previewLanguage = ref('');
const previewLoading = ref(false);
const previewResult = ref(null);

const typeLabel = { mcq: 'QCM', text: 'Réponse libre', code: 'Programmation' };
const difficultyLabel = { easy: 'Facile', medium: 'Intermédiaire', hard: 'Avancé' };

const typeIcon = {
    mcq: '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    text: '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
    code: '<svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>'
};

let filterTimer;
function applyFilters() {
    filtering.value = true;
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        router.get(route('admin.questions.index'), {
            search: search.value,
            type: type.value,
            domain_id: domainId.value,
            difficulty: difficulty.value,
        }, { preserveState: true, replace: true, onFinish: () => { filtering.value = false; } });
    }, 400);
}

watch([search, type, domainId, difficulty], applyFilters);

function deleteQuestion(id) {
    deleteTarget.value = id;
}

function confirmDelete() {
    deleting.value = true;
    router.delete(route('admin.questions.destroy', deleteTarget.value), {
        onSuccess: () => { deleteTarget.value = null; },
        onFinish: () => { deleting.value = false; },
    });
}

function openDetails(q) {
    selectedQuestion.value = q;
    showDetails.value = true;
}

function openPreview(q) {
    selectedQuestion.value = q;
    previewAnswers.value = {};
    previewCode.value = q.initial_code || '';
    previewLanguage.value = q.default_language || 'javascript';
    previewResult.value = null;
    showPreview.value = true;
}

function togglePreviewChoice(choiceId, multiple) {
    if (multiple) {
        const current = previewAnswers.value[selectedQuestion.value.id] || [];
        const idx = current.indexOf(choiceId);
        if (idx === -1) {
            previewAnswers.value[selectedQuestion.value.id] = [...current, choiceId];
        } else {
            previewAnswers.value[selectedQuestion.value.id] = current.filter(id => id !== choiceId);
        }
    } else {
        previewAnswers.value[selectedQuestion.value.id] = [choiceId];
    }
}

async function runPreviewCode() {
    previewLoading.value = true;
    previewResult.value = null;
    try {
        const res = await axios.post(route('admin.questions.test'), {
            code: previewCode.value,
            language: previewLanguage.value,
            unit_tests: selectedQuestion.value.unit_tests,
        });
        previewResult.value = res.data;
    } catch (e) {
        previewResult.value = { success: false, error: "Erreur d'exécution" };
    } finally {
        previewLoading.value = false;
    }
}
</script>

<template>
    <Head title="Questions" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Banque de questions</h2>
                    <p class="text-sm text-slate-500 font-medium">Gérez votre bibliothèque de questions interactives.</p>
                </div>
                <Link :href="route('admin.questions.create')" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouvelle question
                </Link>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- Advanced Filters -->
                <div class="premium-card p-6 glass-card grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Recherche</label>
                        <div class="relative">
                            <input v-model="search" type="text" placeholder="Énoncé..." 
                                class="w-full bg-slate-50 border-none rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700 pl-10" />
                            <svg v-if="!filtering" class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <svg v-else class="absolute left-3.5 top-1/2 -translate-y-1/2 size-4 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Type</label>
                        <select v-model="type" class="w-full bg-slate-50 border-none rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
                            <option value="">Tous les types</option>
                            <option value="mcq">QCM</option>
                            <option value="text">Texte libre</option>
                            <option value="code">Code</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Domaine</label>
                        <select v-model="domainId" class="w-full bg-slate-50 border-none rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
                            <option value="">Tous les domaines</option>
                            <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 ml-1">Difficulté</label>
                        <select v-model="difficulty" class="w-full bg-slate-50 border-none rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
                            <option value="">Toutes les difficultés</option>
                            <option value="easy">Facile</option>
                            <option value="medium">Moyen</option>
                            <option value="hard">Difficile</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-xs uppercase tracking-widest font-bold">
                                    <th class="px-8 py-5 text-left border-b border-slate-100 uppercase">Énoncé de la question</th>
                                    <th class="px-8 py-5 text-left border-b border-slate-100 uppercase">Type & Thématique</th>
                                    <th class="px-8 py-5 text-center border-b border-slate-100 uppercase">Difficulté</th>
                                    <th class="px-8 py-5 text-right border-b border-slate-100 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="questions.data.length === 0">
                                    <td colspan="4" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucune question ne correspond aux critères</td>
                                </tr>
                                <tr v-for="q in questions.data" :key="q.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-8 py-6 max-w-sm">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-base mb-1 line-clamp-1 group-hover:text-indigo-600 transition-colors">{{ q.statement }}</span>
                                            <div class="flex items-center gap-2">
                                                <span v-if="q.type === 'mcq'" class="text-[10px] font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded uppercase tracking-tighter">{{ q.choices.length }} options</span>
                                                <span v-else class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">Question ouverte</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div v-html="typeIcon[q.type]" :class="{
                                                    'text-indigo-500': q.type === 'mcq',
                                                    'text-fuchsia-500': q.type === 'text',
                                                    'text-amber-500': q.type === 'code'
                                                }"></div>
                                                <span class="text-slate-700 font-bold text-xs">{{ typeLabel[q.type] }}</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="d in q.domains" :key="d.id" class="text-[9px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded uppercase tracking-tighter">
                                                    {{ d.name }}
                                                </span>
                                            </div>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="t in q.themes" :key="t.id" class="text-[9px] font-medium text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded italic">
                                                    {{ t.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span :class="{
                                            'bg-emerald-50 text-emerald-600 border-emerald-100': q.difficulty === 'easy',
                                            'bg-amber-50 text-amber-600 border-amber-100': q.difficulty === 'medium',
                                            'bg-rose-50 text-rose-600 border-rose-100': q.difficulty === 'hard'
                                        }" class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border">
                                            {{ difficultyLabel[q.difficulty] }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button @click="openDetails(q)" 
                                                class="size-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all"
                                                title="Détails">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <button @click="openPreview(q)" 
                                                class="size-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all"
                                                title="Tester">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                            <Link :href="route('admin.questions.edit', q.id)" 
                                                class="size-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all"
                                                title="Modifier">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </Link>
                                            <button @click="deleteQuestion(q.id)" 
                                                class="size-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all"
                                                title="Supprimer">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Enhanced Pagination -->
                    <div v-if="questions.last_page > 1" class="px-8 py-6 bg-slate-50/30 flex items-center justify-between border-t border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ questions.total }} questions au total</p>
                        <div class="flex gap-1.5">
                            <Link v-for="link in questions.links" :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'size-8 flex items-center justify-center text-xs font-bold rounded-lg transition-all',
                                    link.active ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300',
                                    !link.url ? 'opacity-30 pointer-events-none' : ''
                                ]" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

    <Teleport to="body">
        <!-- Delete Confirmation Modal -->
        <div v-if="deleteTarget" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-sm shadow-2xl animate-reveal border border-slate-100 text-center">
                <div class="size-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-6">
                    <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Supprimer ?</h3>
                <p class="text-sm text-slate-500 font-medium mb-8">Cette action est irréversible et retirera la question de tous les tests associés.</p>
                <div class="flex gap-4">
                    <button @click="deleteTarget = null"
                        class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                        Garder
                    </button>
                    <button @click="confirmDelete" :disabled="deleting"
                        class="flex-1 px-6 py-4 text-sm font-bold text-white bg-rose-600 rounded-2xl hover:bg-rose-700 transition-all shadow-xl shadow-rose-200 disabled:opacity-50 active:scale-[0.98]">
                        <span v-if="!deleting">Supprimer</span>
                        <svg v-else class="size-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="showDetails && selectedQuestion" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-[2.5rem] w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-2xl animate-reveal flex flex-col">
                <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between shrink-0">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-900">Détails de la question</h3>
                        <p class="text-sm text-slate-400 font-medium uppercase tracking-widest mt-1">ID: #{{ selectedQuestion.id }}</p>
                    </div>
                    <button @click="showDetails = false" class="size-10 rounded-full bg-slate-50 text-slate-400 flex items-center justify-center hover:bg-slate-100 transition-all">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-10 custom-scrollbar">
                    <div class="space-y-10">
                        <!-- Metadata -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Type</span>
                                <span class="px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">{{ typeLabel[selectedQuestion.type] }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Difficulté</span>
                                <span class="px-3 py-1 bg-slate-100 rounded-lg text-xs font-bold text-slate-600">{{ difficultyLabel[selectedQuestion.difficulty] }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Domaines</span>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="d in selectedQuestion.domains" :key="d.id" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-bold">{{ d.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Statement -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Énoncé</span>
                            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-lg font-medium text-slate-800 leading-relaxed">
                                {{ selectedQuestion.statement }}
                            </div>
                        </div>

                        <!-- MCQ Content -->
                        <div v-if="selectedQuestion.type === 'mcq'">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Options de réponse</span>
                            <div class="space-y-3">
                                <div v-for="choice in selectedQuestion.choices" :key="choice.id" 
                                    class="flex items-center gap-4 p-4 rounded-xl border"
                                    :class="choice.is_correct ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-white border-slate-100 text-slate-500'">
                                    <div class="size-6 rounded-full flex items-center justify-center shrink-0 border-2"
                                        :class="choice.is_correct ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200 text-slate-300'">
                                        <svg v-if="choice.is_correct" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <span class="font-bold">{{ choice.text }}</span>
                                    <span v-if="choice.is_correct" class="ml-auto text-[10px] font-black uppercase tracking-widest">Correcte</span>
                                </div>
                            </div>
                        </div>

                        <!-- Code Content -->
                        <div v-if="selectedQuestion.type === 'code'" class="space-y-6">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Configuration Code</span>
                                <div class="bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
                                    <div class="px-6 py-3 border-b border-white/5 flex items-center justify-between">
                                        <span class="text-[10px] font-mono text-slate-500 font-bold uppercase tracking-widest">Boilerplate ({{ selectedQuestion.default_language }})</span>
                                    </div>
                                    <CodeEditor :modelValue="selectedQuestion.initial_code" :language="selectedQuestion.default_language" height="200px" dark readonly />
                                </div>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Tests Unitaires</span>
                                <div class="bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 shadow-xl">
                                    <div class="px-6 py-3 border-b border-white/5 flex items-center justify-between">
                                        <span class="text-[10px] font-mono text-slate-500 font-bold uppercase tracking-widest">Validation Script</span>
                                    </div>
                                    <CodeEditor :modelValue="selectedQuestion.unit_tests" :language="selectedQuestion.default_language" height="200px" dark readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-10 py-8 border-t border-slate-50 bg-slate-50/50 flex justify-end shrink-0">
                    <button @click="showDetails = false" class="px-8 py-3 bg-slate-900 text-white rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all active:scale-[0.98]">
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreview && selectedQuestion" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
            <div class="bg-white rounded-[2.5rem] w-full max-w-5xl max-h-[95vh] overflow-hidden shadow-2xl animate-reveal flex flex-col border border-slate-100">
                <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between shrink-0 bg-indigo-600 text-white">
                    <div class="flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Mode Aperçu (Candidat)</h3>
                            <p class="text-[10px] font-bold text-indigo-100 uppercase tracking-widest mt-0.5">Testez l'expérience utilisateur en temps réel</p>
                        </div>
                    </div>
                    <button @click="showPreview = false" class="size-10 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20 transition-all">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-10 custom-scrollbar bg-slate-50/30">
                    <div class="max-w-4xl mx-auto space-y-10">
                        <!-- Preview Question Layout -->
                        <div class="bg-white rounded-[2rem] p-10 shadow-sm border border-slate-100">
                            <div class="flex items-center gap-3 mb-8 pb-8 border-b border-slate-50">
                                <span class="bg-slate-900 text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-wider">Aperçu</span>
                                <span class="px-4 py-1.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100 text-[10px] font-black uppercase tracking-wider">
                                    {{ typeLabel[selectedQuestion.type] }}
                                </span>
                            </div>

                            <h2 class="text-2xl font-bold text-slate-900 mb-10 leading-tight">
                                {{ selectedQuestion.statement }}
                            </h2>

                            <!-- MCQ Preview -->
                            <div v-if="selectedQuestion.type === 'mcq'" class="grid gap-4">
                                <button v-for="(choice, idx) in selectedQuestion.choices" :key="choice.id"
                                    @click="togglePreviewChoice(choice.id, selectedQuestion.multiple_answers)"
                                    :class="[
                                        'group flex items-center gap-5 p-6 rounded-2xl border-2 text-left transition-all',
                                        (previewAnswers[selectedQuestion.id] || []).includes(choice.id)
                                            ? 'border-slate-900 bg-slate-900 text-white shadow-xl shadow-slate-200'
                                            : 'border-slate-100 bg-slate-50 hover:border-slate-300 hover:bg-white'
                                    ]">
                                    <div :class="[
                                        'size-7 rounded-lg border-2 flex items-center justify-center shrink-0 font-black text-xs transition-colors',
                                        (previewAnswers[selectedQuestion.id] || []).includes(choice.id)
                                            ? 'bg-white border-white text-slate-900'
                                            : 'bg-white border-slate-200 text-slate-300'
                                    ]">
                                        {{ String.fromCharCode(65 + idx) }}
                                    </div>
                                    <span class="text-base font-bold">{{ choice.text }}</span>
                                </button>
                            </div>

                            <!-- Text Preview -->
                            <div v-if="selectedQuestion.type === 'text'">
                                <textarea rows="8" placeholder="Réponse du candidat..." 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-[2rem] p-8 text-lg font-medium text-slate-700 focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 focus:bg-white transition-all resize-none"></textarea>
                            </div>

                            <!-- Code Preview -->
                            <div v-if="selectedQuestion.type === 'code'" class="space-y-6">
                                <div class="bg-slate-900 p-1 rounded-[2rem] shadow-2xl overflow-hidden border border-slate-800">
                                    <div class="px-6 py-3 flex items-center justify-between border-b border-white/5">
                                        <div class="flex gap-1.5">
                                            <div class="size-2.5 rounded-full bg-rose-500"></div>
                                            <div class="size-2.5 rounded-full bg-amber-500"></div>
                                            <div class="size-2.5 rounded-full bg-emerald-500"></div>
                                        </div>
                                        <span class="text-[10px] font-mono text-slate-500 font-bold uppercase tracking-widest">{{ previewLanguage }} editor</span>
                                    </div>
                                    <CodeEditor v-model="previewCode" :language="previewLanguage" height="400px" dark />
                                    
                                    <div class="p-6 bg-slate-800/30 border-t border-white/5 flex items-center justify-between">
                                        <button @click="runPreviewCode" :disabled="previewLoading"
                                            class="bg-emerald-500 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-xl shadow-emerald-500/20 disabled:opacity-30 flex items-center gap-3 active:scale-[0.98]">
                                            <svg v-if="!previewLoading" class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                            <div v-else class="size-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                            {{ previewLoading ? 'Exécution...' : 'Tester le code' }}
                                        </button>

                                        <div v-if="previewResult" class="flex items-center gap-4">
                                            <span :class="previewResult.success ? 'text-emerald-400' : 'text-rose-400'" class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                                <div :class="previewResult.success ? 'bg-emerald-400' : 'bg-rose-400'" class="size-1.5 rounded-full animate-pulse"></div>
                                                {{ previewResult.success ? 'Succès' : 'Échec' }}
                                            </span>
                                            <div class="h-4 w-px bg-white/10"></div>
                                            <span class="text-white font-mono text-xs font-bold">{{ previewResult.passed || 0 }} / {{ previewResult.total || 0 }} tests</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Preview Logs -->
                                <div v-if="previewResult && (previewResult.output || previewResult.error)" 
                                    class="bg-white border border-slate-100 rounded-3xl p-8 shadow-inner animate-reveal">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Console Output</h4>
                                    <pre v-if="previewResult.output" class="text-xs font-mono text-slate-700 whitespace-pre-wrap">{{ previewResult.output }}</pre>
                                    <pre v-if="previewResult.error" class="text-xs font-mono text-rose-500 whitespace-pre-wrap bg-rose-50 border border-rose-100 p-4 rounded-xl">{{ previewResult.error }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-10 py-8 border-t border-slate-50 bg-slate-50/50 flex justify-end shrink-0 gap-4">
                    <button @click="showPreview = false" class="px-10 py-3 bg-slate-900 text-white rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all active:scale-[0.98]">
                        Quitter l'aperçu
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
