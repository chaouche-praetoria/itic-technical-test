<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
import CodeEditor from '@/Components/CodeEditor.vue';
import axios from 'axios';
import { LANGUAGE_TEMPLATES, TEST_TEMPLATES } from '@/Constants/questionTemplates';

const props = defineProps({
    question: Object,
    domains: Array,
    levels: Array,
});

const form = useForm({
    type: props.question?.type || 'mcq',
    domain_ids: props.question?.domains?.map(d => d.id) || [],
    academic_level_id: props.question?.academic_level_id || '',
    theme_ids: props.question?.themes?.map(t => t.id) || [],
    difficulty: props.question?.difficulty || 'easy',
    statement: props.question?.statement || '',
    multiple_answers: props.question?.multiple_answers || false,
    unit_tests: props.question?.unit_tests || '',
    initial_code: props.question?.initial_code || '',
    default_language: props.question?.default_language || 'javascript',
    choices: props.question?.choices?.length
        ? props.question.choices.map(c => ({ id: c.id, text: c.text, is_correct: c.is_correct, image_path: c.image_path, image: null }))
        : [{ text: '', is_correct: false, image: null }, { text: '', is_correct: false, image: null }],
    explanation: props.question?.explanation || '',
    points: props.question?.points || 1,
    image: null,
});

const imagePreview = ref(props.question?.image_path ? `/storage/${props.question.image_path}` : null);
const choicePreviews = ref({});

watch(() => form.image, (file) => {
    if (file) imagePreview.value = URL.createObjectURL(file);
    else imagePreview.value = props.question?.image_path ? `/storage/${props.question.image_path}` : null;
});

const onChoiceImageChange = (index, event) => {
    const file = event.target.files[0];
    if (file) {
        form.choices[index].image = file;
        choicePreviews.value[index] = URL.createObjectURL(file);
    }
};

const getChoiceImage = (choice, index) => {
    if (choicePreviews.value[index]) return choicePreviews.value[index];
    if (choice.image_path) return `/storage/${choice.image_path}`;
    return null;
};

const themes = computed(() => {
    return props.domains
        .filter(d => form.domain_ids.includes(d.id))
        .flatMap(d => d.themes)
        .filter((theme, index, self) => self.findIndex(t => t.id === theme.id) === index);
});

watch(() => form.domain_ids, (newIds) => {
    const validThemeIds = themes.value.map(t => t.id);
    form.theme_ids = form.theme_ids.filter(id => validThemeIds.includes(id));
}, { deep: true });

// Real-time Validation
const validationErrors = computed(() => {
    const errors = [];
    if (form.type === 'mcq') {
        const hasCorrect = form.choices.some(c => c.is_correct);
        if (!hasCorrect) errors.push("Au moins une réponse correcte est requise.");

        // Check for duplicates
        const texts = form.choices.map(c => c.text?.trim().toLowerCase()).filter(t => t);
        const hasDuplicates = texts.some((t, i) => texts.indexOf(t) !== i);
        if (hasDuplicates) errors.push("Les options de réponse doivent être uniques.");
    }
    if (form.type === 'code') {
        if (!form.unit_tests.trim()) errors.push("Les tests unitaires sont recommandés pour valider le code.");
    }
    return errors;
});

const isDuplicate = (text) => {
    if (!text?.trim()) return false;
    const t = text.trim().toLowerCase();
    return form.choices.filter(c => c.text?.trim().toLowerCase() === t).length > 1;
};

const isDirty = computed(() => form.isDirty);

// Warn before leaving if form is dirty
const onBeforeUnload = (e) => {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', onBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', onBeforeUnload);
});

router.on('before', (event) => {
    if (isDirty.value && !confirm('Vous avez des modifications non enregistrées. Voulez-vous vraiment quitter ?')) {
        event.preventDefault();
    }
});

const domainSearch = ref('');
const themeSearch = ref('');

const filteredDomains = computed(() => {
    if (!domainSearch.value) return props.domains;
    const search = domainSearch.value.toLowerCase();
    return props.domains.filter(d => d.name.toLowerCase().includes(search));
});

const filteredThemes = computed(() => {
    let baseThemes = themes.value;
    if (!themeSearch.value) return baseThemes;
    const search = themeSearch.value.toLowerCase();
    return baseThemes.filter(t => t.name.toLowerCase().includes(search));
});

// Drag and Drop Logic
const draggedItemIndex = ref(null);

