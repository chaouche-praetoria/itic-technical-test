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
            <div class="text-6xl mb-4">🎉</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Test terminé !</h1>
            <p class="text-gray-500 mb-8">Merci {{ candidate }}, votre test a été soumis avec succès.</p>

            <div class="bg-indigo-50 rounded-xl p-6 mb-6">
                <div class="text-5xl font-bold mb-1" :class="grade().color">{{ score }}%</div>
                <div class="text-sm font-medium" :class="grade().color">{{ grade().label }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="font-bold text-gray-800">{{ correct_answers }}/{{ total_questions }}</div>
                    <div class="text-gray-500 text-xs">Correctes</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <div class="font-bold text-gray-800">{{ Math.round(duration_seconds / 60) }}min</div>
                    <div class="text-gray-500 text-xs">Durée</div>
                </div>
            </div>

            <div v-if="points_total" class="bg-indigo-50 rounded-xl p-4 mb-6 text-center">
                <div class="text-3xl font-bold text-indigo-700">{{ points_earned }} / {{ points_total }}</div>
                <div class="text-sm text-indigo-500 mt-1">points obtenus</div>
            </div>

            <p class="text-sm text-gray-500">Vos résultats ont été transmis à l'équipe de recrutement. Vous serez contacté prochainement.</p>
        </div>
    </div>
</template>
