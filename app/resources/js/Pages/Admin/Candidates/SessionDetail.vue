<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ session: Object });

const typeLabel = { mcq: 'QCM', text: 'Réponse libre', code: 'Programmation' };

function getAnswer(question) {
    const answer = props.session.answers.find(a => a.question_id === question.id);
    return answer;
}

const getScoreColor = (score) => {
    if (score >= 80) return 'text-emerald-500';
    if (score >= 50) return 'text-amber-500';
    return 'text-rose-500';
};
</script>

<template>
    <Head title="Détails de l'Examen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex items-center gap-5">
                    <div class="size-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-xl shadow-xl shadow-slate-200">
                        {{ session.score }}%
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900">Session #{{ session.id }}</h2>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-sm text-slate-500 font-bold uppercase tracking-wider">{{ session.candidate.first_name }} {{ session.candidate.last_name }}</span>
                            <span class="size-1 rounded-full bg-slate-300"></span>
                            <span class="text-sm text-indigo-600 font-bold uppercase tracking-wider">{{ session.template.name }}</span>
                        </div>
                    </div>
                </div>
                <Link :href="route('admin.candidates.show', session.candidate_id)" 
                    class="px-5 py-2.5 bg-slate-100 text-slate-600 rounded-xl text-xs font-bold hover:bg-slate-200 transition-all active:scale-[0.98] flex items-center gap-2">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Fiche candidat
                </Link>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-10">
                
                <!-- Advanced Metrics Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="premium-card p-6 glass-card text-center group hover:bg-white transition-all">
                        <div class="size-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">{{ session.correct_answers }}/{{ session.total_questions }}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Réponses</div>
                    </div>
                    <div class="premium-card p-6 glass-card text-center group hover:bg-white transition-all">
                        <div class="size-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">{{ Math.round(session.duration_seconds / 60) }} min</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Temps total</div>
                    </div>
                    <div class="premium-card p-6 glass-card text-center group hover:bg-white transition-all">
                        <div class="size-10 rounded-xl bg-fuchsia-50 text-fuchsia-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">{{ session.activity_logs.length }}</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Journaux</div>
                    </div>
                    <div class="premium-card p-6 glass-card text-center group hover:bg-white transition-all">
                        <div class="size-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div class="text-2xl font-bold" :class="getScoreColor(session.score)">{{ session.score }}%</div>
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Global</div>
                    </div>
                </div>

                <!-- Detailed Responses -->
                <div class="space-y-6">
                    <div class="flex items-center justify-between px-4">
                        <h3 class="text-xl font-bold text-slate-800">Analyse par question</h3>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Score individuel</span>
                    </div>

                    <div v-for="sq in session.session_questions" :key="sq.id" class="premium-card p-8 group hover:shadow-2xl hover:shadow-slate-100 transition-all border border-slate-50">
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <span class="size-8 rounded-lg bg-slate-900 text-white flex items-center justify-center font-bold text-xs shadow-lg">#{{ sq.order + 1 }}</span>
                                <span class="bg-indigo-50 text-indigo-600 text-[10px] font-bold px-3 py-1 rounded-lg uppercase tracking-wider border border-indigo-100">{{ typeLabel[sq.question.type] }}</span>
                                <span class="bg-slate-50 text-slate-400 text-[10px] font-bold px-3 py-1 rounded-lg uppercase tracking-wider border border-slate-100">{{ sq.question.theme?.name || 'Général' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-black" :class="getScoreColor(getAnswer(sq.question)?.score ?? 0)">
                                    {{ getAnswer(sq.question)?.score ?? 0 }}%
                                </span>
                            </div>
                        </div>

                        <p class="text-lg font-bold text-slate-800 mb-8 leading-relaxed">{{ sq.question.statement }}</p>

                        <!-- Answer Content -->
                        <div class="rounded-2xl overflow-hidden border border-slate-50">
                            <!-- MCQ -->
                            <div v-if="sq.question.type === 'mcq'" class="divide-y divide-slate-50">
                                <div v-for="choice in sq.question.choices" :key="choice.id"
                                    :class="[
                                        'px-6 py-4 text-sm flex items-center justify-between transition-colors',
                                        choice.is_correct ? 'bg-emerald-50/50' : '',
                                        getAnswer(sq.question)?.answer?.includes(choice.id) && !choice.is_correct ? 'bg-rose-50/50' : '',
                                    ]">
                                    <div class="flex items-center gap-3">
                                        <div class="size-5 rounded flex items-center justify-center border-2 shrink-0"
                                            :class="[
                                                choice.is_correct ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-slate-200',
                                                getAnswer(sq.question)?.answer?.includes(choice.id) ? 'border-slate-400' : ''
                                            ]">
                                            <svg v-if="choice.is_correct" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <span :class="[
                                            'font-medium',
                                            choice.is_correct ? 'text-emerald-800' : 'text-slate-600',
                                            getAnswer(sq.question)?.answer?.includes(choice.id) && !choice.is_correct ? 'text-rose-800' : ''
                                        ]">{{ choice.text }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span v-if="getAnswer(sq.question)?.answer?.includes(choice.id)" 
                                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-white/50 px-2 py-0.5 rounded border border-slate-100 italic">Candidat</span>
                                        <span v-if="choice.is_correct" class="text-[10px] font-bold text-emerald-600 bg-emerald-100 flex items-center gap-1.5 px-3 py-1 rounded-full uppercase tracking-widest border border-emerald-200">
                                            Solution
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Text -->
                            <div v-else-if="sq.question.type === 'text'" class="bg-slate-50/50 p-6">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Réponse rédigée</label>
                                <div class="bg-white border border-slate-100 rounded-xl p-5 text-slate-700 text-sm italic font-medium leading-relaxed shadow-inner">
                                    {{ getAnswer(sq.question)?.answer || 'Aucune réponse n\'a été fournie.' }}
                                </div>
                            </div>

                            <!-- Code -->
                            <div v-else-if="sq.question.type === 'code'" class="bg-slate-900 overflow-hidden shadow-2xl">
                                <div class="px-5 py-3 bg-slate-800 flex items-center justify-between border-b border-white/5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Source Code</span>
                                    <div class="flex gap-1.5">
                                        <span class="size-2 rounded-full bg-slate-700"></span>
                                        <span class="size-2 rounded-full bg-slate-700"></span>
                                        <span class="size-2 rounded-full bg-slate-700"></span>
                                    </div>
                                </div>
                                <pre class="p-6 text-slate-300 text-xs font-mono overflow-x-auto leading-relaxed">{{ getAnswer(sq.question)?.answer?.code || '// Aucun code soumis.' }}</pre>
                                
                                <div v-if="getAnswer(sq.question)?.execution_result" class="px-6 py-4 bg-slate-800/50 flex items-center justify-between border-t border-white/5">
                                    <div class="flex items-center gap-3">
                                        <div :class="getAnswer(sq.question).execution_result.success ? 'bg-emerald-500' : 'bg-rose-500'" class="size-2 rounded-full animate-pulse"></div>
                                        <span class="text-xs font-bold text-white uppercase tracking-wider">Résultats d'exécution</span>
                                    </div>
                                    <span :class="getAnswer(sq.question).execution_result.success ? 'text-emerald-400' : 'text-rose-400'" class="text-xs font-bold font-mono">
                                        {{ getAnswer(sq.question).execution_result.passed }} / {{ getAnswer(sq.question).execution_result.total }} tests passés
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div v-if="session.activity_logs.length > 0" class="premium-card p-10 bg-slate-900 relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-5">
                         <svg class="size-48 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-8">
                             <div class="size-1 bg-white rounded-full"></div>
                             <h3 class="text-white font-bold text-lg uppercase tracking-widest font-mono">Behavioral Audit Log</h3>
                        </div>
                        <div class="space-y-6 max-h-[400px] overflow-y-auto custom-scrollbar pr-4">
                            <div v-for="log in session.activity_logs" :key="log.id"
                                class="flex items-start gap-6 group">
                                <span class="text-[10px] font-bold text-slate-500 font-mono pt-1 grow-0 shrink-0 w-28">{{ log.occurred_at }}</span>
                                <div class="flex flex-col grow min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span :class="log.event.includes('tab') || log.event.includes('blur') ? 'text-rose-400 bg-rose-500/10 border-rose-500/20' : 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20'"
                                            class="text-[10px] font-bold px-2.5 py-0.5 rounded border uppercase tracking-widest shadow-lg">
                                            {{ log.event }}
                                        </span>
                                        <span v-if="log.metadata" class="text-slate-500 text-[10px] font-mono truncate opacity-60 break-all">{{ JSON.stringify(log.metadata) }}</span>
                                    </div>
                                    <div class="h-6 ml-1 mt-1 border-l-2 border-slate-800 group-last:hidden"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>