function onDragStart(index) {
    draggedItemIndex.value = index;
}

function onDrop(index) {
    if (draggedItemIndex.value === null) return;
    const item = form.choices.splice(draggedItemIndex.value, 1)[0];
    form.choices.splice(index, 0, item);
    draggedItemIndex.value = null;
}

watch([() => form.default_language, () => form.type], ([newLang, newType]) => {
    if (newType === 'code') {
        const currentCode = (form.initial_code || '').trim();
        const isTemplate = Object.values(LANGUAGE_TEMPLATES).some(t => t.trim() === currentCode);
        
        if (!currentCode || isTemplate) {
            form.initial_code = LANGUAGE_TEMPLATES[newLang] || "";
        }

        const currentTests = (form.unit_tests || '').trim();
        const isTestTemplate = Object.values(TEST_TEMPLATES).some(t => t.trim() === currentTests);

        if (!currentTests || isTestTemplate) {
            form.unit_tests = TEST_TEMPLATES[newLang] || "";
        }
    }
});

// Auto-test with debounce
let testTimeout = null;
watch([() => form.initial_code, () => form.unit_tests], () => {
    if (form.type !== 'code') return;
    
    if (testTimeout) clearTimeout(testTimeout);
    
    testTimeout = setTimeout(() => {
        if (form.initial_code && form.unit_tests) {
            testCode();
        }
    }, 2000);
});

function addChoice() {
    form.choices.push({ text: '', is_correct: false, image: null });
}
function removeChoice(i) {
    form.choices.splice(i, 1);
    delete choicePreviews.value[i];
}

function submit() {
    if (validationErrors.value.some(err => !err.includes('recommandés'))) {
        return;
    }
    
    if (props.question) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('admin.questions.update', props.question.id));
    } else {
        form.post(route('admin.questions.store'));
    }
}

const testLoading = ref(false);
const testResult = ref(null);

async function testCode() {
    testLoading.value = true;
    testResult.value = null;
    try {
        const res = await axios.post(route('admin.questions.test'), {
            code: form.initial_code,
            language: form.default_language,
            unit_tests: form.unit_tests,
        });
        testResult.value = res.data;
    } catch (e) {
        testResult.value = { success: false, error: "Erreur lors de l'appel au service de test." };
    } finally {
        testLoading.value = false;
    }
}
// MCQ Test Logic
const testAnswers = ref([]);
const testValidated = ref(false);
const testSuccess = ref(false);

function toggleTestChoice(choiceIndex) {
    if (form.multiple_answers) {
        const idx = testAnswers.value.indexOf(choiceIndex);
        if (idx === -1) testAnswers.value.push(choiceIndex);
        else testAnswers.value.splice(idx, 1);
    } else {
        testAnswers.value = [choiceIndex];
    }
}

function validateMCQTest() {
    testValidated.value = true;
    const correctIndices = form.choices
        .map((c, idx) => c.is_correct ? idx : null)
        .filter(idx => idx !== null)
        .sort();
    const selectedIndices = [...testAnswers.value].sort();
    testSuccess.value = JSON.stringify(correctIndices) === JSON.stringify(selectedIndices);
}

watch(() => form.choices, () => {
    testAnswers.value = [];
    testValidated.value = false;
}, { deep: true });

watch(() => form.multiple_answers, () => {
    testAnswers.value = [];
    testValidated.value = false;
});
</script>

