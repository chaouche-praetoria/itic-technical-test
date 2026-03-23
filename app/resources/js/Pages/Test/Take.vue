<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    session: Object,
    candidate: Object,
    questions: Array,
});

// State
const currentIndex = ref(0);
const answers = ref({});
const submitting = ref(false);
const submitted = ref(false);
const codeExecuting = ref(false);
const executionResult = ref(null);
const tabWarnings = ref(0);
const copyPasteWarnings = ref(0);

const currentQuestion = computed(() => props.questions[currentIndex.value]);
const progress = computed(() => Math.round(((currentIndex.value + 1) / props.questions.length) * 100));

// Timer
const startTime = new Date(props.session.started_at).getTime();
const totalSeconds = props.session.duration_minutes * 60;
const remaining = ref(totalSeconds - Math.floor((Date.now() - startTime) / 1000));

const timerColor = computed(() => {
    if (remaining.value > 300) return 'text-gray-800';
    if (remaining.value > 60) return 'text-amber-600';
    return 'text-red-600';
});

const timerDisplay = computed(() => {
    const m = Math.floor(Math.max(0, remaining.value) / 60);
    const s = Math.max(0, remaining.value) % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
});

// Per-question timer
let questionTimerInterval;
const questionRemaining = ref(props.session.question_time_seconds || 0);

const questionTimerDisplay = computed(() => {
    const t = Math.max(0, questionRemaining.value);
    const m = Math.floor(t / 60);
    const s = t % 60;
    return m > 0 ? `${m}:${s.toString().padStart(2, '0')}` : `${s}s`;
});

const questionTimerColor = computed(() => {
    const total = props.session.question_time_seconds || 1;
    const pct = questionRemaining.value / total;
    if (pct > 0.5) return 'text-gray-700';
    if (pct > 0.25) return 'text-amber-600';
    return 'text-red-600 animate-pulse';
});

function startQuestionTimer() {
    clearInterval(questionTimerInterval);
    if (!props.session.question_timer) return;
    questionRemaining.value = props.session.question_time_seconds;
    questionTimerInterval = setInterval(() => {
        questionRemaining.value--;
        if (questionRemaining.value <= 0) {
            clearInterval(questionTimerInterval);
            logActivity('question_timer_expired', { question_id: currentQuestion.value?.id, index: currentIndex.value });
            if (currentIndex.value < props.questions.length - 1) {
                currentIndex.value++;
            } else {
                submitTest();
            }
        }
    }, 1000);
}

let timerInterval;
onMounted(() => {
    timerInterval = setInterval(() => {
        remaining.value--;
        if (remaining.value <= 0) {
            clearInterval(timerInterval);
            submitTest();
        }
    }, 1000);

    startQuestionTimer();

    // Anti-cheat: detect tab changes
    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('contextmenu', (e) => e.preventDefault());
    document.addEventListener('copy', handleCopyPaste);
    document.addEventListener('paste', handleCopyPaste);
    document.addEventListener('cut', handleCopyPaste);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    clearInterval(timerInterval);
    clearInterval(questionTimerInterval);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('copy', handleCopyPaste);
    document.removeEventListener('paste', handleCopyPaste);
    document.removeEventListener('cut', handleCopyPaste);
    document.removeEventListener('keydown', handleKeydown);
});

function handleVisibilityChange() {
    if (document.hidden) {
        tabWarnings.value++;
        logActivity('tab_switch', { count: tabWarnings.value });
    } else {
        logActivity('tab_return', { count: tabWarnings.value });
    }
}

function handleCopyPaste(e) {
    e.preventDefault();
    copyPasteWarnings.value++;
    logActivity('copy_paste_attempt', { type: e.type, count: copyPasteWarnings.value });
}

