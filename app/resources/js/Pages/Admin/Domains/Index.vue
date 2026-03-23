<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ domains: Array });

const showModal = ref(false);
const editingDomain = ref(null);

const form = useForm({ name: '', description: '', color: '#3B82F6' });
const themeForm = useForm({ name: '', domain_id: null });
const showThemeModal = ref(false);

function openCreate() {
    form.reset();
    editingDomain.value = null;
    showModal.value = true;
}

function openEdit(domain) {
    form.name = domain.name;
    form.description = domain.description || '';
    form.color = domain.color;
    editingDomain.value = domain;
    showModal.value = true;
}

function submit() {
    if (editingDomain.value) {
        form.put(route('admin.domains.update', editingDomain.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.domains.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function openTheme(domain) {
    themeForm.name = '';
    themeForm.domain_id = domain.id;
    showThemeModal.value = true;
}

function submitTheme() {
    themeForm.post(route('admin.domains.themes.store', themeForm.domain_id), {
        onSuccess: () => { showThemeModal.value = false; },
    });
}
</script>

<template>
    <Head title="Domaines" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Domaines & Thèmes</h2>
                <button @click="openCreate" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    + Nouveau domaine
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="domain in domains" :key="domain.id" class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full" :style="{ backgroundColor: domain.color }"></div>
                                <h3 class="font-semibold text-gray-800">{{ domain.name }}</h3>
                            </div>
                            <button @click="openEdit(domain)" class="text-gray-400 hover:text-gray-600 text-sm">Modifier</button>
                        </div>
                        <p class="text-sm text-gray-500 mb-3">{{ domain.description || 'Aucune description' }}</p>
                        <div class="flex gap-4 text-xs text-gray-400 mb-4">
                            <span>{{ domain.themes_count }} thèmes</span>
                            <span>{{ domain.questions_count }} questions</span>
                        </div>
                        <div class="border-t pt-3">
                            <p class="text-xs font-medium text-gray-600 mb-2">Thèmes</p>
                            <div class="flex flex-wrap gap-1 mb-2">
                                <span v-for="theme in domain.themes" :key="theme.id"
                                    class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                                    {{ theme.name }}
                                </span>
                            </div>
                            <button @click="openTheme(domain)" class="text-indigo-600 text-xs hover:underline">+ Ajouter un thème</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Domain Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">{{ editingDomain ? 'Modifier' : 'Créer' }} un domaine</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="form.description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                        <input v-model="form.color" type="color" class="h-10 w-20 border border-gray-300 rounded-lg" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                            {{ editingDomain ? 'Mettre à jour' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Theme Modal -->
        <div v-if="showThemeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-sm">
                <h3 class="text-lg font-semibold mb-4">Ajouter un thème</h3>
                <form @submit.prevent="submitTheme" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du thème</label>
                        <input v-model="themeForm.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="showThemeModal = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Annuler</button>
                        <button type="submit" :disabled="themeForm.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
