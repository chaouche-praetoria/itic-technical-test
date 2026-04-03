<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ 
    administrators: Array,
    roles: Array
});

const showModal = ref(false);
const editingAdmin = ref(null);

const form = useForm({ 
    name: '', 
    email: '',
    password: '',
    roles: []
});

function openCreate() {
    form.reset();
    editingAdmin.value = null;
    showModal.value = true;
}

function openEdit(admin) {
    form.name = admin.name;
    form.email = admin.email;
    form.password = ''; // Don't pre-fill password
    form.roles = admin.roles.map(r => r.id);
    editingAdmin.value = admin;
    showModal.value = true;
}

function submit() {
    if (editingAdmin.value) {
        form.put(route('admin.administrators.update', editingAdmin.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.administrators.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function deleteAdmin(admin) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'administrateur "${admin.name}" ?`)) {
        router.delete(route('admin.administrators.destroy', admin.id));
    }
}

function toggleRole(roleId) {
    const index = form.roles.indexOf(roleId);
    if (index > -1) {
        form.roles.splice(index, 1);
    } else {
        form.roles.push(roleId);
    }
}
</script>

<template>
    <Head title="Gestion des Administrateurs" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Administrateurs</h2>
                    <p class="text-sm text-slate-500 font-medium">Gérez les accès à la plateforme (RBAC)</p>
                </div>
                <button @click="openCreate" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouvel administrateur
                </button>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Nom</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Email</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Rôles</th>
                                <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="admin in administrators" :key="admin.id" class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-black text-xs">
                                            {{ admin.name.charAt(0) }}
                                        </div>
                                        <span class="font-bold text-slate-700">{{ admin.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 font-medium">{{ admin.email }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="role in admin.roles" :key="role.id" 
                                            class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider"
                                            :class="role.name === 'super-admin' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-indigo-50 text-indigo-600 border border-indigo-100'">
                                            {{ role.label || role.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button @click="openEdit(admin)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button @click="deleteAdmin(admin)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" v-if="$page.props.auth.user.id !== admin.id">
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

    </AuthenticatedLayout>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-lg shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-6 text-center">
                    <h3 class="text-2xl font-bold text-slate-900">{{ editingAdmin ? 'Modifier' : 'Nouvel' }} Administrateur</h3>
                    <p class="text-sm text-slate-500 font-medium">Définissez les accès de l'utilisateur.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Nom complet</label>
                            <input v-model="form.name" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="John Doe" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Email</label>
                            <input v-model="form.email" type="email" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="john@example.com" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Mot de passe</label>
                        <input v-model="form.password" type="password" :required="!editingAdmin"
                            class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                            :placeholder="editingAdmin ? '(Laisser vide pour ne pas changer)' : '********'" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 ml-1">Rôles de l'utilisateur</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="role in roles" :key="role.id" type="button"
                                @click="toggleRole(role.id)"
                                class="flex items-center gap-3 p-4 rounded-2xl border-2 transition-all text-left"
                                :class="form.roles.includes(role.id) ? 'border-indigo-600 bg-indigo-50 text-indigo-900 ring-4 ring-indigo-500/5' : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-200'">
                                <div class="size-5 rounded-lg border-2 flex items-center justify-center flex-shrink-0"
                                    :class="form.roles.includes(role.id) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-300'">
                                    <svg v-if="form.roles.includes(role.id)" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-black uppercase tracking-tight">{{ role.label || role.name }}</span>
                                    <span class="text-[9px] font-medium opacity-60">{{ role.name === 'super-admin' ? 'Accès total' : 'Accès standard' }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ editingAdmin ? 'Mettre à jour' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
