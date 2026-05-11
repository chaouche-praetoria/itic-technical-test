<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    themes: Array,
    domains: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const editingTheme = ref(null);

const form = useForm({
    name: '',
    domain_ids: []
});

watch(search, debounce((value) => {
    router.get(route('admin.themes.index'), { search: value }, {
        preserveState: true,
        replace: true
    });
}, 300));

function openCreate() {
    form.reset();
    editingTheme.value = null;
    showModal.value = true;
}

function openEdit(theme) {
    form.name = theme.name;
    form.domain_ids = theme.domains.map(d => d.id);
    editingTheme.value = theme;
    showModal.value = true;
}

function submit() {
    if (editingTheme.value) {
        form.put(route('admin.themes.update', editingTheme.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.themes.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function deleteTheme(theme) {
    if (confirm(`Voulez-vous vraiment supprimer la thématique "${theme.name}" ?`)) {
        form.delete(route('admin.themes.destroy', theme.id));
    }
}

function toggleDomain(domainId) {
    const index = form.domain_ids.indexOf(domainId);
    if (index === -1) {
        form.domain_ids.push(domainId);
    } else {
        form.domain_ids.splice(index, 1);
    }
}
</script>

<template>
    <Head title="Thématiques" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-6">
                <div>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight">Thématiques</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Gérez les sous-catégories de questions et leurs rattachements aux domaines.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative group">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input v-model="search" type="text" placeholder="Rechercher un thème..." 
                            class="w-full sm:w-80 bg-white border-slate-200 rounded-2xl pl-11 pr-4 py-3 text-sm focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder:text-slate-400 font-medium" />
                    </div>
                    <button @click="openCreate" 
                        class="bg-indigo-600 text-white px-6 py-3 rounded-2xl hover:bg-indigo-700 font-bold text-sm shadow-xl shadow-indigo-100 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nouveau thème
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-[40px] border border-slate-100 shadow-sm overflow-hidden">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Thématique</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Domaines rattachés</th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Questions</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="theme in themes" :key="theme.id" class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="size-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs">
                                            {{ theme.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">{{ theme.name }}</div>
                                            <div class="text-[10px] text-slate-400 font-medium">Slug: {{ theme.slug }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="domain in theme.domains" :key="domain.id" 
                                            class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider border border-slate-200">
                                            {{ domain.name }}
                                        </span>
                                        <span v-if="theme.domains.length === 0" class="text-xs text-slate-300 italic">Aucun domaine</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="inline-flex items-center justify-center size-8 rounded-xl bg-indigo-50 text-indigo-700 font-black text-xs">
                                        {{ theme.questions_count }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEdit(theme)" class="p-2.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteTheme(theme)" class="p-2.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="themes.length === 0">
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="size-16 rounded-3xl bg-slate-50 text-slate-300 flex items-center justify-center mb-4">
                                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900">Aucune thématique trouvée</h3>
                                        <p class="text-sm text-slate-500 mt-1">Créez votre première thématique pour organiser vos questions.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-[40px] p-10 w-full max-w-xl shadow-2xl animate-reveal border border-slate-100">
                    <div class="mb-8 text-center">
                        <div class="size-16 rounded-3xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-6">
                            <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900">{{ editingTheme ? 'Modifier' : 'Nouvelle' }} Thématique</h3>
                        <p class="text-sm text-slate-500 font-medium mt-2">Définissez une sous-catégorie et liez-la aux domaines concernés.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3 ml-1">Nom de la thématique</label>
                            <input v-model="form.name" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-5 py-4 text-sm focus:ring-4 focus:ring-indigo-500/10 transition-all font-bold placeholder:text-slate-300"
                                placeholder="ex: Programmation Orientée Objet" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 ml-1">Domaines d'application</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button v-for="domain in domains" :key="domain.id" type="button"
                                    @click="toggleDomain(domain.id)"
                                    class="flex items-center gap-3 p-4 rounded-2xl border-2 transition-all text-left"
                                    :class="form.domain_ids.includes(domain.id) 
                                        ? 'bg-indigo-50 border-indigo-200 text-indigo-700' 
                                        : 'bg-white border-slate-100 text-slate-600 hover:border-slate-200'">
                                    <div class="size-5 rounded-lg border-2 flex items-center justify-center transition-all"
                                        :class="form.domain_ids.includes(domain.id) ? 'bg-indigo-600 border-indigo-600' : 'bg-white border-slate-200'">
                                        <svg v-if="form.domain_ids.includes(domain.id)" class="size-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <span class="text-xs font-bold">{{ domain.name }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex gap-4 pt-6">
                            <button type="button" @click="showModal = false"
                                class="flex-1 px-6 py-4 text-sm font-black text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                                Annuler
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="flex-1 px-6 py-4 text-sm font-black text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                                {{ editingTheme ? 'Mettre à jour' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
