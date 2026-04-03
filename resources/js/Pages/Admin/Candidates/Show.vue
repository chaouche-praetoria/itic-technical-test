<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ candidate: Object, sessions: Array, templates: Array });

const showLinkModal = ref(false);
const generatedLink = ref('');
const linkForm = useForm({ 
    test_template_id: '',
    send_email: true
});

function generateLink() {
    linkForm.post(route('admin.candidates.generate-link', props.candidate.id), {
        onSuccess: (page) => {
            const flash = page.props.flash || {};
            generatedLink.value = flash.generated_link || '';
            showLinkModal.value = false;
            linkForm.reset();
        },
    });
}

const statusClass = (status) => ({
    pending: 'bg-amber-50 text-amber-600 border-amber-100',
    in_progress: 'bg-blue-50 text-blue-600 border-blue-100',
    completed: 'bg-emerald-50 text-emerald-600 border-emerald-100',
    expired: 'bg-rose-50 text-rose-600 border-rose-100',
}[status] || 'bg-slate-50 text-slate-600 border-slate-100');

const statusLabel = (status) => ({
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Terminé',
    expired: 'Expiré',
}[status] || status);

function copyLink() {
    navigator.clipboard.writeText(generatedLink.value);
}

function sendEmail(sessionId) {
    router.post(route('admin.sessions.send-email', sessionId), {}, {
        onSuccess: () => alert('Email envoyé avec succès !'),
    });
}

const formatDate = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
};

const syncingHubSpot = ref(false);
function syncCandidateToHubSpot() {
    syncingHubSpot.value = true;
    router.post(route('admin.candidates.push-data', props.candidate.id), {}, {
        onFinish: () => syncingHubSpot.value = false,
    });
}

