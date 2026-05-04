<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ domains: Array });

const search = ref('');
const filteredDomains = ref(props.domains);

const filterDomains = () => {
    filteredDomains.value = props.domains.filter(domain => {
        const matchesName = domain.name.toLowerCase().includes(search.value.toLowerCase());
        const matchesTheme = domain.themes.some(t => t.name.toLowerCase().includes(search.value.toLowerCase()));
        return matchesName || matchesTheme;
    });
};

const showModal = ref(false);
const editingDomain = ref(null);

const form = useForm({ name: '', description: '', color: '#3B82F6' });
const themeForm = useForm({ name: '', domain_id: null });
const showThemeModal = ref(false);
const editingTheme = ref(null);

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
    editingTheme.value = null;
    showThemeModal.value = true;
}

function openEditTheme(theme) {
    themeForm.name = theme.name;
    themeForm.domain_id = null;
    editingTheme.value = theme;
    showThemeModal.value = true;
}

function detachTheme(domain, theme) {
    if (confirm(`Retirer le thème "${theme.name}" de ce domaine ?`)) {
        themeForm.delete(route('admin.domains.themes.detach', { domain: domain.id, theme: theme.id }));
    }
}

function deleteThemeGlobally(theme) {
    if (confirm(`ATTENTION : Supprimer définitivement le thème "${theme.name}" de TOUS les domaines ?`)) {
        themeForm.delete(route('admin.themes.destroy', theme.id));
    }
}

function submitTheme() {
    if (editingTheme.value) {
        themeForm.put(route('admin.themes.update', editingTheme.value.id), {
            onSuccess: () => { showThemeModal.value = false; },
        });
    } else {
        themeForm.post(route('admin.domains.themes.store', themeForm.domain_id), {
            onSuccess: () => { showThemeModal.value = false; },
        });
    }
}

import { watch } from 'vue';
watch(() => props.domains, () => {
    filterDomains();
}, { deep: true });
</script>

<template>
    <Head title="Domaines" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">Domaines & Thèmes</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Structurez votre banque de questions par spécialités métier.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative group">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input v-model="search" @input="filterDomains" type="text" placeholder="Rechercher un domaine ou un thème..." 
                            class="w-full sm:w-80 bg-white border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder:text-slate-400 font-medium" />
                    </div>
                    <button @click="openCreate" 
                        class="bg-indigo-600 text-white px-6 py-3 rounded-2xl hover:bg-indigo-700 font-bold text-sm shadow-xl shadow-indigo-100 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nouveau domaine
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="filteredDomains.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="domain in filteredDomains" :key="domain.id" 
                        class="premium-card p-0 flex flex-col group hover:shadow-2xl transition-all duration-500 border border-slate-100 overflow-hidden bg-white">
                        
                        <!-- Card Header with Accent Color -->
                        <div class="h-2 w-full transition-all group-hover:h-3" :style="{ backgroundColor: domain.color }"></div>
                        
                        <div class="p-8 flex-1 flex flex-col">
                            <div class="flex items-start justify-between mb-6">
                                <div class="flex flex-col gap-1">
                                    <h3 class="font-black text-xl text-slate-900 group-hover:text-indigo-600 transition-colors leading-tight">{{ domain.name }}</h3>
                                    <div class="flex items-center gap-2">
                                        <span class="size-1.5 rounded-full" :style="{ backgroundColor: domain.color }"></span>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Spécialité</span>
                                    </div>
                                </div>
                                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="openEdit(domain)" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                                        <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-sm text-slate-500 mb-8 font-medium leading-relaxed line-clamp-2 italic">{{ domain.description || 'Précisez les objectifs de ce domaine.' }}</p>
                            
                            <!-- Stats Badges -->
                            <div class="flex items-center gap-3 mb-8">
                                <div class="bg-slate-50 rounded-2xl px-4 py-3 border border-slate-100 flex-1 group/stat hover:bg-white hover:border-indigo-100 transition-all">
                                    <div class="text-sm font-black text-slate-900">{{ domain.themes_count }}</div>
                                    <div class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Thèmes</div>
                                </div>
                                <div class="bg-slate-50 rounded-2xl px-4 py-3 border border-slate-100 flex-1 group/stat hover:bg-white hover:border-indigo-100 transition-all">
                                    <div class="text-sm font-black text-slate-900">{{ domain.questions_count }}</div>
                                    <div class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Questions</div>
                                </div>
                            </div>
    
                            <div class="mt-auto pt-8 border-t border-slate-50 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Lexique métier</h4>
                                    <button @click="openTheme(domain)" 
                                        class="size-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <div v-for="theme in domain.themes" :key="theme.id"
                                        class="group/theme relative flex items-center gap-2 bg-white text-slate-600 text-[11px] font-bold pl-4 pr-3 py-2 rounded-xl border border-slate-200 hover:border-indigo-600 hover:text-indigo-600 transition-all">
                                        {{ theme.name }}
                                        <div class="flex items-center gap-1.5 border-l border-slate-100 pl-2 ml-1 opacity-0 group-hover/theme:opacity-100 transition-opacity translate-x-2 group-hover/theme:translate-x-0">
                                            <button @click="openEditTheme(theme)" class="p-1 hover:bg-indigo-50 rounded-lg transition-colors">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                            </button>
                                             <button @click="detachTheme(domain, theme)" title="Retirer du domaine" class="p-1 hover:bg-rose-50 hover:text-rose-600 rounded-lg transition-colors">
                                                 <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                             </button>
                                             <button @click="deleteThemeGlobally(theme)" title="Supprimer définitivement" class="p-1 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors ml-1 border border-transparent hover:border-red-200">
                                                 <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                             </button>
                                        </div>
                                    </div>
                                    <div v-if="domain.themes.length === 0" 
                                        class="w-full py-6 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-2xl gap-2">
                                        <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">Aucun sous-thème</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-[40px] border border-dashed border-slate-200">
                    <div class="size-20 rounded-3xl bg-slate-50 text-slate-300 flex items-center justify-center mb-6">
                        <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">Aucun résultat trouvé</h3>
                    <p class="text-sm text-slate-500 mt-2">Essayez d'ajuster votre recherche ou créez un nouveau domaine.</p>
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
                    <h3 class="text-xl font-bold text-slate-900 mb-1">{{ editingTheme ? 'Modifier le' : 'Nouveau' }} Thème</h3>
                    <p class="text-xs text-slate-500 font-medium">{{ editingTheme ? 'Modifiez l\'intitulé de ce thème.' : 'Ajouter une sous-catégorie spécifique.' }}</p>
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
                            {{ editingTheme ? 'Mettre à jour' : 'Ajouter' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
