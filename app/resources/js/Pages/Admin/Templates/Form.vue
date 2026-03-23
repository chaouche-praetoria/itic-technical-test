<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({ template: Object, domains: Array, levels: Array });

const form = useForm({
    name: props.template?.name || '',
    description: props.template?.description || '',
    domain_id: props.template?.domain_id || '',
    academic_level_id: props.template?.academic_level_id || '',
    duration_minutes: props.template?.duration_minutes || 60,
    question_timer: props.template?.question_timer || false,
    question_time_seconds: props.template?.question_time_seconds || null,
    single_attempt: props.template?.single_attempt ?? true,
    link_expiry_hours: props.template?.link_expiry_hours || 72,
    rules: props.template?.rules?.length
        ? props.template.rules.map(r => ({ theme_id: r.theme_id, question_type: r.question_type, difficulty: r.difficulty || '', count: r.count }))
        : [{ theme_id: '', question_type: 'mcq', difficulty: '', count: 5 }],
});

const themes = computed(() => {
    const domain = props.domains.find(d => d.id == form.domain_id);
    return domain?.themes || [];
});

watch(() => form.domain_id, () => {
    form.rules.forEach(r => r.theme_id = '');
});

function addRule() {
    form.rules.push({ theme_id: '', question_type: 'mcq', difficulty: '', count: 5 });
}
function removeRule(i) {
    form.rules.splice(i, 1);
}

function submit() {
    if (props.template) {
        form.put(route('admin.templates.update', props.template.id));
    } else {
        form.post(route('admin.templates.store'));
    }
}
</script>

<template>
    <Head :title="template ? 'Modifier le template' : 'Nouveau template'" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ template ? 'Modifier' : 'Nouveau' }} template</h2>
                    <p class="text-sm text-slate-500 font-medium">Définissez les règles de génération automatique du test.</p>
                </div>
            </div>
        </template>
        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom du template</label>
                            <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Domaine</label>
                            <select v-model="form.domain_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sélectionner...</option>
                                <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
                            <select v-model="form.academic_level_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Sélectionner...</option>
                                <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée (minutes)</label>
                            <input v-model.number="form.duration_minutes" type="number" min="5" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expiration du lien (heures)</label>
                            <input v-model.number="form.link_expiry_hours" type="number" min="1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" v-model="form.single_attempt" class="text-indigo-600" />
                                Tentative unique
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" v-model="form.question_timer" class="text-indigo-600" />
                                Timer par question
                            </label>
                        </div>
                        <div v-if="form.question_timer">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Temps par question (secondes)</label>
                            <div class="flex gap-2 flex-wrap mb-2">
                                <button v-for="preset in [30, 60, 120, 180, 300]" :key="preset" type="button"
                                    @click="form.question_time_seconds = preset"
                                    :class="form.question_time_seconds === preset ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    class="px-3 py-1 rounded-lg text-xs font-medium transition-colors">
                                    {{ preset < 60 ? preset + 's' : (preset / 60) + 'min' }}
                                </button>
                            </div>
                            <input v-model.number="form.question_time_seconds" type="number" min="10" placeholder="Saisie libre (secondes)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>

                    <!-- Rules -->
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-sm font-medium text-gray-700">Règles de composition</label>
                            <button type="button" @click="addRule" class="text-indigo-600 text-sm hover:underline">+ Ajouter une règle</button>
                        </div>
                        <div v-if="themes.length === 0" class="text-sm text-amber-600 bg-amber-50 px-4 py-2 rounded-lg mb-3">
                            Sélectionnez d'abord un domaine pour accéder aux thèmes.
                        </div>
                        <div v-for="(rule, i) in form.rules" :key="i" class="flex gap-3 items-end mb-3 p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600 mb-1">Thème</label>
                                <select v-model="rule.theme_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Thème...</option>
                                    <option v-for="t in themes" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="block text-xs text-gray-600 mb-1">Type</label>
                                <select v-model="rule.question_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="mcq">QCM</option>
                                    <option value="text">Texte</option>
                                    <option value="code">Code</option>
                                </select>
                            </div>
                            <div class="w-28">
                                <label class="block text-xs text-gray-600 mb-1">Difficulté</label>
                                <select v-model="rule.difficulty" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">Toutes</option>
                                    <option value="easy">Facile</option>
                                    <option value="medium">Moyen</option>
                                    <option value="hard">Difficile</option>
                                </select>
                            </div>
                            <div class="w-20">
                                <label class="block text-xs text-gray-600 mb-1">Nombre</label>
                                <input v-model.number="rule.count" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                            </div>
                            <button v-if="form.rules.length > 1" type="button" @click="removeRule(i)" class="text-red-500 hover:text-red-700 text-xl pb-2">×</button>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end pt-8 border-t border-slate-100">
                        <Link :href="route('admin.templates.index')" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all active:scale-[0.98]">Annuler</Link>
                        <button type="submit" :disabled="form.processing" class="px-8 py-2.5 text-sm font-bold text-white bg-slate-900 rounded-xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ template ? 'Enregistrer les modifications' : 'Créer le template' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