function handleKeydown(e) {
    if (e.ctrlKey || e.metaKey) {
        if (['c', 'v', 'x'].includes(e.key.toLowerCase())) {
            e.preventDefault();
            copyPasteWarnings.value++;
            logActivity('copy_paste_attempt', { type: `keyboard_ctrl_${e.key.toLowerCase()}`, count: copyPasteWarnings.value });
        }
    }
}

function logActivity(event, metadata = {}) {
    axios.post(`/test/${props.session.token}/activity`, { event, metadata });
}

// Answer handling
function setAnswer(questionId, value) {
    answers.value[questionId] = value;
    saveAnswer(questionId);
}

function toggleChoice(questionId, choiceId, multiple) {
    if (multiple) {
        const current = answers.value[questionId] || [];
        const idx = current.indexOf(choiceId);
        if (idx === -1) {
            answers.value[questionId] = [...current, choiceId];
        } else {
            answers.value[questionId] = current.filter(id => id !== choiceId);
        }
    } else {
        answers.value[questionId] = [choiceId];
    }
    saveAnswer(questionId);
}

function isChoiceSelected(questionId, choiceId) {
    const ans = answers.value[questionId] || [];
    return ans.includes(choiceId);
}

let saveTimeout;
function saveAnswer(questionId) {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        axios.post(`/test/${props.session.token}/answer`, {
            question_id: questionId,
            answer: answers.value[questionId],
            time_spent_seconds: null,
        });
    }, 500);
}

// Code execution
const codeAnswer = ref('');
const selectedLanguage = ref(currentQuestion.value?.default_language || 'javascript');

watch(currentIndex, () => {
    executionResult.value = null;
    startQuestionTimer();
    const q = currentQuestion.value;
    if (q?.type === 'code') {
        const existing = answers.value[q.id];
        codeAnswer.value = existing?.code || '';
        selectedLanguage.value = existing?.language || q.default_language || 'javascript';
    }
});

async function executeCode() {
    codeExecuting.value = true;
    executionResult.value = null;
    const q = currentQuestion.value;
    answers.value[q.id] = { code: codeAnswer.value, language: selectedLanguage.value };

    try {
        const res = await axios.post(`/test/${props.session.token}/execute`, {
            question_id: q.id,
            code: codeAnswer.value,
            language: selectedLanguage.value,
        });
        executionResult.value = res.data;
    } catch (e) {
        executionResult.value = { error: 'Erreur lors de l\'exécution' };
    } finally {
        codeExecuting.value = false;
    }
}

function next() {
    if (currentIndex.value < props.questions.length - 1) {
        currentIndex.value++;
    }
}
function prev() {
    if (currentIndex.value > 0) {
        currentIndex.value--;
    }
}

async function submitTest() {
    if (submitting.value) return;
    submitting.value = true;
    clearInterval(timerInterval);
    clearInterval(questionTimerInterval);

    try {
        const res = await axios.post(`/test/${props.session.token}/submit`);
        window.location.href = res.data.redirect;
    } catch (e) {
        submitting.value = false;
    }
}

function confirmSubmit() {
    if (confirm('Êtes-vous sûr de vouloir soumettre votre test ? Cette action est irréversible.')) {
        submitTest();
    }
}

const answeredCount = computed(() => Object.keys(answers.value).length);
</script>

