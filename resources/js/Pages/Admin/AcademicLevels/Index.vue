<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ levels: Array });

const showModal = ref(false);
const editingLevel = ref(null);

const form = useForm({ 
    name: '', 
    order: 0,
    fallback_level_id: null
});

function openCreate() {
    form.reset();
    form.order = props.levels.length + 1;
    editingLevel.value = null;
    showModal.value = true;
}

function openEdit(level) {
    form.name = level.name;
    form.order = level.order;
    form.fallback_level_id = level.fallback_level_id;
    editingLevel.value = level;
    showModal.value = true;
}

function submit() {
    if (editingLevel.value) {
        form.put(route('admin.levels.update', editingLevel.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.levels.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function deleteLevel(level) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le niveau "${level.name}" ?`)) {
        router.delete(route('admin.levels.destroy', level.id));
    }
}
</script>

<template>
    <Head title="Niveaux Académiques" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Niveaux Académiques</h2>
                    <p class="text-sm text-slate-500 font-medium">Gérez les qualifications cibles (Bachelor, Mastère, etc.)</p>
                </div>
                <button @click="openCreate" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouveau niveau
                </button>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="level in levels" :key="level.id" class="premium-card p-6 flex flex-col group hover:shadow-2xl transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs">
                                    #{{ level.order }}
                                </div>
                                <h3 class="font-bold text-lg text-slate-800 group-hover:text-indigo-600 transition-colors">{{ level.name }}</h3>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openEdit(level)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteLevel(level)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="level.fallback_level" class="mb-4">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1 px-1">Repli en cas d'échec</span>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold ring-1 ring-slate-200">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                {{ level.fallback_level.name }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Questions</span>
                                <span class="text-sm font-bold text-slate-700">{{ level.questions_count }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Templates</span>
                                <span class="text-sm font-bold text-slate-700">{{ level.test_templates_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="levels.length === 0" class="flex flex-col items-center justify-center p-20 bg-white rounded-3xl border-2 border-dashed border-slate-100 italic text-slate-400">
                    <svg class="size-12 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                    Aucun niveau configuré.
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6 text-center">
                    <h3 class="text-2xl font-bold text-slate-900">{{ editingLevel ? 'Modifier' : 'Nouveau' }} Niveau</h3>
                    <p class="text-sm text-slate-500 font-medium">Définissez une qualification académique.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nom du niveau</label>
                        <input v-model="form.name" type="text" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="ex: Bachelor Concepteur" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Ordre d'affichage</label>
                        <input v-model="form.order" type="number" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="ex: 10" />
                        <p class="text-[10px] text-slate-400 font-medium mt-2 px-1 italic text-center">Permet de trier les niveaux dans les listes déroulantes.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Prochain niveau (Repli)</label>
                        <select v-model="form.fallback_level_id" 
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium appearance-none">
                            <option :value="null">Aucun repli (Défaut)</option>
                            <option v-for="l in levels.filter(lvl => !editingLevel || lvl.id !== editingLevel.id)" 
                                :key="l.id" :value="l.id">
                                Vers : {{ l.name }}
                            </option>
                        </select>
                        <p class="text-[10px] text-slate-400 font-medium mt-2 px-1 italic text-center">Niveau proposé au candidat en cas d'échec (< 70%).</p>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ editingLevel ? 'Mettre à jour' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
