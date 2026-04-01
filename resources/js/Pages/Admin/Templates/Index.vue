<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ templates: Object });

function deleteTemplate(id) {
    if (confirm('Supprimer ce template ?')) {
        router.delete(route('admin.templates.destroy', id), {
            onSuccess: () => alert('Template supprimé'),
        });
    }
}
</script>

<template>
    <Head title="Templates de tests" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Templates de tests</h2>
                    <p class="text-sm text-slate-500 font-medium">Gérez vos modèles d'évaluations par domaine et niveau.</p>
                </div>
                <Link :href="route('admin.templates.create')" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouveau template
                </Link>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-xs uppercase tracking-widest font-bold">
                                    <th class="px-8 py-5 text-left border-b border-slate-100 uppercase">Template / Modèle</th>
                                    <th class="px-8 py-5 text-left border-b border-slate-100 uppercase">Domaine & Niveau</th>
                                    <th class="px-8 py-5 text-center border-b border-slate-100 uppercase">Sessions</th>
                                    <th class="px-8 py-5 text-center border-b border-slate-100 uppercase">Statut</th>
                                    <th class="px-8 py-5 text-right border-b border-slate-100 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="templates.data.length === 0">
                                    <td colspan="5" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucun template enregistré</td>
                                </tr>
                                <tr v-for="t in templates.data" :key="t.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-800 text-base mb-0.5 group-hover:text-indigo-600 transition-colors">{{ t.name }}</span>
                                            <span class="text-xs text-slate-400 font-medium line-clamp-1 truncate max-w-xs">{{ t.description || 'Pas de description' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-slate-700 font-semibold">{{ t.domain?.name }}</span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ t.academic_level?.name || 'Tous niveaux' }} • {{ t.duration_minutes }} min</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <div class="inline-flex size-10 rounded-xl bg-slate-50 text-slate-500 items-center justify-center font-bold border border-slate-100">
                                            {{ t.test_sessions_count }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span :class="t.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                            class="px-3 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                                            {{ t.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex justify-end gap-2">
                                            <Link :href="route('admin.templates.edit', t.id)" 
                                                class="size-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all shadow-sm shadow-indigo-100/20">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </Link>
                                            <button @click="deleteTemplate(t.id)" 
                                                class="size-9 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-sm shadow-rose-100/20">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
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
