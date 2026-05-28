<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    candidates: Object,
    filters: Object,
    stats: Object,
    formations: Array,
    templates: Array
});

const search = ref(props.filters.search || '');
const perPage = ref(props.filters.per_page || 20);
const addedBy = ref(props.filters.added_by || '');
const formationSouhaitee = ref(props.filters.formation_souhaitee || '');
const hasSessions = ref(props.filters.has_sessions || '');
const testCompleted = ref(props.filters.test_completed || '');

const selectedIds = ref([]);
const showModal = ref(false);
const showImportModal = ref(false);
const showBulkSendModal = ref(false);
const searching = ref(false);
const syncing = ref(false);

const form = useForm({ first_name: '', last_name: '', email: '', phone: '' });
const importForm = useForm({ file: null });
const bulkSendForm = useForm({
    ids: [],
    test_template_id: '',
    send_email: true,
    sync_hubspot: true,
});

function syncHubSpot() {
    syncing.value = true;
    router.post(route('admin.candidates.sync-hubspot'), {}, {
        onFinish: () => { syncing.value = false; },
    });
}

let searchTimer;
function applyFilters() {
    searching.value = true;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('admin.candidates.index'),
            {
                search: search.value,
                per_page: perPage.value,
                added_by: addedBy.value,
                formation_souhaitee: formationSouhaitee.value,
                has_sessions: hasSessions.value,
                test_completed: testCompleted.value
            },
            { preserveState: true, replace: true, onFinish: () => { searching.value = false; } }
        );
    }, 400);
}

watch([search, perPage, addedBy, formationSouhaitee, hasSessions, testCompleted], applyFilters);

watch(() => props.filters, (newFilters) => {
    if (newFilters) {
        if (search.value !== (newFilters.search || '')) search.value = newFilters.search || '';
        if (perPage.value !== (newFilters.per_page || 20)) perPage.value = newFilters.per_page || 20;
        if (addedBy.value !== (newFilters.added_by || '')) addedBy.value = newFilters.added_by || '';
        if (formationSouhaitee.value !== (newFilters.formation_souhaitee || '')) formationSouhaitee.value = newFilters.formation_souhaitee || '';
        if (hasSessions.value !== (newFilters.has_sessions || '')) hasSessions.value = newFilters.has_sessions || '';
        if (testCompleted.value !== (newFilters.test_completed || '')) testCompleted.value = newFilters.test_completed || '';
    }
}, { deep: true });

function toggleAll() {
    if (selectedIds.value.length === props.candidates.data.length) {
        selectedIds.value = [];
    } else {
        selectedIds.value = props.candidates.data.map(c => c.id);
    }
}

function bulkDelete() {
    if (confirm(`Êtes-vous sûr de vouloir supprimer les ${selectedIds.value.length} candidats sélectionnés ?`)) {
        router.post(route('admin.candidates.bulk-destroy'), {
            ids: selectedIds.value
        }, {
            onSuccess: () => {
                selectedIds.value = [];
            }
        });
    }
}

function openBulkSend() {
    bulkSendForm.test_template_id = '';
    showBulkSendModal.value = true;
}

function submitBulkSend() {
    bulkSendForm.ids = selectedIds.value;
    bulkSendForm.post(route('admin.candidates.bulk-generate-link'), {
        onSuccess: () => {
            showBulkSendModal.value = false;
            selectedIds.value = [];
            bulkSendForm.reset();
        }
    });
}

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

const cleanLabel = (label) => {
    return label.replace('&laquo; Previous', 'Précédent').replace('Next &raquo;', 'Suivant');
};

const bulkSyncing = ref(false);
const hasHubSpotSelected = computed(() => {
    return selectedIds.value.some(id => {
        const candidate = props.candidates.data.find(c => c.id === id);
        return candidate && candidate.added_by === 'hubspot';
    });
});

function bulkSyncHubSpot() {
    const hubspotIds = selectedIds.value.filter(id => {
        const candidate = props.candidates.data.find(c => c.id === id);
        return candidate && candidate.added_by === 'hubspot';
    });
    
    if (hubspotIds.length === 0) return;
    
    if (confirm(`Êtes-vous sûr de vouloir synchroniser les ${hubspotIds.length} candidat(s) HubSpot sélectionnés vers HubSpot ?`)) {
        bulkSyncing.value = true;
        router.post(route('admin.candidates.bulk-sync-hubspot'), {
            ids: hubspotIds
        }, {
            onSuccess: () => {
                selectedIds.value = [];
            },
            onFinish: () => {
                bulkSyncing.value = false;
            }
        });
    }
}

