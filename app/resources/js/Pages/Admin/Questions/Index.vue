<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    questions: Object,
    domains: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const domainId = ref(props.filters.domain_id || '');
const difficulty = ref(props.filters.difficulty || '');
const filtering = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);

let filterTimer;
function applyFilters() {
    filtering.value = true;
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => {
        router.get(route('admin.questions.index'), {
            search: search.value,
            type: type.value,
            domain_id: domainId.value,
            difficulty: difficulty.value,
        }, { preserveState: true, replace: true, onFinish: () => { filtering.value = false; } });
    }, 400);
}

watch([search, type, domainId, difficulty], applyFilters);

const typeLabel = { mcq: 'QCM', text: 'Texte libre', code: 'Code' };
const difficultyLabel = { easy: 'Facile', medium: 'Moyen', hard: 'Difficile' };
const typeClass = { mcq: 'bg-blue-100 text-blue-700', text: 'bg-purple-100 text-purple-700', code: 'bg-orange-100 text-orange-700' };
const diffClass = { easy: 'bg-green-100 text-green-700', medium: 'bg-yellow-100 text-yellow-700', hard: 'bg-red-100 text-red-700' };

function deleteQuestion(id) {
    deleteTarget.value = id;
}

function confirmDelete() {
    deleting.value = true;
    router.delete(route('admin.questions.destroy', deleteTarget.value), {
        onFinish: () => { deleting.value = false; deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Questions" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Banque de questions</h2>
                <Link :href="route('admin.questions.create')" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    + Nouvelle question
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow p-4 flex flex-wrap gap-3 items-center">
                    <div class="relative flex-1 min-w-40">
                        <input v-model="search" type="text" placeholder="Rechercher..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm w-full pr-8" />
                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2">
                            <svg v-if="filtering" class="w-4 h-4 text-indigo-400 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                            </svg>
                            <svg v-else-if="search" @click="search = ''" class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </div>
                    <select v-model="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Tous les types</option>
                        <option value="mcq">QCM</option>
                        <option value="text">Texte libre</option>
                        <option value="code">Code</option>
                    </select>
                    <select v-model="domainId" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Tous les domaines</option>
                        <option v-for="d in domains" :key="d.id" :value="d.id">{{ d.name }}</option>
                    </select>
                    <select v-model="difficulty" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toutes les difficultés</option>
                        <option value="easy">Facile</option>
                        <option value="medium">Moyen</option>
                        <option value="hard">Difficile</option>
                    </select>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                            <tr>
                                <th class="px-6 py-3 text-left">Question</th>
                                <th class="px-6 py-3 text-left">Type</th>
                                <th class="px-6 py-3 text-left">Domaine</th>
                                <th class="px-6 py-3 text-left">Thème</th>
                                <th class="px-6 py-3 text-left">Difficulté</th>
                                <th class="px-6 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="questions.data.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">Aucune question trouvée</td>
                            </tr>
                            <tr v-for="q in questions.data" :key="q.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="truncate text-gray-800 font-medium">{{ q.statement }}</p>
                                    <p v-if="q.type === 'mcq'" class="text-xs text-gray-400">{{ q.choices.length }} choix</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="typeClass[q.type]" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ typeLabel[q.type] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ q.domain?.name }}</td>
                                <td class="px-6 py-4 text-gray-600">{{ q.theme?.name }}</td>
                                <td class="px-6 py-4">
                                    <span :class="diffClass[q.difficulty]" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ difficultyLabel[q.difficulty] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <Link :href="route('admin.questions.edit', q.id)" class="text-indigo-600 hover:underline text-xs">Modifier</Link>
                                    <button @click="deleteQuestion(q.id)" class="text-red-500 hover:underline text-xs">Supprimer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="questions.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-sm text-gray-500">{{ questions.total }} résultats</p>
                        <div class="flex gap-1">
                            <Link v-for="link in questions.links" :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100', !link.url ? 'opacity-50 pointer-events-none' : '']" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <Transition name="modal">
            <div v-if="deleteTarget" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50"
                @keydown.esc="deleteTarget = null" tabindex="-1">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Supprimer la question</h3>
                            <p class="text-sm text-gray-500">Cette action est irréversible.</p>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="deleteTarget = null"
                            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Annuler
                        </button>
                        <button @click="confirmDelete" :disabled="deleting"
                            class="px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 flex items-center gap-2">
                            <svg v-if="deleting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 00-8 8h4z"/>
                            </svg>
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div, .modal-leave-to > div { transform: scale(0.95); }
</style>
