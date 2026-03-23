<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    stats: Object,
    recentSessions: Array,
    scoresByDomain: Array,
    scoresByLevel: Array,
});

const statusClass = (status) => ({
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    expired: 'bg-red-100 text-red-800',
}[status] || 'bg-gray-100 text-gray-800');

const refreshing = ref(false);
const lastRefreshed = ref(new Date());
let interval;

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['stats', 'recentSessions', 'scoresByDomain', 'scoresByLevel'],
        onFinish: () => {
            refreshing.value = false;
            lastRefreshed.value = new Date();
        },
    });
}

function sendEmail(sessionId) {
    router.post(route('admin.sessions.send-email', sessionId), {}, {
        onSuccess: () => alert('Email envoyé !'),
    });
}

onMounted(() => { interval = setInterval(refresh, 30000); });
onUnmounted(() => clearInterval(interval));
</script>

<template>
    <Head title="Dashboard Admin" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Vue d'ensemble</h2>
                    <p class="text-sm text-slate-500">Bienvenue sur votre tableau de bord d'administration.</p>
                </div>
                <button @click="refresh" :disabled="refreshing"
                    class="btn-secondary flex items-center gap-2 text-xs">
                    <svg :class="['w-4 h-4', refreshing && 'animate-spin']" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Mis à jour {{ lastRefreshed.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</span>
                </button>
            </div>
        </template>

        <div class="space-y-8 mt-2">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Candidates -->
                <div class="premium-card p-6 flex items-center gap-4">
                    <div class="size-12 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Candidats</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_candidates }}</p>
                    </div>
                </div>

                <!-- Total Questions -->
                <div class="premium-card p-6 flex items-center gap-4">
                    <div class="size-12 rounded-2xl bg-purple-50 flex items-center justify-center text-purple-600">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Questions</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_questions }}</p>
                    </div>
                </div>

                <!-- Total Sessions -->
                <div class="premium-card p-6 flex items-center gap-4">
                    <div class="size-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Sessions</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.total_sessions }}</p>
                    </div>
                </div>

                <!-- Avg Score -->
                <div class="premium-card p-6 flex items-center gap-4">
                    <div class="size-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-500 uppercase tracking-wider">Score moyen</p>
                        <p class="text-2xl font-bold text-slate-900">{{ stats.avg_score }}%</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Scores by Domain -->
                <div class="premium-card p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-bold text-slate-900">Performance par domaine</h3>
                        <div class="size-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div v-if="scoresByDomain.length === 0" class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <svg class="size-12 mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-sm">Aucune donnée disponible</p>
                    </div>
                    <div v-for="d in scoresByDomain" :key="d.name" class="mb-6 last:mb-0">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-semibold text-slate-700">{{ d.name }}</span>
                            <span class="text-slate-500 font-medium">{{ Math.round(d.avg_score) }}% <span class="text-xs ml-1 opacity-50">({{ d.total }} tests)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-indigo-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: d.avg_score + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Scores by Level -->
                <div class="premium-card p-8">
                     <div class="flex items-center justify-between mb-8">
                        <h3 class="text-lg font-bold text-slate-900">Performance par niveau</h3>
                        <div class="size-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                    </div>
                    <div v-if="scoresByLevel.length === 0" class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <svg class="size-12 mb-3 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-sm">Aucune donnée disponible</p>
                    </div>
                    <div v-for="l in scoresByLevel" :key="l.name" class="mb-6 last:mb-0">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="font-semibold text-slate-700">{{ l.name }}</span>
                            <span class="text-slate-500 font-medium">{{ Math.round(l.avg_score) }}% <span class="text-xs ml-1 opacity-50">({{ l.total }} tests)</span></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-purple-500 h-2.5 rounded-full transition-all duration-1000" :style="{ width: l.avg_score + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="premium-card overflow-hidden">
                <div class="p-8 flex justify-between items-center border-b border-slate-100 bg-slate-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Sessions récentes</h3>
                        <p class="text-sm text-slate-500">Les 10 dernières tentatives de tests.</p>
                    </div>
                    <Link :href="route('admin.candidates.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 px-4 py-2 rounded-xl transition-colors">Tout voir</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="bg-white text-slate-400 text-xs uppercase tracking-widest font-bold">
                                <th class="px-8 py-5 text-left border-b border-slate-100">Candidat</th>
                                <th class="px-8 py-5 text-left border-b border-slate-100">Template</th>
                                <th class="px-8 py-5 text-left border-b border-slate-100 text-center">Statut</th>
                                <th class="px-8 py-5 text-left border-b border-slate-100 text-center">Score</th>
                                <th class="px-8 py-5 text-left border-b border-slate-100">Complété le</th>
                                <th class="px-8 py-5 text-right border-b border-slate-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-if="recentSessions.length === 0">
                                <td colspan="5" class="px-8 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="size-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-200 mb-4">
                                             <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-slate-400 font-medium font-serif italic text-lg opacity-60">Aucune session enregistrée</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="s in recentSessions" :key="s.id" class="hover:bg-slate-50/80 transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors">
                                            {{ s.candidate.charAt(0) }}
                                        </div>
                                        <span class="font-bold text-slate-700">{{ s.candidate }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-slate-600 font-medium">{{ s.template }}</td>
                                <td class="px-8 py-5 text-center">
                                    <span :class="statusClass(s.status)" class="px-3 py-1.5 rounded-full text-[10px] items-center uppercase tracking-wider font-bold inline-flex">
                                        <span class="size-1.5 rounded-full mr-2" :class="{
                                            'bg-green-500': s.status === 'completed',
                                            'bg-blue-500': s.status === 'in_progress',
                                            'bg-yellow-500': s.status === 'pending',
                                            'bg-red-500': s.status === 'expired'
                                        }"></span>
                                        {{ s.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <div v-if="s.score !== null">
                                        <span class="text-base font-bold text-slate-900">{{ s.score }}%</span>
                                    </div>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-8 py-5 text-left text-slate-400 font-medium">
                                    {{ s.completed_at || 'En cours...' }}
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button v-if="s.status === 'pending'" @click="sendEmail(s.id)" title="Envoyer par mail"
                                            class="size-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-100 transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <Link :href="s.status === 'completed' ? route('admin.sessions.show', s.id) : route('admin.candidates.show', s.candidate_id || '')" 
                                            class="size-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
