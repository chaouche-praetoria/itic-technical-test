<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import CodeEditor from '@/Components/CodeEditor.vue';
import { LANGUAGE_TEMPLATES } from '@/Constants/questionTemplates';

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
const showImportModal = ref(false);

const importForm = useForm({
    file: null,
});

function submitImport() {
    importForm.post(route('admin.questions.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
}

// Preview state
const previewAnswers = ref({});
const previewCode = ref('');
const previewLanguage = ref('');
const previewLoading = ref(false);
const previewResult = ref(null);
const previewValidated = ref(false);
const previewSuccess = ref(false);

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
    previewCode.value = q.initial_code || LANGUAGE_TEMPLATES[q.default_language || 'javascript'] || '';
    previewLanguage.value = q.default_language || 'javascript';
    previewResult.value = null;
    previewValidated.value = false;
    previewSuccess.value = false;
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

function validatePreviewAnswer() {
    previewValidated.value = true;
    if (selectedQuestion.value.type === 'mcq') {
        const correctIds = selectedQuestion.value.choices
            .filter(c => c.is_correct)
            .map(c => c.id)
            .sort();
        const selectedIds = (previewAnswers.value[selectedQuestion.value.id] || [])
            .slice()
            .sort();
        
        previewSuccess.value = JSON.stringify(correctIds) === JSON.stringify(selectedIds);
    } else if (selectedQuestion.value.type === 'text') {
        previewSuccess.value = true; // No automated validation for text
    }
}

const cleanLabel = (label) => {
    if (label.includes('Previous')) return '&laquo;';
    if (label.includes('Next')) return '&raquo;';
    return label;
};
</script>

<template>
    <Head title="Questions" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Banque de questions</h2>
                    <p class="text-xs text-slate-500 font-medium">Gérez votre bibliothèque de questions interactives.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('admin.questions.export', { search, type, domain_id: domainId, difficulty })"
                        class="bg-white text-slate-900 border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50 font-bold text-xs shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Exporter
                    </a>
                    <button @click="showImportModal = true"
                        class="bg-white text-slate-900 border border-slate-200 px-4 py-2 rounded-lg hover:bg-slate-50 font-bold text-xs shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Importer
                    </button>
                    <Link :href="route('admin.questions.create')" 
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 font-bold text-xs shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 w-fit">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nouvelle
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Advanced Filters -->
                <div class="premium-card p-4 glass-card grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    <div class="md:col-span-1">
                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Recherche</label>
                        <div class="relative">
                            <input v-model="search" type="text" placeholder="Énoncé..." 
                                class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700 pl-9" />
                            <svg v-if="!filtering" class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <svg v-else class="absolute left-3 top-1/2 -translate-y-1/2 size-3.5 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Type</label>
                        <select v-model="type" class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
                            <option value="">Tous les types</option>
                            <option value="mcq">QCM</option>
                            <option value="text">Texte libre</option>
                            <option value="code">Code</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Domaine</label>
                        <select v-model="domainId" class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
                            <option value="">Tous les domaines</option>
                            <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Difficulté</label>
                        <select v-model="difficulty" class="w-full bg-slate-50 border-none rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-700">
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
                        <table class="w-full text-[13px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="px-6 py-4 text-left border-b border-slate-100 uppercase">Énoncé de la question</th>
                                    <th class="px-6 py-4 text-left border-b border-slate-100 uppercase">Type & Thématique</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100 uppercase">Difficulté</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100 uppercase">Points</th>
                                    <th class="px-6 py-4 text-right border-b border-slate-100 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="questions.data.length === 0">
                                    <td colspan="4" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucune question ne correspond aux critères</td>
                                </tr>
                                <tr v-for="q in questions.data" :key="q.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-6 py-4 max-w-sm">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-[13px] mb-0.5 line-clamp-1 group-hover:text-indigo-600 transition-colors">{{ q.statement }}</span>
                                            <div class="flex items-center gap-2">
                                                <span v-if="q.type === 'mcq'" class="text-[9px] font-bold text-indigo-500 bg-indigo-50 px-1.5 py-0.5 rounded uppercase tracking-tighter">{{ q.choices.length }} options</span>
                                                <span v-else class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Question ouverte</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <div v-html="typeIcon[q.type]" :class="{
                                                    'text-indigo-500': q.type === 'mcq',
                                                    'text-fuchsia-500': q.type === 'text',
                                                    'text-amber-500': q.type === 'code'
                                                }" class="[&>svg]:size-3.5"></div>
                                                <span class="text-slate-700 font-bold text-[11px]">{{ typeLabel[q.type] }}</span>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <span v-for="d in q.domains" :key="d.id" class="text-[9px] font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded uppercase tracking-tighter">
                                                    {{ d.name }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="{
                                            'bg-emerald-50 text-emerald-600 border-emerald-100': q.difficulty === 'easy',
                                            'bg-amber-50 text-amber-600 border-amber-100': q.difficulty === 'medium',
                                            'bg-rose-50 text-rose-600 border-rose-100': q.difficulty === 'hard'
                                        }" class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase tracking-wider border">
                                            {{ difficultyLabel[q.difficulty] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="text-xs font-black text-slate-700 bg-slate-100 px-2 py-0.5 rounded-lg">
                                                {{ q.points }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <button @click="openDetails(q)" 
                                                class="size-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all"
                                                title="Détails">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <button @click="openPreview(q)" 
                                                class="size-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all"
                                                title="Tester">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                            <Link :href="route('admin.questions.edit', q.id)" 
                                                class="size-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all"
                                                title="Modifier">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </Link>
                                            <button @click="deleteQuestion(q.id)" 
                                                class="size-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all"
                                                title="Supprimer">
                                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
                                v-html="cleanLabel(link.label)"
                                :class="[
                                    'h-8 min-w-[2rem] px-2 flex items-center justify-center text-xs font-bold rounded-lg transition-all',
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
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Points</span>
                                <span class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-xs font-black">{{ selectedQuestion.points }} pts</span>
                            </div>
                            <div class="col-span-1">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Domaines</span>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="d in selectedQuestion.domains" :key="d.id" class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-bold">{{ d.name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Statement & Image -->
                        <div>
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Énoncé</span>
                            <div v-if="selectedQuestion.image_path" class="mb-4">
                                <img :src="`/storage/${selectedQuestion.image_path}`" class="max-h-64 rounded-2xl border border-slate-100 shadow-sm" />
                            </div>
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
                                    <div class="flex flex-col gap-2">
                                        <img v-if="choice.image_path" :src="`/storage/${choice.image_path}`" class="max-h-32 rounded-lg border border-slate-200/50 shadow-sm" />
                                        <span class="font-bold">{{ choice.text }}</span>
                                    </div>
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
                        <!-- Explanation -->
                        <div v-if="selectedQuestion.explanation" class="mt-8">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Explication / Correction</span>
                            <div class="p-6 bg-emerald-50 rounded-2xl border border-emerald-100 text-sm font-medium text-emerald-800 leading-relaxed shadow-sm">
                                {{ selectedQuestion.explanation }}
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
                                <span v-if="selectedQuestion.type === 'mcq'" class="px-4 py-1.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200 text-[10px] font-black uppercase tracking-wider">
                                    {{ selectedQuestion.multiple_answers ? 'Plusieurs réponses possibles' : 'Réponse unique' }}
                                </span>
                            </div>

                            <div v-if="selectedQuestion.image_path" class="mb-6">
                                <img :src="`/storage/${selectedQuestion.image_path}`" class="max-h-80 rounded-2xl border border-slate-100 shadow-lg" />
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
                                            ? 'border-indigo-600 bg-indigo-50 text-indigo-900 shadow-md'
                                            : 'border-slate-100 bg-slate-50 hover:border-slate-300 hover:bg-white'
                                    ]">
                                    <div :class="[
                                        'size-6 rounded flex items-center justify-center border-2 transition-colors shrink-0',
                                        (previewAnswers[selectedQuestion.id] || []).includes(choice.id)
                                            ? 'bg-indigo-600 border-indigo-600 text-white'
                                            : 'bg-white border-slate-200 text-slate-300'
                                    ]">
                                        <svg v-if="(previewAnswers[selectedQuestion.id] || []).includes(choice.id)" xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><path d="M5 13l4 4L19 7"/></svg>
                                        <span v-else class="text-[10px] font-bold">{{ String.fromCharCode(65 + idx) }}</span>
                                    </div>
                                    <div class="flex flex-col gap-3">
                                        <img v-if="choice.image_path" :src="`/storage/${choice.image_path}`" class="max-w-[200px] rounded-lg border border-slate-200/50 shadow-sm" />
                                        <span v-if="choice.text" class="text-base font-bold">{{ choice.text }}</span>
                                    </div>
                                </button>
                                
                                <div class="mt-8 pt-8 border-t border-slate-50 flex items-center justify-between">
                                    <button @click="validatePreviewAnswer"
                                        class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all active:scale-[0.98]">
                                        Valider ma réponse
                                    </button>

                                    <div v-if="previewValidated" class="flex items-center gap-4 animate-reveal">
                                        <span :class="previewSuccess ? 'text-emerald-600' : 'text-rose-600'" class="text-sm font-black uppercase tracking-widest flex items-center gap-2">
                                            <div :class="previewSuccess ? 'bg-emerald-500' : 'bg-rose-500'" class="size-2 rounded-full"></div>
                                            {{ previewSuccess ? 'Correct !' : 'Incorrect' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Text Preview -->
                            <div v-if="selectedQuestion.type === 'text'" class="space-y-8">
                                <textarea rows="8" placeholder="Réponse du candidat..." 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-[2rem] p-8 text-lg font-medium text-slate-700 focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 focus:bg-white transition-all resize-none"></textarea>
                                
                                <div class="flex items-center justify-between">
                                    <button @click="validatePreviewAnswer"
                                        class="bg-slate-900 text-white px-8 py-3 rounded-2xl font-bold text-sm hover:bg-slate-800 transition-all active:scale-[0.98]">
                                        Soumettre ma réponse
                                    </button>

                                    <div v-if="previewValidated" class="flex items-center gap-4 animate-reveal">
                                        <span class="text-emerald-600 text-sm font-black uppercase tracking-widest flex items-center gap-2">
                                            <div class="bg-emerald-500 size-2 rounded-full"></div>
                                            Réponse enregistrée
                                        </span>
                                    </div>
                                </div>
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
        <!-- Import Modal -->
        <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-[70] p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-slate-900">Importer des questions</h3>
                    <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-6">
                    <div class="p-6 border-2 border-dashed border-slate-200 rounded-2xl text-center hover:border-indigo-400 transition-colors cursor-pointer relative">
                        <input type="file" @input="importForm.file = $event.target.files[0]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept=".csv,.xlsx,.xls" />
                        <div class="space-y-2">
                            <svg class="size-10 text-slate-300 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <div class="text-sm text-slate-600 font-medium">
                                <span v-if="!importForm.file">Cliquez ou glissez un fichier Excel/CSV</span>
                                <span v-else class="text-indigo-600 font-bold">{{ importForm.file.name }}</span>
                            </div>
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Format accepté : CSV, XLSX</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl flex items-start gap-3">
                        <svg class="size-5 text-indigo-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                Utilisez notre modèle pour structurer vos questions QCM (énoncés, thèmes, choix, réponses correctes).
                            </p>
                            <a :href="route('admin.questions.template')" class="text-indigo-600 text-xs font-bold hover:underline mt-1 inline-block">Télécharger le modèle (.csv)</a>
                        </div>
                    </div>

                    <div v-if="importForm.errors.file" class="text-rose-500 text-xs font-bold animate-shake">
                        {{ importForm.errors.file }}
                    </div>

                    <div class="flex gap-4">
                        <button type="button" @click="showImportModal = false"
                            class="flex-1 px-6 py-3 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">
                            Annuler
                        </button>
                        <button type="submit" :disabled="importForm.processing || !importForm.file"
                            class="flex-1 px-6 py-3 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all disabled:opacity-50 shadow-xl shadow-slate-200">
                            <span v-if="!importForm.processing">Lancer l'import</span>
                            <svg v-else class="size-4 animate-spin mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
