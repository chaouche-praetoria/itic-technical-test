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
const totalSeconds = props.session.question_timer
    ? props.questions.length * props.session.question_time_seconds
    : props.session.duration_minutes * 60;
const remaining = ref(totalSeconds - Math.floor((Date.now() - startTime) / 1000));

const timerColor = computed(() => {
    if (remaining.value > 300) return 'text-slate-800';
    if (remaining.value > 60) return 'text-amber-500';
    return 'text-rose-500 animate-pulse';
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
    if (pct > 0.5) return 'text-slate-400';
    if (pct > 0.25) return 'text-amber-500';
    return 'text-rose-500 animate-pulse';
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
    <Head title="Certification en cours" />

    <div class="min-h-screen bg-slate-50 select-none font-sans overflow-x-hidden" @contextmenu.prevent>
        
        <!-- Header / Progress Area -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
            <!-- Security Alerts Overlay -->
            <div v-if="tabWarnings > 0 || copyPasteWarnings > 0" class="bg-rose-600 text-white px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest flex items-center justify-center gap-4">
                <span v-if="tabWarnings > 0" class="flex items-center gap-1.5 animate-pulse">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Alerte Sécurité: Changement d'onglet détecté ({{ tabWarnings }})
                </span>
                <span v-if="copyPasteWarnings > 0" class="flex items-center gap-1.5 animate-pulse border-l border-white/20 pl-4">
                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Copier/Coller interdit ({{ copyPasteWarnings }} tentatives)
                </span>
            </div>

            <div class="max-w-[1400px] mx-auto px-6 h-20 flex items-center justify-between gap-8">
                <!-- Branding & User -->
                <div class="flex items-center gap-5 shrink-0">
                    <div class="size-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg shadow-xl shadow-slate-200">
                        {{ candidate.name.charAt(0) }}
                    </div>
                    <div>
                        <h1 class="font-bold text-slate-900 text-sm leading-tight">{{ candidate.name }}</h1>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Session d'évaluation</p>
                    </div>
                </div>

                <!-- Global Progress -->
                <div class="flex-1 max-w-xl hidden md:block">
                    <div class="flex items-center justify-between mb-2 px-1">
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Progression globale</span>
                        <span class="text-[10px] font-bold text-slate-900 uppercase tracking-widest">{{ progress }}%</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-slate-900 transition-all duration-500 ease-out" :style="{ width: progress + '%' }"></div>
                    </div>
                </div>

                <!-- Timers & Actions -->
                <div class="flex items-center gap-8 shrink-0">
                    <div v-if="session.question_timer" class="flex flex-col items-end">
                        <span :class="questionTimerColor" class="text-xl font-black font-mono leading-none tracking-tighter">{{ questionTimerDisplay }}</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Temps question</span>
                    </div>
                    
                    <div class="flex flex-col items-end border-l border-slate-100 pl-8">
                        <span :class="timerColor" class="text-3xl font-black font-mono leading-none tracking-tighter">{{ timerDisplay }}</span>
                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Restant total</span>
                    </div>

                    <button @click="confirmSubmit" :disabled="submitting"
                        class="bg-rose-600 text-white px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-rose-700 transition-all shadow-xl shadow-rose-100 active:scale-[0.98] disabled:opacity-50">
                        Soumettre
                    </button>
                </div>
            </div>
        </header>

        <main class="max-w-[1400px] mx-auto p-8 flex flex-col lg:flex-row gap-10">
            
            <!-- Left Navigation Sidebar -->
            <aside class="w-full lg:w-72 shrink-0">
                <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 sticky top-32">
                    <div class="flex items-center justify-between mb-8">
                         <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">Navigation</h3>
                         <div class="flex items-center gap-1.5">
                             <div class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                             <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Actif</span>
                         </div>
                    </div>

                    <div class="grid grid-cols-5 sm:grid-cols-6 lg:grid-cols-4 gap-3">
                        <div v-for="(q, i) in questions" :key="q.id"
                            :class="[
                                'relative aspect-square rounded-xl text-xs font-black flex items-center justify-center border-2',
                                i === currentIndex
                                    ? 'bg-slate-900 text-white border-slate-900 shadow-xl shadow-slate-200 scale-110 z-10'
                                    : (answers[q.id] !== undefined
                                        ? 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                        : 'bg-slate-50 text-slate-400 border-slate-50')
                            ]">
                            {{ i + 1 }}
                            <div v-if="answers[q.id] !== undefined && i !== currentIndex" class="absolute -top-1 -right-1 size-3 rounded-full bg-emerald-500 border-2 border-white"></div>
                        </div>
                    </div>

                    <div class="mt-10 pt-8 border-t border-slate-50 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Répondues</span>
                            <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">{{ answeredCount }} / {{ questions.length }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-50 rounded-full overflow-hidden">
                            <div class="h-full bg-emerald-500 transition-all duration-500" :style="{ width: (answeredCount/questions.length)*100 + '%' }"></div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Question Content Area -->
            <div class="flex-1 min-w-0">
                <div class="bg-white rounded-[2.5rem] p-10 lg:p-14 shadow-sm border border-slate-100 relative overflow-hidden group">
                    <!-- Background Decoration -->
                    <div class="absolute top-0 right-0 p-12 opacity-[0.03] group-hover:opacity-[0.05] transition-opacity">
                         <svg class="size-64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>

                    <div class="relative z-10 transition-all duration-300 animate-reveal" :key="currentIndex">
                        <!-- Question Header Meta -->
                        <div class="flex items-center justify-between gap-4 mb-10 pb-10 border-b border-slate-50">
                            <div class="flex items-center gap-3">
                                <span class="bg-slate-900 text-white text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-wider shadow-lg">Q{{ currentIndex + 1 }}</span>
                                <span :class="{
                                    'bg-indigo-50 text-indigo-600 border-indigo-100': currentQuestion.type === 'mcq',
                                    'bg-purple-50 text-purple-600 border-purple-100': currentQuestion.type === 'text',
                                    'bg-amber-50 text-amber-600 border-amber-100': currentQuestion.type === 'code',
                                }" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border">
                                    {{ { mcq: 'QCM', text: 'Réponse rédigée', code: 'Programmation' }[currentQuestion.type] }}
                                </span>
                                <span :class="{
                                    'bg-emerald-50 text-emerald-600 border-emerald-100': currentQuestion.difficulty === 'easy',
                                    'bg-amber-50 text-amber-600 border-amber-100': currentQuestion.difficulty === 'medium',
                                    'bg-rose-50 text-rose-600 border-rose-100': currentQuestion.difficulty === 'hard',
                                }" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider border">
                                    {{ { easy: 'Basique', medium: 'Intermédiaire', hard: 'Avancé' }[currentQuestion.difficulty] }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Valeur</span>
                                <span class="text-sm font-black text-slate-900">{{ currentQuestion.max_points }} pts</span>
                            </div>
                        </div>

                        <!-- Statement -->
                        <div class="mb-14">
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 leading-tight mb-8">
                                {{ currentQuestion.statement }}
                            </h2>
                        </div>

                        <!-- MCQ Area -->
                        <div v-if="currentQuestion.type === 'mcq'" class="grid gap-4 max-w-3xl">
                            <div v-if="currentQuestion.multiple_answers" class="mb-2 flex items-center gap-2">
                                <span class="size-1.5 rounded-full bg-indigo-500"></span>
                                <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">Plusieurs choix possibles</span>
                            </div>
                            <button v-for="(choice, idx) in currentQuestion.choices" :key="choice.id"
                                @click="toggleChoice(currentQuestion.id, choice.id, currentQuestion.multiple_answers)"
                                :class="[
                                    'group flex items-center gap-5 p-6 rounded-2xl border-2 text-left transition-all duration-200 hover:scale-[1.01]',
                                    isChoiceSelected(currentQuestion.id, choice.id)
                                        ? 'border-slate-900 bg-slate-900 text-white shadow-2xl shadow-slate-200'
                                        : 'border-slate-100 bg-slate-50 hover:border-slate-300 hover:bg-white'
                                ]">
                                <div :class="[
                                    'size-7 rounded-lg border-2 flex items-center justify-center shrink-0 font-black text-xs transition-colors',
                                    isChoiceSelected(currentQuestion.id, choice.id)
                                        ? 'bg-white border-white text-slate-900'
                                        : 'bg-white border-slate-200 text-slate-300 group-hover:border-slate-300'
                                ]">
                                    {{ String.fromCharCode(65 + idx) }}
                                </div>
                                <span class="text-base font-bold">{{ choice.text }}</span>
                                <div v-if="isChoiceSelected(currentQuestion.id, choice.id)" class="ml-auto">
                                     <svg class="size-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </button>
                        </div>

                        <!-- Text Area -->
                        <div v-else-if="currentQuestion.type === 'text'" class="max-w-4xl">
                            <textarea
                                :value="answers[currentQuestion.id] || ''"
                                @input="setAnswer(currentQuestion.id, $event.target.value)"
                                rows="10"
                                placeholder="Commencez à rédiger votre réponse ici..."
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-[2rem] p-8 text-lg font-medium text-slate-700 focus:ring-4 focus:ring-slate-900/5 focus:border-slate-900 focus:bg-white transition-all resize-none placeholder:text-slate-300 leading-relaxed shadow-inner"></textarea>
                        </div>

                        <!-- Programming Area -->
                        <div v-else-if="currentQuestion.type === 'code'" class="space-y-6">
                            <div class="flex items-center gap-6 bg-slate-100 p-2 rounded-2xl w-fit">
                                <button v-for="lang in ['javascript', 'python', 'php', 'java']" :key="lang"
                                    @click="selectedLanguage = lang"
                                    :class="[
                                        'px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all',
                                        selectedLanguage === lang ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600'
                                    ]">
                                    {{ lang }}
                                </button>
                            </div>
                            
                            <div class="bg-slate-900 p-1 rounded-[2rem] shadow-2xl relative">
                                <div class="px-6 py-3 flex items-center justify-between border-b border-white/5">
                                    <div class="flex gap-1.5">
                                        <div class="size-2.5 rounded-full bg-rose-500"></div>
                                        <div class="size-2.5 rounded-full bg-amber-500"></div>
                                        <div class="size-2.5 rounded-full bg-emerald-500"></div>
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-500 font-bold uppercase tracking-widest">{{ selectedLanguage }} editor</span>
                                </div>
                                <textarea
                                    v-model="codeAnswer"
                                    @input="setAnswer(currentQuestion.id, { code: codeAnswer, language: selectedLanguage })"
                                    rows="15"
                                    class="w-full bg-transparent border-none p-10 text-base font-mono text-slate-300 focus:ring-0 resize-none custom-scrollbar"
                                    placeholder="// Your logic goes here..."></textarea>
                                
                                <div class="p-4 flex justify-between items-center bg-slate-800/30 rounded-b-[1.9rem]">
                                    <button @click="executeCode" :disabled="codeExecuting || !codeAnswer.trim()"
                                        class="bg-emerald-500 text-white px-8 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 transition-all shadow-xl shadow-emerald-500/20 disabled:opacity-30 flex items-center gap-3 active:scale-[0.98]">
                                        <svg v-if="!codeExecuting" class="size-3.5 fill-current" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        <div v-else class="size-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                                        {{ codeExecuting ? 'Exécution...' : 'Tester le code' }}
                                    </button>

                                    <div v-if="executionResult" class="flex items-center gap-4">
                                        <span :class="executionResult.success ? 'text-emerald-400' : 'text-rose-400'" class="text-[10px] font-black uppercase tracking-widest flex items-center gap-2">
                                            <div :class="executionResult.success ? 'bg-emerald-400' : 'bg-rose-400'" class="size-1.5 rounded-full animate-pulse"></div>
                                            {{ executionResult.success ? 'Succès' : 'Erreurs détectées' }}
                                        </span>
                                        <div class="h-4 w-px bg-white/10"></div>
                                        <span class="text-white font-mono text-xs font-bold">{{ executionResult.passed || 0 }} / {{ executionResult.total || 0 }} tests</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Execution Logs -->
                            <div v-if="executionResult && (executionResult.output || executionResult.error)" 
                                class="bg-slate-50 border-2 border-slate-100 rounded-3xl p-8 animate-reveal">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Output / Consoles</h4>
                                <pre v-if="executionResult.output" class="text-xs font-mono text-slate-700 whitespace-pre-wrap">{{ executionResult.output }}</pre>
                                <pre v-if="executionResult.error" class="text-xs font-mono text-rose-500 whitespace-pre-wrap bg-rose-50 border border-rose-100 p-4 rounded-xl">{{ executionResult.error }}</pre>
                            </div>
                        </div>

                        <!-- Interaction Navigation -->
                        <div class="mt-20 pt-10 border-t border-slate-50 flex justify-end items-center gap-6">
                            <div class="grow text-[10px] font-black text-slate-300 uppercase tracking-[0.2em] hidden sm:block">
                                Test de Compétences Automatisé
                            </div>

                            <button v-if="currentIndex < questions.length - 1" @click="next"
                                class="w-full sm:w-auto px-10 py-4 text-[10px] font-black text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-2xl shadow-slate-200 uppercase tracking-widest flex items-center justify-center gap-2 active:scale-[0.98]">
                                Suivante
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button v-else @click="confirmSubmit" :disabled="submitting"
                                class="w-full sm:w-auto px-12 py-4 text-[10px] font-black text-white bg-rose-600 rounded-2xl hover:bg-rose-700 transition-all shadow-2xl shadow-rose-200 uppercase tracking-widest flex items-center justify-center gap-2 active:scale-[0.98] animate-bounce-subtle">
                                Finaliser le test
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer Info -->
        <footer class="max-w-[1400px] mx-auto px-8 py-10 opacity-30">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 border-t border-slate-200 pt-8">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">© 2024 ITIC Paris Tech Assessment Framework</span>
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">Support: support@iticparis.com</span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-reveal {
    animation: reveal 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes reveal {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}

.animate-bounce-subtle {
    animation: bounce-subtle 2s infinite;
}

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #475569; }
</style>
