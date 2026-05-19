<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ candidates: Object, filters: Object });

const search = ref(props.filters.search || '');
const showModal = ref(false);
const showImportModal = ref(false);
const searching = ref(false);
const syncing = ref(false);
const form = useForm({ first_name: '', last_name: '', email: '', phone: '' });
const importForm = useForm({ file: null });

function syncHubSpot() {
    syncing.value = true;
    router.post(route('admin.candidates.sync-hubspot'), {}, {
        onFinish: () => { syncing.value = false; },
    });
}

let searchTimer;
watch(search, (val) => {
    searching.value = true;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('admin.candidates.index'),
            { search: val },
            { preserveState: true, replace: true, onFinish: () => { searching.value = false; } }
        );
    }, 400);
});

function submit() {
    form.post(route('admin.candidates.store'), {
        onSuccess: () => { 
            showModal.value = false; 
            form.reset(); 
        },
    });
}

function importCandidates() {
    importForm.post(route('admin.candidates.import'), {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
}

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};
const formatPhone = (phone) => {
    if (!phone) return null;
    let p = phone.trim().replace(/\s+/g, '');
    if (!p.startsWith('+')) p = '+' + p;
    return p;
};

const sourceClass = (source) => ({
    manual: 'bg-slate-50 text-slate-600 border-slate-200/60',
    excel: 'bg-emerald-50 text-emerald-600 border-emerald-200/60',
    hubspot: 'bg-orange-50 text-orange-600 border-orange-200/60',
}[source] || 'bg-slate-50 text-slate-600 border-slate-200/60');

const sourceLabel = (source) => ({
    manual: 'Manuel',
    excel: 'Excel',
    hubspot: 'HubSpot',
}[source] || 'Manuel');

function deleteCandidate(candidate) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le dossier de ${candidate.first_name} ${candidate.last_name} ?`)) {
        router.delete(route('admin.candidates.destroy', candidate.id));
    }
}
</script>

<template>
    <Head title="Candidats" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Candidats</h2>
                    <p class="text-xs text-slate-500 font-medium">Gérez les profils et suivez les invitations de tests.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="syncHubSpot" :disabled="syncing"
                        class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 font-bold text-xs transition-all flex items-center justify-center gap-2 disabled:opacity-50 shadow-sm">
                        <svg v-if="syncing" class="size-3.5 animate-spin text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        <svg v-else class="size-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        HubSpot
                    </button>
                    <button @click="showImportModal = true"
                        class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg hover:bg-slate-50 font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
                        <svg class="size-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Importer
                    </button>
                    <button @click="showModal = true" 
                        class="bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 font-bold text-xs shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 w-fit">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        Nouveau
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Search Section -->
                <div class="premium-card p-4 glass-card">
                    <div class="relative max-w-md">
                        <input v-model="search" type="text" placeholder="Rechercher un candidat..."
                            class="w-full bg-slate-50 border-none rounded-lg px-4 py-2 text-xs focus:ring-2 focus:ring-indigo-500/20 transition-all pl-10 font-medium text-slate-700" />
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg v-if="searching" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </span>
                        <button v-if="search && !searching" @click="search = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="px-6 py-4 text-left border-b border-slate-100 uppercase">Candidat</th>
                                    <th class="px-6 py-4 text-left border-b border-slate-100 uppercase">Contact</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100 uppercase">Origine</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100 uppercase">Sessions</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100 uppercase">Note</th>
                                    <th class="px-6 py-4 text-left border-b border-slate-100 uppercase">Inscrit le</th>
                                    <th class="px-6 py-4 text-right border-b border-slate-100 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="candidates.data.length === 0">
                                    <td colspan="7" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucun candidat trouvé</td>
                                </tr>
                                <tr v-for="c in candidates.data" :key="c.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="size-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-all text-xs">
                                                {{ c.first_name.charAt(0) }}{{ c.last_name.charAt(0) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 text-[13px] mb-0 group-hover:text-indigo-600 transition-colors">{{ c.first_name }} {{ c.last_name }}</span>
                                                <span class="text-[10px] text-slate-400 font-medium">{{ c.email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col text-slate-600 font-medium text-xs">
                                            <a v-if="c.phone" :href="`tel:${formatPhone(c.phone)}`" class="hover:text-indigo-600 transition-colors flex items-center gap-2">
                                                <svg class="size-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                                {{ c.phone.startsWith('+') ? c.phone : '+' + c.phone }}
                                            </a>
                                            <span v-else class="text-slate-300">—</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="sourceClass(c.added_by)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                                            {{ sourceLabel(c.added_by) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="inline-flex size-8 rounded-lg bg-slate-50 text-slate-500 items-center justify-center font-bold border border-slate-100 text-xs">
                                            {{ c.test_sessions_count }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div v-if="c.score_test_technique" class="inline-flex px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 font-black text-[11px]">
                                            {{ c.score_test_technique }}%
                                        </div>
                                        <span v-else class="text-slate-300 font-bold">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 font-medium text-[10px]">
                                        {{ formatDate(c.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Link :href="route('admin.candidates.show', c.id)" 
                                                class="inline-flex items-center gap-1.5 bg-white border border-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-[11px] font-bold hover:bg-slate-50 hover:border-slate-300 transition-all shadow-sm">
                                                Détails
                                            </Link>
                                            <button @click="deleteCandidate(c)" 
                                                class="p-1.5 text-slate-400 hover:text-rose-600 transition-all hover:bg-rose-50 rounded-lg"
                                                title="Supprimer">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

    <Teleport to="body">
        <!-- New Candidate Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-slate-900 mb-1">Nouveau candidat</h3>
                    <p class="text-sm text-slate-500 font-medium">Inscrivez un nouveau talent sur la plateforme.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Prénom</label>
                            <input v-model="form.first_name" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="Jean" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nom</label>
                            <input v-model="form.last_name" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="Dupont" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Email professionnel</label>
                        <input v-model="form.email" type="email" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="jean.dupont@exemple.com" />
                        <p v-if="form.errors.email" class="text-rose-500 text-xs mt-2 ml-1 font-bold">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Téléphone (Optionnel)</label>
                        <input v-model="form.phone" type="tel"
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            placeholder="+33 6 00 00 00 00" />
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Import Candidates Modal -->
        <div v-if="showImportModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-slate-900 mb-1">Importer des candidats</h3>
                    <p class="text-sm text-slate-500 font-medium">Téléchargez un fichier CSV ou Excel pour importer plusieurs candidats.</p>
                </div>
                
                <div class="mb-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Format attendu</h4>
                    <p class="text-xs text-slate-600 leading-relaxed">
                        Le fichier doit contenir les colonnes suivantes (en-tête obligatoire) :<br>
                        <span class="font-bold">Prenom, Nom, Email, Telephone, Formation</span>
                    </p>
                </div>

                <form @submit.prevent="importCandidates" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Fichier (CSV, XLSX)</label>
                        <div class="relative">
                            <input type="file" @input="importForm.file = $event.target.files[0]"
                                class="w-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl px-4 py-8 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-center file:hidden cursor-pointer hover:border-indigo-300"
                                accept=".csv,.xlsx,.xls" />
                            <div v-if="importForm.file" class="mt-2 text-center text-xs font-bold text-indigo-600">
                                {{ importForm.file.name }}
                            </div>
                            <div v-else class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center gap-2">
                                <svg class="size-6 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                <span class="text-xs text-slate-400">Cliquez ou glissez un fichier ici</span>
                            </div>
                        </div>
                        <p v-if="importForm.errors.file" class="text-rose-500 text-xs mt-2 ml-1 font-bold">{{ importForm.errors.file }}</p>
                    </div>

                    <div v-if="importForm.progress" class="w-full bg-slate-100 rounded-full h-1.5">
                        <div class="bg-indigo-600 h-1.5 rounded-full transition-all" :style="{ width: `${importForm.progress.percentage}%` }"></div>
                    </div>

                    <div class="flex gap-4">
                        <button type="button" @click="showImportModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="importForm.processing || !importForm.file"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ importForm.processing ? 'Importation...' : 'Importer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
