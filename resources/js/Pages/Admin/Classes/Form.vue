<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({ classroom: Object, levels: Array });

const isEdit = !!props.classroom;

const form = useForm({
    name: props.classroom?.name ?? '',
    description: props.classroom?.description ?? '',
    academic_level_id: props.classroom?.academic_level_id ?? '',
    is_active: props.classroom?.is_active ?? true,
});

function submit() {
    if (isEdit) {
        form.put(route('admin.classes.update', props.classroom.id));
    } else {
        form.post(route('admin.classes.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Éditer la classe' : 'Nouvelle classe'" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ isEdit ? 'Éditer la classe' : 'Nouvelle classe' }}</h2>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="premium-card p-8 space-y-6">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Nom de la classe</label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                        <p v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Niveau</label>
                        <select v-model="form.academic_level_id" class="w-full rounded-lg border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="" disabled>Sélectionnez un niveau</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                        <p v-if="form.errors.academic_level_id" class="text-rose-500 text-xs mt-1 font-medium">{{ form.errors.academic_level_id }}</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Description</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                    </div>

                    <div v-if="isEdit" class="flex items-center gap-2">
                        <input id="active" v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="active" class="text-sm font-medium text-slate-700">Classe active</label>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing" class="bg-slate-900 text-white px-5 py-2.5 rounded-lg hover:bg-slate-800 font-bold text-xs disabled:opacity-50">
                            {{ isEdit ? 'Enregistrer' : 'Créer la classe' }}
                        </button>
                        <Link :href="route('admin.classes.index')" class="text-xs font-bold text-slate-500 hover:text-slate-800">Annuler</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