<template>
    <Head title="Test en cours" />

    <!-- Anti-cheat: no select -->
    <div class="min-h-screen bg-gray-50 select-none" @contextmenu.prevent>

        <!-- Tab Warning -->
        <div v-if="tabWarnings > 0" class="fixed top-0 left-0 right-0 z-50 bg-red-600 text-white text-center py-2 text-sm font-medium">
            ⚠️ Changement d'onglet détecté ({{ tabWarnings }} fois). Cette activité est enregistrée.
        </div>

        <!-- Copy/Paste Warning -->
        <div v-if="copyPasteWarnings > 0" class="fixed left-0 right-0 z-50 bg-orange-500 text-white text-center py-2 text-sm font-medium"
            :style="tabWarnings > 0 ? 'top: 32px' : 'top: 0'">
            🚫 Copier/coller interdit — tentative détectée ({{ copyPasteWarnings }} fois). Cette activité est enregistrée.
        </div>

        <!-- Header -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-40"
            :style="{ marginTop: (tabWarnings > 0 ? 32 : 0) + (copyPasteWarnings > 0 ? 32 : 0) + 'px' }">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <div>
                    <h1 class="font-semibold text-gray-800 text-sm">{{ candidate.name }}</h1>
                    <p class="text-xs text-gray-500">Question {{ currentIndex + 1 }}/{{ questions.length }}</p>
                </div>

                <!-- Timers -->
                <div class="flex items-center gap-4">
                    <!-- Per-question timer -->
                    <div v-if="session.question_timer" class="text-center">
                        <div :class="questionTimerColor" class="text-lg font-mono font-bold">{{ questionTimerDisplay }}</div>
                        <div class="text-xs text-gray-400">par question</div>
                    </div>
                    <!-- Global timer -->
                    <div :class="timerColor" class="text-2xl font-mono font-bold">
                        {{ timerDisplay }}
                    </div>
                </div>

                <button @click="confirmSubmit" :disabled="submitting"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 disabled:opacity-50">
                    Terminer le test
                </button>
            </div>

            <!-- Progress bar -->
            <div class="h-1 bg-gray-100">
                <div class="h-1 bg-indigo-500 transition-all" :style="{ width: progress + '%' }"></div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 py-8 flex gap-6">
            <!-- Question Navigator -->
            <div class="w-48 flex-shrink-0">
                <div class="bg-white rounded-xl shadow p-4 sticky top-24">
                    <p class="text-xs font-medium text-gray-600 mb-3">Navigation</p>
                    <div class="grid grid-cols-5 gap-1">
                        <button v-for="(q, i) in questions" :key="q.id"
                            @click="currentIndex = i"
                            :class="[
                                'w-8 h-8 rounded text-xs font-medium',
                                i === currentIndex ? 'bg-indigo-600 text-white' :
                                answers[q.id] !== undefined ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            ]">
                            {{ i + 1 }}
                        </button>
                    </div>
                    <div class="mt-4 text-xs text-gray-500">
                        <div class="flex items-center gap-2 mb-1">
                            <div class="w-3 h-3 rounded bg-green-100"></div> Répondu ({{ answeredCount }})
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded bg-gray-100"></div> Non répondu ({{ questions.length - answeredCount }})
                        </div>
                    </div>
                </div>
            </div>

            <!-- Question Content -->
            <div class="flex-1">
                <div class="bg-white rounded-xl shadow p-6">
                    <!-- Question Header -->
                    <div class="flex items-center gap-3 mb-4 flex-wrap">
                        <span :class="{
                            'bg-blue-100 text-blue-700': currentQuestion.type === 'mcq',
                            'bg-purple-100 text-purple-700': currentQuestion.type === 'text',
                            'bg-orange-100 text-orange-700': currentQuestion.type === 'code',
                        }" class="px-2 py-1 rounded-full text-xs font-medium">
                            {{ { mcq: 'QCM', text: 'Texte libre', code: 'Code' }[currentQuestion.type] }}
                        </span>
                        <span :class="{
                            'bg-green-100 text-green-700': currentQuestion.difficulty === 'easy',
                            'bg-yellow-100 text-yellow-700': currentQuestion.difficulty === 'medium',
                            'bg-red-100 text-red-700': currentQuestion.difficulty === 'hard',
                        }" class="px-2 py-1 rounded-full text-xs font-medium">
                            {{ { easy: 'Facile', medium: 'Moyen', hard: 'Difficile' }[currentQuestion.difficulty] }}
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                            {{ currentQuestion.max_points }} pts
                        </span>
                        <span class="text-sm text-gray-500 ml-auto">Question {{ currentIndex + 1 }}/{{ questions.length }}</span>
                    </div>

                    <!-- Statement -->
                    <div class="text-gray-800 leading-relaxed mb-6 whitespace-pre-wrap">{{ currentQuestion.statement }}</div>

                    <!-- MCQ -->
                    <div v-if="currentQuestion.type === 'mcq'" class="space-y-3">
                        <p v-if="currentQuestion.multiple_answers" class="text-xs text-gray-500 italic">
                            Plusieurs réponses possibles
                        </p>
                        <div v-for="choice in currentQuestion.choices" :key="choice.id"
                            @click="toggleChoice(currentQuestion.id, choice.id, currentQuestion.multiple_answers)"
                            :class="[
                                'flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                isChoiceSelected(currentQuestion.id, choice.id)
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                            ]">
                            <div :class="[
                                'w-5 h-5 border-2 flex items-center justify-center flex-shrink-0',
                                currentQuestion.multiple_answers ? 'rounded' : 'rounded-full',
                                isChoiceSelected(currentQuestion.id, choice.id)
                                    ? 'border-indigo-500 bg-indigo-500'
                                    : 'border-gray-300'
                            ]">
                                <svg v-if="isChoiceSelected(currentQuestion.id, choice.id)" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-gray-700">{{ choice.text }}</span>
                        </div>
                    </div>

                    <!-- Text -->
                    <div v-else-if="currentQuestion.type === 'text'">
                        <textarea
                            :value="answers[currentQuestion.id] || ''"
                            @input="setAnswer(currentQuestion.id, $event.target.value)"
                            rows="8"
                            placeholder="Rédigez votre réponse ici..."
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>

                    <!-- Code -->
                    <div v-else-if="currentQuestion.type === 'code'" class="space-y-4">
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-medium text-gray-700">Langage:</label>
                            <select v-model="selectedLanguage" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                                <option value="javascript">JavaScript</option>
                                <option value="python">Python</option>
                                <option value="php">PHP</option>
                                <option value="java">Java</option>
                                <option value="cpp">C++</option>
                            </select>
                        </div>
                        <textarea
                            v-model="codeAnswer"
                            @input="setAnswer(currentQuestion.id, { code: codeAnswer, language: selectedLanguage })"
                            rows="12"
                            placeholder="// Écrivez votre code ici..."
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm font-mono focus:ring-indigo-500 focus:border-indigo-500 resize-none bg-gray-900 text-gray-100"></textarea>

                        <button @click="executeCode" :disabled="codeExecuting || !codeAnswer.trim()"
                            class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                            <span v-if="codeExecuting">Exécution...</span>
                            <span v-else>▶ Tester le code</span>
                        </button>

                        <div v-if="executionResult" :class="executionResult.success ? 'bg-green-50 border-green-300' : 'bg-red-50 border-red-300'"
                            class="border rounded-xl p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <span :class="executionResult.success ? 'text-green-700' : 'text-red-700'" class="font-medium text-sm">
                                    {{ executionResult.success ? '✓ Tous les tests passent' : '✗ Des tests échouent' }}
                                </span>
                                <span v-if="executionResult.passed !== undefined" class="text-xs text-gray-500">
                                    ({{ executionResult.passed }}/{{ executionResult.total }})
                                </span>
                            </div>
                            <pre v-if="executionResult.output" class="text-xs text-gray-700 whitespace-pre-wrap">{{ executionResult.output }}</pre>
                            <pre v-if="executionResult.error" class="text-xs text-red-600 whitespace-pre-wrap">{{ executionResult.error }}</pre>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-100">
                        <button @click="prev" :disabled="currentIndex === 0"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40">
                            ← Précédente
                        </button>
                        <button v-if="currentIndex < questions.length - 1" @click="next"
                            class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                            Suivante →
                        </button>
                        <button v-else @click="confirmSubmit" :disabled="submitting"
                            class="px-6 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium">
                            {{ submitting ? 'Envoi...' : 'Soumettre le test' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
