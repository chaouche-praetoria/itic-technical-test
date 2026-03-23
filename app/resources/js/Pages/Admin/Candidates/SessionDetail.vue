<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({ session: Object });

const typeLabel = { mcq: 'QCM', text: 'Texte libre', code: 'Code' };

function getAnswer(question) {
    const answer = props.session.answers.find(a => a.question_id === question.id);
    return answer;
}
</script>

<template>
    <Head title="Détail de session" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Session #{{ session.id }}</h2>
                    <p class="text-sm text-gray-500">{{ session.candidate.first_name }} {{ session.candidate.last_name }} — {{ session.template.name }}</p>
                </div>
                <Link :href="route('admin.candidates.show', session.candidate_id)" class="text-sm text-indigo-600 hover:underline">← Retour</Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Summary -->
                <div class="bg-white rounded-xl shadow p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-indigo-600">{{ session.score }}%</div>
                        <div class="text-xs text-gray-500 mt-1">Score final</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ session.correct_answers }}/{{ session.total_questions }}</div>
                        <div class="text-xs text-gray-500 mt-1">Réponses correctes</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ Math.round(session.duration_seconds / 60) }}min</div>
                        <div class="text-xs text-gray-500 mt-1">Durée totale</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-800">{{ session.activity_logs.length }}</div>
                        <div class="text-xs text-gray-500 mt-1">Événements</div>
                    </div>
                </div>

                <!-- Questions & Answers -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">Réponses détaillées</h3>
                    <div v-for="sq in session.session_questions" :key="sq.id" class="bg-white rounded-xl shadow p-6">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-gray-500">Q{{ sq.order + 1 }}</span>
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">{{ typeLabel[sq.question.type] }}</span>
                            </div>
                            <div class="text-right">
                                <span :class="getAnswer(sq.question)?.score >= 100 ? 'text-green-600' : 'text-red-500'"
                                    class="font-semibold text-sm">
                                    {{ getAnswer(sq.question)?.score ?? 0 }}%
                                </span>
                            </div>
                        </div>

                        <p class="text-gray-800 font-medium mb-3">{{ sq.question.statement }}</p>

                        <!-- MCQ Answer -->
                        <div v-if="sq.question.type === 'mcq'" class="space-y-2">
                            <div v-for="choice in sq.question.choices" :key="choice.id"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm border',
                                    choice.is_correct ? 'border-green-400 bg-green-50 text-green-800' : 'border-gray-200 text-gray-700',
                                    getAnswer(sq.question)?.answer?.includes(choice.id) && !choice.is_correct ? 'border-red-400 bg-red-50 text-red-800' : '',
                                ]">
                                {{ choice.text }}
                                <span v-if="choice.is_correct" class="text-green-600 ml-2 text-xs">✓ Correcte</span>
                            </div>
                        </div>

                        <!-- Text Answer -->
                        <div v-else-if="sq.question.type === 'text'" class="mt-2">
                            <p class="text-xs text-gray-500 mb-1">Réponse du candidat:</p>
                            <p class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
                                {{ getAnswer(sq.question)?.answer || 'Aucune réponse' }}
                            </p>
                        </div>

                        <!-- Code Answer -->
                        <div v-else-if="sq.question.type === 'code'" class="mt-2">
                            <p class="text-xs text-gray-500 mb-1">Code soumis:</p>
                            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto">{{ getAnswer(sq.question)?.answer?.code || 'Aucun code' }}</pre>
                            <div v-if="getAnswer(sq.question)?.execution_result" class="mt-2 text-xs">
                                <span class="text-gray-500">Tests: </span>
                                <span :class="getAnswer(sq.question).execution_result.success ? 'text-green-600' : 'text-red-600'">
                                    {{ getAnswer(sq.question).execution_result.passed }}/{{ getAnswer(sq.question).execution_result.total }} passés
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Logs -->
                <div v-if="session.activity_logs.length > 0" class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Journal d'activité</h3>
                    <div class="space-y-2 max-h-60 overflow-y-auto">
                        <div v-for="log in session.activity_logs" :key="log.id"
                            class="flex justify-between items-center text-sm py-2 border-b border-gray-50">
                            <div>
                                <span :class="log.event.includes('tab') || log.event.includes('blur') ? 'text-red-600' : 'text-gray-700'"
                                    class="font-medium">{{ log.event }}</span>
                                <span v-if="log.metadata" class="text-gray-400 text-xs ml-2">{{ JSON.stringify(log.metadata) }}</span>
                            </div>
                            <span class="text-gray-400 text-xs">{{ log.occurred_at }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
