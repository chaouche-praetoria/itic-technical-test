<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    score: Number,
    candidate: String,
    duration_seconds: Number,
    total_questions: Number,
    correct_answers: Number,
    points_earned: Number,
    points_total: Number,
});

const grade = () => {
    if (props.score >= 80) return { label: 'Excellent', color: 'text-green-600' };
    if (props.score >= 60) return { label: 'Bien', color: 'text-blue-600' };
    if (props.score >= 40) return { label: 'Passable', color: 'text-amber-600' };
    return { label: 'Insuffisant', color: 'text-red-600' };
};
</script>

<template>
    <Head title="Test terminé" />
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-lg p-10 max-w-md w-full text-center">
            <div class="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Test terminé !</h1>
            <p class="text-gray-600 mb-6">Merci <span class="font-semibold text-gray-800">{{ candidate }}</span>, votre test technique a été soumis avec succès.</p>

            <div class="border-t border-gray-100 pt-6">
                <p class="text-gray-500 leading-relaxed">
                    Le service concerné a bien reçu vos réponses et reviendra vers vous rapidement pour la suite de votre candidature.
                </p>
            </div>
        </div>
    </div>
</template>