const displayedLinks = computed(() => {
    const links = props.candidates.links;
    if (!links || links.length <= 7) return links;

    const current = props.candidates.current_page;
    const last = props.candidates.last_page;

    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pageLinks = links.slice(1, -1);

    const range = 1;
    const result = [];

    // Always add Prev
    result.push(prevLink);

    // Always add Page 1
    result.push(pageLinks[0]);

    // Ellipsis after Page 1
    if (current > range + 3) {
        result.push({ label: '...', url: null, active: false });
    } else if (current === range + 3) {
        result.push(pageLinks[1]);
    }

    // Middle pages
    const start = Math.max(2, current - range);
    const end = Math.min(last - 1, current + range);

    for (let i = start; i <= end; i++) {
        result.push(pageLinks[i - 1]);
    }

    // Ellipsis before Last Page
    if (current === last - range - 2) {
        result.push(pageLinks[last - 2]);
    } else if (current < last - range - 2) {
        result.push({ label: '...', url: null, active: false });
    }

    // Always add Last page (if last > 1)
    if (last > 1) {
        result.push(pageLinks[last - 1]);
    }

    // Always add Next
    result.push(nextLink);

    return result;
});
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
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Candidates -->
                    <div class="premium-card p-4 flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Candidats</p>
                            <p class="text-xl font-extrabold text-slate-900">{{ stats.total_candidates }}</p>
                            <div class="flex items-center gap-1.5 mt-1 text-[9px] font-extrabold tracking-wider uppercase">
                                <span class="text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                                    {{ stats.candidates_with_sessions }} avec test
                                </span>
                                <span class="text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded">
                                    {{ stats.candidates_without_sessions }} sans test
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Completed Sessions -->
                    <div class="premium-card p-4 flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tests Complétés</p>
                            <p class="text-xl font-extrabold text-slate-900">{{ stats.completed_sessions }}</p>
                        </div>
                    </div>

                    <!-- Avg Score -->
                    <div class="premium-card p-4 flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Moyenne Générale</p>
                            <p class="text-xl font-extrabold text-slate-900">{{ stats.avg_score }}%</p>
                        </div>
                    </div>

                    <!-- Success Rate -->
                    <div class="premium-card p-4 flex items-center gap-4">
                        <div class="size-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Taux d'Admission</p>
                            <p class="text-xl font-extrabold text-slate-900">{{ stats.success_rate }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters Section -->
                <div class="premium-card p-4 glass-card flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-md">
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
                    <div class="flex items-center gap-3">
                        <!-- Origin Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider hidden sm:inline">Origine :</span>
                            <select v-model="addedBy" class="h-9 px-3 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-all cursor-pointer shadow-sm">
                                <option value="">Toutes les origines</option>
                                <option value="manual">Manuel</option>
                                <option value="excel">Excel</option>
                                <option value="hubspot">HubSpot</option>
                            </select>
                        </div>

                        <!-- Formation Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider hidden sm:inline">Formation :</span>
                            <select v-model="formationSouhaitee" class="h-9 px-3 max-w-[200px] rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-all cursor-pointer shadow-sm">
                                <option value="">Toutes les formations</option>
                                <option v-for="f in formations" :key="f" :value="f">{{ f }}</option>
                            </select>
                       </div>

                        <!-- Sessions Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider hidden sm:inline">Sessions :</span>
                            <select v-model="hasSessions" class="h-9 px-3 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-all cursor-pointer shadow-sm">
                                <option value="">Toutes</option>
                                <option value="yes">Avec session(s)</option>
                                <option value="no">Sans session</option>
                            </select>
                        </div>

                        <!-- Test Completed Filter -->
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider hidden sm:inline">Test effectué :</span>
                            <select v-model="testCompleted" class="h-9 px-3 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-all cursor-pointer shadow-sm">
                                <option value="">Tous</option>
                                <option value="yes">Oui</option>
                                <option value="no">Non</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="pl-6 py-4 text-left border-b border-slate-100 w-10">
                                        <input type="checkbox"
                                            :checked="selectedIds.length === candidates.data.length && candidates.data.length > 0"
                                            @change="toggleAll"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer transition-all" />
                                    </th>
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
                                    <td colspan="8" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucun candidat trouvé</td>
                                </tr>
                                <tr v-for="c in candidates.data" :key="c.id" :class="[selectedIds.includes(c.id) ? 'bg-indigo-50/30' : '', 'hover:bg-slate-50/80 transition-all group']">
                                    <td class="pl-6 py-4 w-10">
                                        <input type="checkbox"
                                            :value="c.id"
                                            v-model="selectedIds"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer transition-all" />
                                    </td>
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
                                        <div v-else-if="c.latest_session && c.latest_session.score !== null" class="inline-flex px-2 py-0.5 rounded bg-indigo-50 text-indigo-600 font-black text-[11px]">
                                            {{ c.latest_session.score }}%
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

                    <!-- Enhanced Pagination & per_page Selector -->
                    <div v-if="candidates.total > 0" class="px-8 py-6 bg-slate-50/30 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-100">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-6">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ candidates.total }} candidat(s) au total</p>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Afficher :</span>
                                <select v-model="perPage" class="h-8 px-2.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-600 bg-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-all cursor-pointer">
                                    <option value="10">10 par page</option>
                                    <option value="20">20 par page</option>
                                    <option value="50">50 par page</option>
                                    <option value="100">100 par page</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="candidates.last_page > 1" class="flex gap-1.5">
                            <Link v-for="link in displayedLinks" :key="link.label"
                                :href="link.url || '#'"
                                v-html="cleanLabel(link.label)"
                                :class="[
                                    'h-8 min-w-[2rem] px-2 flex items-center justify-center text-xs font-bold rounded-lg transition-all',
                                    link.active ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : 'bg-white text-slate-500 border border-slate-200 hover:border-slate-300',
                                    !link.url ? 'opacity-30 pointer-events-none border-none text-slate-300' : ''
                                ]" />
                        </div>
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

        <!-- Bulk Send Modal -->
        <div v-if="showBulkSendModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-slate-900 mb-1">Inviter la sélection</h3>
                    <p class="text-sm text-slate-500 font-medium">Générez et envoyez un test technique pour les {{ selectedIds.length }} candidats sélectionnés.</p>
                </div>
                <form @submit.prevent="submitBulkSend" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Modèle de test</label>
                        <select v-model="bulkSendForm.test_template_id" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium">
                            <option value="">Sélectionnez un modèle de test</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <p v-if="bulkSendForm.errors.test_template_id" class="text-rose-500 text-xs mt-2 ml-1 font-bold">{{ bulkSendForm.errors.test_template_id }}</p>
                    </div>

                    <div class="space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" v-model="bulkSendForm.send_email"
                                class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer transition-all" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">Envoyer l'invitation par email</span>
                                <span class="text-[10px] text-slate-400 font-medium">Chaque candidat recevra un email personnalisé contenant son lien d'accès.</span>
                            </div>
                        </label>

                        <div class="h-px bg-slate-200/60 my-2"></div>

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" v-model="bulkSendForm.sync_hubspot"
                                class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer transition-all" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">Synchroniser avec HubSpot</span>
                                <span class="text-[10px] text-slate-400 font-medium">Mettre à jour le contact HubSpot avec le lien du test généré.</span>
                            </div>
                        </label>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showBulkSendModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="bulkSendForm.processing || !bulkSendForm.test_template_id"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ bulkSendForm.processing ? 'Envoi...' : 'Envoyer les tests' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sliding Bulk Actions Toolbar -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-12 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transform ease-in duration-200 transition"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-12 opacity-0"
        >
            <div v-if="selectedIds.length > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-slate-900 text-white rounded-2xl px-6 py-4 flex items-center gap-6 shadow-2xl border border-slate-800 backdrop-blur-md">
                <div class="flex items-center gap-2">
                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-black animate-pulse">
                        {{ selectedIds.length }}
                    </span>
                    <span class="text-xs font-bold text-slate-300">sélectionné(s)</span>
                </div>
                <div class="h-4 w-px bg-slate-800"></div>
                <div class="flex items-center gap-2">
                    <button v-if="hasHubSpotSelected" @click="bulkSyncHubSpot" :disabled="bulkSyncing" class="bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs py-2 px-4 rounded-xl transition-all shadow-lg shadow-orange-600/30 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                        <svg v-if="bulkSyncing" class="size-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
                        <svg v-else class="size-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Sync HubSpot
                    </button>
                    <button @click="openBulkSend" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs py-2 px-4 rounded-xl transition-all shadow-lg shadow-indigo-600/30 flex items-center gap-2 hover:scale-[1.02] active:scale-[0.98]">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        Envoyer un test
                    </button>
                    <button @click="bulkDelete" class="bg-rose-600/10 hover:bg-rose-600 text-rose-400 hover:text-white font-bold text-xs py-2 px-4 rounded-xl transition-all flex items-center gap-2">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer
                    </button>
                    <button @click="selectedIds = []" class="text-slate-400 hover:text-white font-bold text-xs py-2 px-3 rounded-xl transition-all">
                        Annuler
                    </button>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
