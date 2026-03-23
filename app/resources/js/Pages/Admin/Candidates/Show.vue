<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ candidate: Object, sessions: Array, templates: Array });

const showLinkModal = ref(false);
const generatedLink = ref('');
const linkForm = useForm({ test_template_id: '' });

function generateLink() {
    linkForm.post(route('admin.candidates.generate-link', props.candidate.id), {
        onSuccess: (page) => {
            const flash = page.props.flash?.success || '';
            generatedLink.value = flash.replace('Lien généré: ', '');
            showLinkModal.value = false;
            linkForm.reset();
        },
    });
}

const statusClass = (status) => ({
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    expired: 'bg-red-100 text-red-800',
}[status] || 'bg-gray-100 text-gray-800');

function copyLink() {
    navigator.clipboard.writeText(generatedLink.value);
    alert('Lien copié !');
}

function sendEmail(sessionId) {
    router.post(route('admin.sessions.send-email', sessionId), {}, {
        onSuccess: () => alert('Email envoyé !'),
    });
}
</script>

<template>
    <Head :title="`Candidat: ${candidate.first_name} ${candidate.last_name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ candidate.first_name }} {{ candidate.last_name }}</h2>
                    <p class="text-sm text-gray-500">{{ candidate.email }}</p>
                </div>
                <button @click="showLinkModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    Générer un lien de test
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Generated Link -->
                <div v-if="generatedLink" class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-bold text-green-800 flex items-center gap-2">
                             <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                             Lien généré avec succès !
                        </p>
                        <p class="text-xs text-green-700 mt-1 font-mono bg-white/50 px-2 py-1 rounded border border-green-100 break-all select-all">{{ generatedLink }}</p>
                    </div>
                    <div class="flex gap-2 ml-4 self-end sm:self-center">
                        <button @click="copyLink" class="text-xs bg-slate-800 text-white px-3 py-2 rounded-lg font-bold hover:bg-slate-900 transition-all flex items-center gap-2">
                             <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                             Copier
                        </button>
                        <button v-if="$page.props.flash?.last_session_id" @click="sendEmail($page.props.flash.last_session_id)" class="text-xs bg-indigo-600 text-white px-3 py-2 rounded-lg font-bold hover:bg-indigo-700 transition-all shadow-md shadow-indigo-100 flex items-center gap-2">
                             <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                             Envoyer par mail
                        </button>
                    </div>
                </div>

                <!-- Candidate Info -->
                <div class="bg-white rounded-xl shadow p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Prénom</p>
                        <p class="font-medium">{{ candidate.first_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Nom</p>
                        <p class="font-medium">{{ candidate.last_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="font-medium">{{ candidate.email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Téléphone</p>
                        <p class="font-medium">{{ candidate.phone || '—' }}</p>
                    </div>
                </div>

                <!-- Sessions -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-800">Sessions de test</h3>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Template</th>
                                <th class="px-6 py-3 text-left">Domaine</th>
                                <th class="px-6 py-3 text-left">Statut</th>
                                <th class="px-6 py-3 text-left">Score</th>
                                <th class="px-6 py-3 text-left">Démarré le</th>
                                <th class="px-6 py-3 text-left">Durée</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="sessions.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">Aucune session</td>
                            </tr>
                            <tr v-for="s in sessions" :key="s.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium">{{ s.template }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ s.domain }}</td>
                                <td class="px-6 py-4">
                                    <span :class="statusClass(s.status)" class="px-2 py-1 rounded-full text-xs font-medium">{{ s.status }}</span>
                                </td>
                                <td class="px-6 py-4">{{ s.score !== null ? s.score + '%' : '—' }}</td>
                                <td class="px-6 py-4 text-gray-500 text-xs">{{ s.started_at || '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ s.duration_seconds ? Math.round(s.duration_seconds / 60) + 'min' : '—' }}
                                </td>
                                <td class="px-6 py-4 flex gap-3 items-center">
                                    <Link v-if="s.status === 'completed'" :href="route('admin.sessions.show', s.id)" class="text-indigo-600 hover:underline text-xs">Détails</Link>
                                    <template v-if="s.status === 'pending'">
                                        <a :href="'/test/' + s.token" target="_blank" class="text-gray-500 hover:underline text-xs">Lien →</a>
                                        <button @click="sendEmail(s.id)" class="text-indigo-600 hover:text-indigo-800 text-xs flex items-center gap-1 border border-indigo-100 px-2 py-1 rounded hover:bg-indigo-50 transition-colors">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Envoyer mail
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Generate Link Modal -->
        <div v-if="showLinkModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Générer un lien de test</h3>
                <form @submit.prevent="generateLink" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template de test</label>
                        <select v-model="linkForm.test_template_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Sélectionner un template...</option>
                            <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="showLinkModal = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Annuler</button>
                        <button type="submit" :disabled="linkForm.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Générer</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
