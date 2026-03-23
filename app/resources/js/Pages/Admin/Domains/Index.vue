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
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Domaines & Thèmes</h2>
                    <p class="text-sm text-slate-500 font-medium">Organisez vos bibliothèques de questions par spécialités.</p>
                </div>
                <button @click="openCreate" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouveau domaine
                </button>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="domain in domains" :key="domain.id" class="premium-card p-6 flex flex-col group hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4">
                                <div class="size-4 rounded-full shadow-sm ring-4 ring-slate-50" :style="{ backgroundColor: domain.color }"></div>
                                <h3 class="font-bold text-lg text-slate-800 group-hover:text-indigo-600 transition-colors">{{ domain.name }}</h3>
                            </div>
                            <button @click="openEdit(domain)" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                        </div>
                        
                        <p class="text-sm text-slate-500 mb-6 font-medium leading-relaxed">{{ domain.description || 'Aucune description fournie pour ce domaine.' }}</p>
                        
                        <div class="flex gap-4 mb-8">
                            <div class="flex flex-col">
                                <span class="text-lg font-bold text-slate-800">{{ domain.themes_count }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Thèmes</span>
                            </div>
                            <div class="w-px h-8 bg-slate-100 mx-2"></div>
                            <div class="flex flex-col">
                                <span class="text-lg font-bold text-slate-800">{{ domain.questions_count }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Questions</span>
                            </div>
                        </div>

                        <div class="mt-auto pt-6 border-t border-slate-50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Mots clés / Thèmes</span>
                                <button @click="openTheme(domain)" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-700 uppercase tracking-widest flex items-center gap-1 transition-colors">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Ajouter
                                </button>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="theme in domain.themes" :key="theme.id"
                                    class="bg-slate-50 text-slate-600 text-[10px] font-bold px-3 py-1.5 rounded-lg border border-slate-100 hover:bg-white hover:border-indigo-100 transition-all cursor-default">
                                    {{ theme.name }}
                                </span>
                                <span v-if="domain.themes.length === 0" class="text-xs text-slate-300 italic font-medium">Aucun sous-thème</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

    <!-- Domain Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6 text-center">
                    <h3 class="text-2xl font-bold text-slate-900">{{ editingDomain ? 'Modifier' : 'Nouveau' }} Domaine</h3>
                    <p class="text-sm text-slate-500 font-medium">Définissez une catégorie principale pour vos tests.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nom du domaine</label>
                        <input v-model="form.name" type="text" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="ex: Développement Web" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Description</label>
                        <textarea v-model="form.description" rows="3"
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="Décrivez brièvement ce domaine..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Identité visuelle (Couleur)</label>
                        <div class="flex items-center gap-4">
                            <input v-model="form.color" type="color" class="size-12 rounded-xl bg-slate-50 cursor-pointer overflow-hidden border-none p-1 shadow-sm" />
                            <span class="text-sm font-mono text-slate-400 uppercase tracking-wider">{{ form.color }}</span>
                        </div>
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ editingDomain ? 'Mettre à jour' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Theme Modal -->
    <Teleport to="body">
        <div v-if="showThemeModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-sm shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-slate-900 mb-1">Nouveau Thème</h3>
                    <p class="text-xs text-slate-500 font-medium">Ajouter une sous-catégorie spécifique.</p>
                </div>
                <form @submit.prevent="submitTheme" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Intitulé du thème</label>
                        <input v-model="themeForm.name" type="text" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="ex: React.js, Laravel, UX Design" />
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showThemeModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Fermer
                        </button>
                        <button type="submit" :disabled="themeForm.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
