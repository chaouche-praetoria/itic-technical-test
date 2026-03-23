<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ templates: Object });

function deleteTemplate(id) {
    if (confirm('Supprimer ce template ?')) {
        router.delete(route('admin.templates.destroy', id));
    }
}
</script>

<template>
    <Head title="Templates de tests" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Templates de tests</h2>
                <Link :href="route('admin.templates.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    + Nouveau template
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Nom</th>
                                <th class="px-6 py-3 text-left">Domaine</th>
                                <th class="px-6 py-3 text-left">Niveau</th>
                                <th class="px-6 py-3 text-left">Durée</th>
                                <th class="px-6 py-3 text-left">Sessions</th>
                                <th class="px-6 py-3 text-left">Statut</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="templates.data.length === 0">
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">Aucun template</td>
                            </tr>
                            <tr v-for="t in templates.data" :key="t.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-800">{{ t.name }}</p>
                                    <p class="text-xs text-gray-400">{{ t.description }}</p>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ t.domain?.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ t.academic_level?.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ t.duration_minutes }} min</td>
                                <td class="px-6 py-4 text-gray-600">{{ t.test_sessions_count }}</td>
                                <td class="px-6 py-4">
                                    <span :class="t.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                        class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ t.is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <Link :href="route('admin.templates.edit', t.id)" class="text-indigo-600 hover:underline text-xs">Modifier</Link>
                                    <button @click="deleteTemplate(t.id)" class="text-red-500 hover:underline text-xs">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