<template>
    <Head :title="question ? 'Modifier la question' : 'Nouvelle question'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                        {{ question ? 'Modifier la question' : 'Nouvelle question' }}
                    </h2>
                    <p class="text-sm text-slate-500 font-medium">Configurez l'énoncé et les critères d'évaluation.</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-6">

                    <!-- Validation Alerts -->
                    <div v-if="validationErrors.length" class="space-y-2">
                        <div v-for="err in validationErrors" :key="err" class="flex items-center gap-2 p-3 bg-amber-50 text-amber-700 rounded-xl border border-amber-100 text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                            {{ err }}
                        </div>
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de question</label>
                        <div class="flex gap-3">
                            <label v-for="t in [{val:'mcq',label:'QCM'},{val:'text',label:'Texte libre'},{val:'code',label:'Code'}]" :key="t.val"
                                class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.type" :value="t.val" class="text-indigo-600" />
                                <span class="text-sm">{{ t.label }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</p>
                    </div>

                    <!-- Domain, Level, Theme -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-slate-700">Domaines</label>
                                <div class="relative">
                                    <input type="text" v-model="domainSearch" placeholder="Rechercher..." 
                                        class="text-xs border-slate-200 rounded-lg px-2 py-1 w-32 focus:ring-indigo-500 focus:border-indigo-500" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-1 border border-slate-200 rounded-xl p-2 max-h-56 overflow-y-auto bg-slate-50/50 shadow-inner">
                                <label v-for="d in filteredDomains" :key="d.id" 
                                    class="flex items-center gap-3 cursor-pointer text-sm p-2 rounded-lg transition-all"
                                    :class="form.domain_ids.includes(d.id) ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'hover:bg-white text-slate-600'">
                                    <input type="checkbox" v-model="form.domain_ids" :value="d.id" class="text-indigo-600 rounded-md border-slate-300 focus:ring-indigo-500" />
                                    <span class="font-medium">{{ d.name }}</span>
                                </label>
                                <div v-if="filteredDomains.length === 0" class="text-xs text-slate-400 italic py-4 text-center">Aucun domaine trouvé</div>
                            </div>
                            <p v-if="form.errors.domain_ids" class="text-red-500 text-xs mt-1">{{ form.errors.domain_ids }}</p>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Niveau académique</label>
                                <select v-model="form.academic_level_id" required class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                    :class="{'border-red-500': form.errors.academic_level_id}">
                                    <option value="">Sélectionner un niveau...</option>
                                    <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                                </select>
                                <p v-if="form.errors.academic_level_id" class="text-red-500 text-xs mt-1">{{ form.errors.academic_level_id }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-semibold text-slate-700">Thèmes</label>
                                    <div v-if="form.domain_ids.length" class="relative">
                                        <input type="text" v-model="themeSearch" placeholder="Filtrer..." 
                                            class="text-xs border-slate-200 rounded-lg px-2 py-1 w-24 focus:ring-indigo-500 focus:border-indigo-500" />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-1 border border-slate-200 rounded-xl p-2 max-h-56 overflow-y-auto bg-slate-50/50 shadow-inner" 
                                    :class="{'opacity-50 grayscale': !form.domain_ids.length}">
                                    <template v-if="filteredThemes.length">
                                        <label v-for="t in filteredThemes" :key="t.id" 
                                            class="flex items-center gap-3 cursor-pointer text-sm p-2 rounded-lg transition-all"
                                            :class="form.theme_ids.includes(t.id) ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'hover:bg-white text-slate-600'">
                                            <input type="checkbox" v-model="form.theme_ids" :value="t.id" class="text-indigo-600 rounded-md border-slate-300 focus:ring-indigo-500" />
                                            <span class="font-medium">{{ t.name }}</span>
                                        </label>
                                    </template>
                                    <div v-else class="text-xs text-slate-400 italic py-8 text-center px-4">
                                        {{ form.domain_ids.length ? 'Aucun thème correspondant' : "Sélectionnez un domaine pour voir les thèmes" }}
                                    </div>
                                </div>
                                <p v-if="form.errors.theme_ids" class="text-red-500 text-xs mt-1">{{ form.errors.theme_ids }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Difficulty & Points -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Difficulté</label>
                            <div class="flex gap-3">
                                <label v-for="d in [{val:'easy',label:'Facile'},{val:'medium',label:'Moyen'},{val:'hard',label:'Difficile'}]" :key="d.val"
                                    class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" v-model="form.difficulty" :value="d.val" class="text-indigo-600" />
                                    <span class="text-sm">{{ d.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.difficulty" class="text-red-500 text-xs mt-1">{{ form.errors.difficulty }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Points</label>
                            <input type="number" v-model="form.points" min="0" 
                                class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.points" class="text-red-500 text-xs mt-1">{{ form.errors.points }}</p>
                        </div>
                    </div>

                    <!-- Statement & Image -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Énoncé</label>
                            <textarea v-model="form.statement" rows="4" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                            <p v-if="form.errors.statement" class="text-red-500 text-xs mt-1">{{ form.errors.statement }}</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image d'illustration (optionnelle)</label>
                            <div class="flex items-center gap-6">
                                <div v-if="imagePreview" class="relative group">
                                    <img :src="imagePreview" 
                                        class="size-32 object-cover rounded-xl border border-slate-200" />
                                    <button type="button" @click="form.image = null" 
                                        class="absolute -top-2 -right-2 bg-rose-500 text-white p-1 rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                    </button>
                                </div>
                                <input type="file" @input="form.image = $event.target.files[0]" 
                                    class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer" />
                            </div>
                            <p v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</p>
                        </div>
                    </div>

                    <!-- MCQ Choices -->
                    <div v-if="form.type === 'mcq'" class="space-y-4">
                        <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                            <div>
                                <label class="block text-sm font-bold text-slate-800">Options de réponse</label>
                                <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">Cochez les réponses correctes</p>
                            </div>
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer hover:text-indigo-600 transition-colors">
                                <input type="checkbox" v-model="form.multiple_answers" class="text-indigo-600 rounded border-slate-300 focus:ring-indigo-500" />
                                Réponses multiples
                            </label>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(choice, i) in form.choices" :key="i" 
                                class="group flex items-start gap-3 p-3 bg-white border border-slate-200 rounded-xl transition-all hover:border-indigo-200 hover:shadow-sm"
                                :class="{'ring-2 ring-emerald-500/20 border-emerald-500 bg-emerald-50/30': choice.is_correct, 'opacity-50 border-dashed border-indigo-400': draggedItemIndex === i}"
                                draggable="true"
                                @dragstart="onDragStart(i)"
                                @dragover.prevent
                                @drop="onDrop(i)">
                                
                                <div class="mt-2.5 text-slate-300 group-hover:text-slate-400 cursor-grab active:cursor-grabbing">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                                </div>

                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 flex flex-col gap-2">
                                            <input type="text" v-model="choice.text" placeholder="Entrez le texte de la réponse..."
                                                class="w-full border-none bg-transparent p-0 text-sm focus:ring-0 placeholder:text-slate-400 font-medium transition-colors"
                                                :class="[
                                                    choice.is_correct ? 'text-emerald-900' : 'text-slate-700',
                                                    isDuplicate(choice.text) ? 'text-rose-600 bg-rose-50 px-2 py-0.5 rounded' : ''
                                                ]" />
                                            
                                            <!-- Choice Image Upload -->
                                            <div class="flex items-center gap-3">
                                                <div v-if="getChoiceImage(choice, i)" class="relative group/choice">
                                                    <img :src="getChoiceImage(choice, i)" class="size-16 object-cover rounded-lg border border-slate-100" />
                                                    <button type="button" @click="choice.image = null; choice.image_path = null; delete choicePreviews[i]" 
                                                        class="absolute -top-1 -right-1 bg-rose-500 text-white p-0.5 rounded-full opacity-0 group-hover/choice:opacity-100 transition-opacity">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                                    </button>
                                                </div>
                                                <input type="file" @change="onChoiceImageChange(i, $event)" accept="image/*"
                                                    class="text-[10px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-bold file:bg-slate-50 file:text-slate-600 hover:file:bg-slate-100 cursor-pointer" />
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="choice.is_correct" class="sr-only peer" />
                                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                                                <span class="ms-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 peer-checked:text-emerald-600">
                                                    {{ choice.is_correct ? 'Correct' : 'Incorrect' }}
                                                </span>
                                            </label>

                                            <button v-if="form.choices.length > 2" type="button" @click="removeChoice(i)"
                                                class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-all opacity-0 group-hover:opacity-100"
                                                title="Supprimer">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    <p v-if="form.errors[`choices.${i}.text`]" class="text-red-500 text-[10px] font-bold">{{ form.errors[`choices.${i}.text`] }}</p>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="addChoice" class="flex items-center justify-center gap-2 w-full py-2 border-2 border-dashed border-slate-200 rounded-xl text-xs font-bold text-slate-400 hover:border-indigo-300 hover:text-indigo-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Ajouter un choix de réponse
                        </button>

                        <!-- MCQ Test / Preview Section -->
                        <div class="mt-8 bg-slate-50 p-6 rounded-2xl border border-slate-200 space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-widest">Aperçu & Test du QCM</h4>
                                <span class="px-2 py-1 rounded-md text-[9px] font-black uppercase tracking-tighter shadow-sm"
                                    :class="form.multiple_answers ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600'">
                                    {{ form.multiple_answers ? 'Plusieurs réponses possibles' : 'Réponse unique' }}
                                </span>
                            </div>
                            
                            <div class="grid gap-2">
                                <button v-for="(choice, i) in form.choices" :key="i" type="button"
                                    @click="toggleTestChoice(i)"
                                    class="flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left"
                                    :class="[
                                        testAnswers.includes(i) 
                                            ? 'border-indigo-600 bg-indigo-50 text-indigo-900 shadow-sm' 
                                            : 'border-white bg-white hover:border-slate-200 text-slate-600'
                                    ]">
                                    <div class="size-5 rounded flex items-center justify-center border-2 transition-colors shrink-0"
                                        :class="[
                                            testAnswers.includes(i)
                                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                                : 'bg-white border-slate-200'
                                        ]">
                                        <svg v-if="testAnswers.includes(i)" xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><path d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <span class="text-sm font-medium">{{ choice.text || '(Champ vide)' }}</span>
                                </button>
                            </div>

                            <div class="pt-4 flex items-center gap-4">
                                <button type="button" @click="validateMCQTest" 
                                    class="px-6 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all active:scale-[0.98]">
                                    Valider le test
                                </button>
                                
                                <div v-if="testValidated" class="flex items-center gap-2 animate-reveal">
                                    <span :class="testSuccess ? 'text-emerald-600' : 'text-rose-600'" class="text-xs font-black uppercase tracking-widest flex items-center gap-2">
                                        <div :class="testSuccess ? 'bg-emerald-500' : 'bg-rose-500'" class="size-2 rounded-full"></div>
                                        {{ testSuccess ? 'Correct !' : 'Incorrect' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Code -->
                    <div v-if="form.type === 'code'" class="space-y-4 bg-slate-50 p-6 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Configuration Programmation</h3>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-medium text-gray-500">Langage :</label>
                                <select v-model="form.default_language" class="border border-gray-300 rounded-lg px-3 py-1 text-xs focus:ring-slate-500 focus:border-slate-500">
                                    <option v-for="lang in ['javascript', 'python', 'php', 'java', 'cpp']" :key="lang" :value="lang">
                                        {{ lang.charAt(0).toUpperCase() + lang.slice(1) }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-tight">Code de départ (Boilerplate)</label>
                            <CodeEditor 
                                v-model="form.initial_code" 
                                :language="form.default_language"
                                height="200px"
                                placeholder="// Squelette de code pour le candidat..."
                            />
                            <p class="text-[10px] text-slate-400 mt-1 italic">Ce code sera affiché par défaut dans l'éditeur du candidat.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-tight">Tests unitaires</label>
                            <CodeEditor 
                                v-model="form.unit_tests" 
                                :language="form.default_language"
                                height="250px"
                                placeholder="// Vos tests unitaires ici..."
                            />
                            <p class="text-[10px] text-slate-400 mt-1 italic">Utilisez 'PASS' ou 'FAIL' dans la console pour valider les tests.</p>
                        </div>

                        <div class="pt-4">
                            <button type="button" @click="testCode" :disabled="testLoading"
                                class="w-full py-3 bg-white border-2 border-slate-200 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all flex items-center justify-center gap-2">
                                <span v-if="testLoading" class="size-3 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
                                {{ testLoading ? 'Validation en cours...' : 'Tester la configuration' }}
                            </button>

                            <div v-if="testResult" class="mt-4 p-4 rounded-xl text-xs font-mono" :class="testResult.success ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100'">
                                <div class="font-bold mb-1">{{ testResult.success ? '✅ Succès' : '❌ Échec' }}</div>
                                <div v-if="testResult.passed !== undefined">{{ testResult.passed }} / {{ testResult.total }} tests passés</div>
                                <div v-if="testResult.error" class="mt-2 whitespace-pre-wrap opacity-80">{{ testResult.error }}</div>
                                <div v-if="testResult.output" class="mt-2 p-2 bg-black/5 rounded whitespace-pre-wrap truncate max-h-32">{{ testResult.output }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Explanation / Correction -->
                    <div class="pt-8 border-t border-slate-100">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Explication / Correction</label>
                        <p class="text-[10px] text-slate-500 uppercase font-bold tracking-wider mb-3">Cette explication sera montrée au candidat après sa réponse</p>
                        <textarea v-model="form.explanation" rows="3" placeholder="Détaillez la solution ou donnez des indices..."
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50/50"></textarea>
                        <p v-if="form.errors.explanation" class="text-red-500 text-xs mt-1">{{ form.errors.explanation }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 justify-end pt-8 border-t border-slate-100">
                        <Link :href="route('admin.questions.index')" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing || validationErrors.some(err => !err.includes('recommandés'))"
                            class="px-8 py-2.5 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.98]">
                            {{ question ? 'Enregistrer les modifications' : 'Créer la question' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
