<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ 
    roles: Array,
    permissions: Array
});

const showModal = ref(false);
const editingRole = ref(null);

const form = useForm({ 
    name: '', 
    label: '',
    permissions: []
});

function openCreate() {
    form.reset();
    editingRole.value = null;
    showModal.value = true;
}

function openEdit(role) {
    if (role.name === 'super-admin') return alert('Le rôle Super Admin ne peut pas être modifié.');
    
    form.name = role.name;
    form.label = role.label;
    form.permissions = role.permissions.map(p => p.id);
    editingRole.value = role;
    showModal.value = true;
}

function submit() {
    if (editingRole.value) {
        form.put(route('admin.roles.update', editingRole.value.id), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    } else {
        form.post(route('admin.roles.store'), {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function deleteRole(role) {
    if (role.name === 'super-admin') return;
    if (confirm(`Êtes-vous sûr de vouloir supprimer le rôle "${role.label || role.name}" ?`)) {
        router.delete(route('admin.roles.destroy', role.id));
    }
}

function togglePermission(permissionId) {
    const index = form.permissions.indexOf(permissionId);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permissionId);
    }
}
</script>

<template>
    <Head title="Gestion des Rôles & Permissions" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Rôles & Permissions</h2>
                    <p class="text-sm text-slate-500 font-medium">Définissez les niveaux d'accès de la plateforme</p>
                </div>
                <button @click="openCreate" 
                    class="bg-slate-900 text-white px-5 py-2.5 rounded-xl hover:bg-slate-800 font-bold text-sm shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-3 w-fit">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nouveau rôle
                </button>
            </div>
        </template>

        <div class="py-10 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="role in roles" :key="role.id" class="premium-card p-6 flex flex-col group hover:shadow-2xl transition-all duration-300 relative overflow-hidden" 
                        :class="role.name === 'super-admin' ? 'border-indigo-500/20 bg-indigo-50/10' : ''">
                        
                        <div v-if="role.name === 'super-admin'" class="absolute -right-8 -top-8 size-24 bg-indigo-500/5 rounded-full flex items-center justify-center rotate-12">
                             <svg class="size-12 text-indigo-500/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.333 9-6.03 9-11.623 0-1.312-.251-2.566-.707-3.719A11.946 11.946 0 0112 2.714z" /></svg>
                        </div>

                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="size-10 rounded-xl flex items-center justify-center font-black text-xs shadow-sm border"
                                    :class="role.name === 'super-admin' ? 'bg-indigo-600 text-white border-indigo-500' : 'bg-white text-slate-600 border-slate-100'">
                                    {{ role.name.charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 leading-tight">{{ role.label || role.name }}</h3>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ role.name }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1" v-if="role.name !== 'super-admin'">
                                <button @click="openEdit(role)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                                <button @click="deleteRole(role)" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" v-if="role.users_count === 0 || !role.users_count">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2">
                             <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-2">Permissions attribuées</span>
                             <div v-if="role.name === 'super-admin'" class="py-2 px-3 bg-indigo-50 text-indigo-600 rounded-xl text-xs font-bold border border-indigo-100 flex items-center gap-2">
                                 <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                 Contrôle total sur toutes les fonctionnalités
                             </div>
                             <div v-else class="flex flex-wrap gap-1.5">
                                 <span v-for="permission in role.permissions" :key="permission.id" 
                                    class="px-2 py-0.5 bg-slate-50 text-slate-600 rounded-lg text-[9px] font-bold border border-slate-100">
                                    {{ permission.label || permission.name }}
                                 </span>
                                 <span v-if="role.permissions.length === 0" class="text-xs text-slate-400 italic font-medium">Aucune permission</span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>

    <!-- Modal -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl p-8 w-full max-w-2xl shadow-2xl animate-reveal border border-slate-100">
                <div class="mb-8 text-center">
                    <h3 class="text-2xl font-bold text-slate-900">{{ editingRole ? 'Modifier' : 'Nouveau' }} Rôle</h3>
                    <p class="text-sm text-slate-500 font-medium">Définissez le nom et les permissions associées.</p>
                </div>
                <form @submit.prevent="submit" class="space-y-8">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Label public</label>
                            <input v-model="form.label" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="ex: Gestionnaire des Tests" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Code Système</label>
                            <input v-model="form.name" type="text" required
                                class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium"
                                placeholder="ex: gestionnaire-tests" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-4 ml-1">Permissions du rôle</label>
                        <div class="grid grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                            <button v-for="perm in permissions" :key="perm.id" type="button"
                                @click="togglePermission(perm.id)"
                                class="flex items-center gap-3 p-4 rounded-2xl border-2 transition-all text-left"
                                :class="form.permissions.includes(perm.id) ? 'border-indigo-600 bg-indigo-50 text-indigo-900 shadow-sm' : 'border-slate-100 bg-slate-50 text-slate-600 hover:border-slate-200'">
                                <div class="size-5 rounded-lg border-2 flex items-center justify-center flex-shrink-0"
                                    :class="form.permissions.includes(perm.id) ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-slate-300'">
                                    <svg v-if="form.permissions.includes(perm.id)" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-[11px] font-black uppercase tracking-tight">{{ perm.label || perm.name }}</span>
                                    <span class="text-[9px] font-medium opacity-60">{{ perm.name }}</span>
                                </div>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 pt-4 border-t border-slate-50">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-6 py-4 text-sm font-bold text-slate-600 bg-slate-100 rounded-2xl hover:bg-slate-200 transition-all active:scale-[0.98]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-6 py-4 text-sm font-bold text-white bg-slate-900 rounded-2xl hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 disabled:opacity-50 active:scale-[0.98]">
                            {{ editingRole ? 'Mettre à jour le rôle' : 'Créer le rôle' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
