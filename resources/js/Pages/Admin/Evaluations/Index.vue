<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ evaluations: Object });

function togglePublish(id) {
    router.post(route('admin.evaluations.publish', id), {}, { preserveScroll: true });
}

function deleteEvaluation(id) {
    if (confirm('Supprimer cette évaluation ?')) {
        router.delete(route('admin.evaluations.destroy', id));
    }
}
</script>

<template>
    <Head title="Évaluations" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Évaluations</h2>
                    <p class="text-xs text-slate-500 font-medium">Rédigez des examens chronométrés pour vos classes.</p>
                </div>
                <Link :href="route('admin.evaluations.create')"
                    class="bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 font-bold text-xs shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 w-fit">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouvelle évaluation
                </Link>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="px-6 py-4 text-left border-b border-slate-100">Évaluation</th>
                                    <th class="px-6 py-4 text-left border-b border-slate-100">Classe</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Durée</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Questions</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Tentatives</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Statut</th>
                                    <th class="px-6 py-4 text-right border-b border-slate-100">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="evaluations.data.length === 0">
                                    <td colspan="7" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucune évaluation</td>
                                </tr>
                                <tr v-for="ev in evaluations.data" :key="ev.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ ev.title }}</span>
                                        <p class="text-[10px] text-slate-400 font-medium truncate max-w-xs">{{ ev.subject || '—' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600">
                                        {{ ev.classroom?.name }}
                                        <span class="text-[10px] text-slate-400 block">{{ ev.classroom?.academic_level?.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">{{ ev.time_limit_minutes }} min</td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">{{ ev.questions_count }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">{{ ev.attempts_count }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <button @click="togglePublish(ev.id)"
                                            :class="ev.is_published ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500'"
                                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                            {{ ev.is_published ? 'Publiée' : 'Brouillon' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link v-if="ev.is_published" :href="route('admin.evaluations.send', ev.id)" class="text-xs font-bold text-emerald-600 hover:text-emerald-800">Envoyer</Link>
                                            <Link :href="route('admin.evaluations.attempts', ev.id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Copies</Link>
                                            <Link :href="route('admin.evaluations.edit', ev.id)" class="text-xs font-bold text-slate-500 hover:text-slate-800">Éditer</Link>
                                            <button @click="deleteEvaluation(ev.id)" class="text-xs font-bold text-rose-500 hover:text-rose-700">Supprimer</button>
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
</template>