const syncingFromHubSpot = ref(false);
function syncCandidateFromHubSpot() {
    console.log("DEBUG: syncCandidateFromHubSpot started (VERSION 2)");
    syncingFromHubSpot.value = true;
    
    // URL unique et prioritaire
    const url = `/admin/sync-pull-data/${props.candidate.id}`;
    console.log("DEBUG: Requesting URL:", url);

    router.post(url, {}, {
        onSuccess: () => {
            console.log("DEBUG: Success!");
        },
        onError: (errors) => {
            console.error("DEBUG: Inertia Error:", errors);
            alert("Erreur Inertia: " + JSON.stringify(errors));
        },
        onFinish: () => {
            console.log("DEBUG: Finished");
            syncingFromHubSpot.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Candidat: ${candidate.first_name} ${candidate.last_name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div class="flex items-center gap-4">
                    <div class="size-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-xl shadow-xl shadow-slate-200">
                        {{ candidate.first_name[0] }}{{ candidate.last_name[0] }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ candidate.first_name }} {{ candidate.last_name }}</h2>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm text-slate-500 font-medium flex items-center gap-1">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ candidate.email }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <!-- synchronization individuelles (Pull) -->
                    <button @click="syncCandidateFromHubSpot" 
                        :disabled="syncingFromHubSpot"
                        title="Récupérer les dernières infos de HubSpot"
                        class="bg-white text-emerald-600 border border-emerald-100 p-2.5 rounded-xl hover:bg-emerald-50 font-bold text-sm shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center disabled:opacity-50">
                        <svg :class="{ 'animate-spin': syncingFromHubSpot }" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path v-if="!syncingFromHubSpot" stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2 A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <!-- synchronisation individuelles (Push) -->
                    <button @click="syncCandidateToHubSpot" 
                        :disabled="syncingHubSpot"
                        title="Envoyer les scores vers HubSpot"
                        class="bg-white text-indigo-600 border border-indigo-100 px-5 py-2.5 rounded-xl hover:bg-indigo-50 font-bold text-sm shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit disabled:opacity-50">
                        <svg :class="{ 'animate-spin': syncingHubSpot }" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Mise à jour HubSpot
                    </button>
                    <button @click="showLinkModal = true" 
                        class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        Nouveau test
                    </button>
                </div>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Generated Link Alert -->
                <div v-if="generatedLink" class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm shadow-emerald-50 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <svg class="size-24" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <div class="flex-1 relative z-10 w-full">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="size-6 rounded-full bg-emerald-500 text-white flex items-center justify-center">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-sm font-bold text-emerald-800 uppercase tracking-wider">Le lien de test est prêt !</span>
                        </div>
                        <div class="bg-white/80 backdrop-blur border border-emerald-100 rounded-xl px-4 py-3 flex items-center gap-3">
                            <code class="text-xs text-emerald-700 font-mono break-all line-clamp-1 flex-1 select-all">{{ generatedLink }}</code>
                            <button @click="copyLink" class="shrink-0 text-[10px] font-bold text-slate-500 hover:text-slate-900 uppercase tracking-widest flex items-center gap-1.5 transition-all">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Copier
                            </button>
                        </div>
                    </div>
                    <div class="shrink-0 w-full md:w-auto relative z-10">
                        <button v-if="$page.props.flash?.last_session_id" @click="sendEmail($page.props.flash.last_session_id)" 
                            class="w-full bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-100 flex items-center justify-center gap-2 group">
                            <svg class="size-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            Envoyer par email
                        </button>
                    </div>
                </div>

                <!-- Candidate Details Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="md:col-span-2 premium-card p-8 group hover:border-indigo-100 transition-all">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Informations Académiques</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Formation souhaitée</span>
                                <p class="text-sm font-bold text-slate-700 leading-relaxed">{{ candidate.formation_souhaitee || 'Non renseignée' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Code YPareo</span>
                                <p class="text-sm font-bold text-slate-700">{{ candidate.formation_souhaitee_pour_ypareo || 'Non renseigné' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Score test technique</span>
                                <div class="flex items-center gap-2">
                                    <span v-if="candidate.score_test_technique" class="text-sm font-black text-indigo-600 px-2 py-0.5 bg-indigo-50 rounded-md">{{ candidate.score_test_technique }}%</span>
                                    <span v-else class="text-sm font-medium text-slate-400">En attente</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Résultat Test (HubSpot)</span>
                                <p class="text-sm font-bold" :class="candidate.resultat_test_technique === 'admis' ? 'text-emerald-600' : 'text-slate-700'">
                                    {{ candidate.resultat_test_technique || '—' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date Test (HubSpot)</span>
                                <p class="text-sm font-bold text-slate-700">{{ candidate.date_test_technique || '—' }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">ID HubSpot</span>
                                <p class="text-xs text-slate-400 font-mono">{{ candidate.hubspot_id || '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="premium-card p-8 bg-slate-900 text-white shadow-xl shadow-slate-200">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="size-8 rounded-lg bg-white/10 text-white flex items-center justify-center">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <h3 class="text-lg font-bold">Contact</h3>
                        </div>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-white/5 flex items-center justify-center text-white/50">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-bold text-white/40 uppercase tracking-widest mb-0.5">Email</span>
                                    <span class="text-xs font-bold text-white/90 break-all">{{ candidate.email }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="size-10 rounded-xl bg-white/5 flex items-center justify-center text-white/50">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-bold text-white/40 uppercase tracking-widest mb-0.5">Téléphone</span>
                                    <span class="text-xs font-bold text-white/90">{{ candidate.phone || 'Non renseigné' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sessions List -->
                <div class="premium-card overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-slate-800">Historique des tests</h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ sessions.length }} session(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-white text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="px-8 py-5 text-left border-b border-slate-50">Template & Domaine</th>
                                    <th class="px-8 py-5 text-center border-b border-slate-50">Statut</th>
                                    <th class="px-8 py-5 text-center border-b border-slate-50">Score</th>
                                    <th class="px-8 py-5 text-left border-b border-slate-50">Activité</th>
                                    <th class="px-8 py-5 text-right border-b border-slate-50">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="sessions.length === 0">
                                    <td colspan="5" class="px-8 py-16 text-center text-slate-300 italic font-medium">Aucun test envoyé pour le moment</td>
                                </tr>
                                <tr v-for="s in sessions" :key="s.id" class="hover:bg-slate-50/50 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-sm mb-0.5">{{ s.template }}</span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ s.domain }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span :class="statusClass(s.status)" class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border">
                                            {{ statusLabel(s.status) }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div v-if="s.score !== null">
                                            <span class="text-lg font-bold" :class="s.score >= 70 ? 'text-emerald-500' : 'text-slate-800'">{{ s.score }}%</span>
                                        </div>
                                        <span v-else class="text-slate-300 font-bold text-lg">—</span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-slate-600 font-bold flex items-center gap-1.5">
                                                <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ formatDate(s.started_at) || 'Non démarré' }}
                                            </span>
                                            <span v-if="s.duration_seconds" class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                                                Temps passé: {{ Math.round(s.duration_seconds / 60) }}min
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link v-if="s.status === 'completed'" :href="route('admin.sessions.show', s.id)" 
                                                class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all">
                                                Détails de l'examen
                                            </Link>
                                            <template v-if="s.status === 'pending'">
                                                <button @click="sendEmail(s.id)" class="px-5 py-2.5 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-xl text-xs font-black shadow-sm hover:bg-indigo-600 hover:text-white transition-all flex items-center gap-2 group/btn">
                                                    <svg class="size-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                    Envoyer par mail
                                                </button>
                                                <a :href="'/test/' + s.token" target="_blank" 
                                                    class="size-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-900 hover:text-white transition-all">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </template>
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
        <!-- Generate Link Modal -->
        <div v-if="showLinkModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-md shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-8 text-center">
                    <div class="size-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900">Préparer un test</h3>
                    <p class="text-sm text-slate-500 font-medium">Choisissez le template de test à soumettre au candidat.</p>
                </div>
                <form @submit.prevent="generateLink" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Modèle d'examen</label>
                        <select v-model="linkForm.test_template_id" required
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-bold text-slate-700">
                            <option value="">Sélectionner un template...</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-3 bg-slate-50 p-4 rounded-2xl cursor-pointer hover:bg-slate-100 transition-all border border-transparent" @click="linkForm.send_email = !linkForm.send_email">
                        <div class="size-6 rounded-lg flex items-center justify-center transition-all"
                            :class="linkForm.send_email ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border border-slate-200 text-transparent'">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700">Envoyer par email</span>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Le lien sera envoyé automatiquement</span>
                        </div>
                        <input type="checkbox" v-model="linkForm.send_email" class="hidden">
                    </div>
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showLinkModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="linkForm.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            Générer le lien
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
