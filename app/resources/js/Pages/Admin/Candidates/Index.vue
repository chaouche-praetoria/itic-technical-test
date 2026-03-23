<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({ candidates: Object, filters: Object });

const search = ref(props.filters.search || '');
const showModal = ref(false);
const searching = ref(false);
const form = useForm({ first_name: '', last_name: '', email: '', phone: '' });

let searchTimer;
watch(search, (val) => {
    searching.value = true;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route('admin.candidates.index'),
            { search: val },
            { preserveState: true, replace: true, onFinish: () => { searching.value = false; } }
        );
    }, 400);
});

function submit() {
    form.post(route('admin.candidates.store'), {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
}
</script>

<template>
    <Head title="Candidats" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Candidats</h2>
                <button @click="showModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    + Nouveau candidat
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="relative max-w-sm">
                        <input v-model="search" type="text" placeholder="Rechercher par nom ou email..."
                            class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full pr-8" />
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2">
                            <svg v-if="searching" class="w-4 h-4 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                            </svg>
                            <svg v-else-if="search" @click="search = ''" class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Nom</th>
                                <th class="px-6 py-3 text-left">Email</th>
                                <th class="px-6 py-3 text-left">Téléphone</th>
                                <th class="px-6 py-3 text-left">Sessions</th>
                                <th class="px-6 py-3 text-left">Inscrit le</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="candidates.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">Aucun candidat</td>
                            </tr>
                            <tr v-for="c in candidates.data" :key="c.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">{{ c.first_name }} {{ c.last_name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ c.email }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ c.phone || '—' }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ c.test_sessions_count }}</td>
                                <td class="px-6 py-4 text-gray-500 text-xs">{{ c.created_at }}</td>
                                <td class="px-6 py-4">
                                    <Link :href="route('admin.candidates.show', c.id)" class="text-indigo-600 hover:underline text-xs">Voir →</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- New Candidate Modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Nouveau candidat</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                            <input v-model="form.first_name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input v-model="form.last_name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input v-model="form.phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="showModal = false" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg">Annuler</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
