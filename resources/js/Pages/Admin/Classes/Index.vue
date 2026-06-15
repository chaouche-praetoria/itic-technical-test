<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ classes: Object, levels: Array });

const levelFilter = ref('');

const filtered = computed(() => {
    if (!levelFilter.value) return props.classes.data;
    return props.classes.data.filter(c => String(c.academic_level_id) === String(levelFilter.value));
});

function deleteClass(id) {
    if (confirm('Supprimer cette classe ? Les étudiants et évaluations associés seront supprimés.')) {
        router.delete(route('admin.classes.destroy', id));
    }
}
</script>

<template>
    <Head title="Classes" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Classes</h2>
                    <p class="text-xs text-slate-500 font-medium">Gérez vos classes par niveau et invitez vos étudiants.</p>
                </div>
                <Link :href="route('admin.classes.create')"
                    class="bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 font-bold text-xs shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2 w-fit">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouvelle classe
                </Link>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-4 flex items-center gap-3">
                    <label class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Niveau</label>
                    <select v-model="levelFilter" class="rounded-lg border-slate-200 text-sm font-medium focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Tous les niveaux</option>
                        <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                    </select>
                </div>

                <div class="premium-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-[13px] border-collapse">
                            <thead>
                                <tr class="bg-slate-50/50 text-slate-400 text-[10px] uppercase tracking-widest font-bold">
                                    <th class="px-6 py-4 text-left border-b border-slate-100">Classe</th>
                                    <th class="px-6 py-4 text-left border-b border-slate-100">Niveau</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Étudiants</th>
                                    <th class="px-6 py-4 text-center border-b border-slate-100">Évaluations</th>
                                    <th class="px-6 py-4 text-right border-b border-slate-100">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-if="filtered.length === 0">
                                    <td colspan="5" class="px-8 py-20 text-center text-slate-300 font-medium italic">Aucune classe</td>
                                </tr>
                                <tr v-for="c in filtered" :key="c.id" class="hover:bg-slate-50/80 transition-all group">
                                    <td class="px-6 py-4">
                                        <Link :href="route('admin.classes.show', c.id)" class="font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{ c.name }}</Link>
                                        <p class="text-[10px] text-slate-400 font-medium truncate max-w-xs">{{ c.description || 'Pas de description' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-bold uppercase tracking-wider">{{ c.academic_level?.name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">{{ c.students_count }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-slate-700">{{ c.evaluations_count }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link :href="route('admin.classes.show', c.id)" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Gérer</Link>
                                            <Link :href="route('admin.classes.edit', c.id)" class="text-xs font-bold text-slate-500 hover:text-slate-800">Éditer</Link>
                                            <button @click="deleteClass(c.id)" class="text-xs font-bold text-rose-500 hover:text-rose-700">Supprimer</button>
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
