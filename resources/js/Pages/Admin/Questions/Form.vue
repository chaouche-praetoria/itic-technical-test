<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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
    default_language: props.question?.default_language || 'javascript',
    choices: props.question?.choices?.length
        ? props.question.choices.map(c => ({ text: c.text, is_correct: c.is_correct }))
        : [{ text: '', is_correct: false }, { text: '', is_correct: false }],
});

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

function addChoice() {
    form.choices.push({ text: '', is_correct: false });
}
function removeChoice(i) {
    form.choices.splice(i, 1);
}

function submit() {
    if (props.question) {
        form.put(route('admin.questions.update', props.question.id));
    } else {
        form.post(route('admin.questions.store'));
    }
}
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Domaines</label>
                            <div class="grid grid-cols-1 gap-2 border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto bg-slate-50">
                                <label v-for="d in domains" :key="d.id" class="flex items-center gap-2 cursor-pointer text-sm hover:bg-white p-1 rounded transition-colors">
                                    <input type="checkbox" v-model="form.domain_ids" :value="d.id" class="text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">{{ d.name }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.domain_ids" class="text-red-500 text-xs mt-1">{{ form.errors.domain_ids }}</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Niveau académique</label>
                                <select v-model="form.academic_level_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Sélectionner...</option>
                                    <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Thèmes</label>
                                <div class="grid grid-cols-1 gap-2 border border-gray-300 rounded-lg p-3 max-h-48 overflow-y-auto bg-slate-50" :class="{'opacity-50': !form.domain_ids.length}">
                                    <template v-if="themes.length">
                                        <label v-for="t in themes" :key="t.id" class="flex items-center gap-2 cursor-pointer text-sm hover:bg-white p-1 rounded transition-colors">
                                            <input type="checkbox" v-model="form.theme_ids" :value="t.id" class="text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                            <span class="text-slate-600">{{ t.name }}</span>
                                        </label>
                                    </template>
                                    <div v-else class="text-xs text-slate-400 italic py-2">
                                        {{ form.domain_ids.length ? 'Aucun thème disponible' : "Sélectionnez d'abord un domaine" }}
                                    </div>
                                </div>
                                <p v-if="form.errors.theme_ids" class="text-red-500 text-xs mt-1">{{ form.errors.theme_ids }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Difficulty -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Difficulté</label>
                        <div class="flex gap-3">
                            <label v-for="d in [{val:'easy',label:'Facile'},{val:'medium',label:'Moyen'},{val:'hard',label:'Difficile'}]" :key="d.val"
                                class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" v-model="form.difficulty" :value="d.val" class="text-indigo-600" />
                                <span class="text-sm">{{ d.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Statement -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Énoncé</label>
                        <textarea v-model="form.statement" rows="4" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        <p v-if="form.errors.statement" class="text-red-500 text-xs mt-1">{{ form.errors.statement }}</p>
                    </div>

                    <!-- MCQ Choices -->
                    <div v-if="form.type === 'mcq'" class="space-y-3">
                        <div class="flex justify-between items-center">
                            <label class="block text-sm font-medium text-gray-700">Réponses possibles</label>
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" v-model="form.multiple_answers" class="text-indigo-600" />
                                Réponses multiples
                            </label>
                        </div>
                        <div v-for="(choice, i) in form.choices" :key="i" class="flex items-center gap-3">
                            <input type="text" v-model="choice.text" placeholder="Texte de la réponse" required
                                class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            <label class="flex items-center gap-1 text-sm text-gray-600 whitespace-nowrap">
                                <input type="checkbox" v-model="choice.is_correct" class="text-green-600" />
                                Correcte
                            </label>
                            <button v-if="form.choices.length > 2" type="button" @click="removeChoice(i)"
                                class="text-red-500 hover:text-red-700 text-lg leading-none">×</button>
                        </div>
                        <button type="button" @click="addChoice" class="text-indigo-600 text-sm hover:underline">+ Ajouter un choix</button>
                    </div>

                    <!-- Code -->
                    <div v-if="form.type === 'code'" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Langage par défaut</label>
                            <select v-model="form.default_language" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="java">Java</option>
                                <option value="cpp">C++</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tests unitaires</label>
                            <textarea v-model="form.unit_tests" rows="6" placeholder="// Code de tests..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4 justify-end pt-8 border-t border-slate-100">
                        <Link :href="route('admin.questions.index')" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing"
                            class="px-8 py-2.5 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ question ? 'Enregistrer les modifications' : 'Créer la question' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
