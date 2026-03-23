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
    domain_id: props.question?.domain_id || '',
    academic_level_id: props.question?.academic_level_id || '',
    theme_id: props.question?.theme_id || '',
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
    const domain = props.domains.find(d => d.id == form.domain_id);
    return domain?.themes || [];
});

watch(() => form.domain_id, () => { form.theme_id = ''; });

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
            <h2 class="text-xl font-semibold text-gray-800">
                {{ question ? 'Modifier la question' : 'Nouvelle question' }}
            </h2>
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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domaine</label>
                            <select v-model="form.domain_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sélectionner...</option>
                                <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                            <p v-if="form.errors.domain_id" class="text-red-500 text-xs mt-1">{{ form.errors.domain_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
                            <select v-model="form.academic_level_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sélectionner...</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thème</label>
                            <select v-model="form.theme_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sélectionner...</option>
                                <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </select>
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
                    <div class="flex gap-3 justify-end pt-4 border-t border-gray-100">
                        <a :href="route('admin.questions.index')" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Annuler
                        </a>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ question ? 'Mettre à jour' : 'Créer la question' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
