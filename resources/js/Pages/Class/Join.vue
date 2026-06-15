<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    classroom: Object,
    prefillEmail: String,
});

const form = useForm({
    first_name: '',
    last_name: '',
    email: props.prefillEmail ?? '',
    phone: '',
});

function submit() {
    form.post(route('class.store', props.token));
}
</script>

<template>
    <Head title="Rejoindre une classe" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 md:p-8 font-sans">
        <div class="max-w-3xl w-full bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden animate-reveal">
            <div class="flex flex-col md:flex-row">
                <div class="md:w-2/5 bg-slate-900 p-10 text-white flex flex-col justify-between">
                    <div>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-2">Invitation</p>
                        <h1 class="text-2xl font-extrabold leading-tight">{{ classroom.name }}</h1>
                        <p v-if="classroom.level" class="text-slate-400 font-bold text-xs uppercase tracking-widest mt-2">Niveau {{ classroom.level }}</p>
                        <p v-if="classroom.description" class="text-slate-300 text-sm mt-4 leading-relaxed">{{ classroom.description }}</p>
                    </div>
                    <p v-if="classroom.teacher" class="text-slate-400 text-xs mt-10">Enseignant : <span class="text-white font-bold">{{ classroom.teacher }}</span></p>
                </div>

                <div class="md:w-3/5 p-10">
                    <h2 class="text-xl font-black text-slate-900 mb-1">Rejoindre la classe</h2>
                    <p class="text-slate-500 text-sm mb-8">Renseignez vos informations pour intégrer la classe.</p>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Prénom</label>
                                <input v-model="form.first_name" type="text" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                <p v-if="form.errors.first_name" class="text-rose-500 text-xs mt-1">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Nom</label>
                                <input v-model="form.last_name" type="text" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                <p v-if="form.errors.last_name" class="text-rose-500 text-xs mt-1">{{ form.errors.last_name }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Email</label>
                            <input v-model="form.email" type="email" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                            <p v-if="form.errors.email" class="text-rose-500 text-xs mt-1">{{ form.errors.email }}</p>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-2">Téléphone (optionnel)</label>
                            <input v-model="form.phone" type="tel" class="w-full rounded-lg border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>

                        <button type="submit" :disabled="form.processing"
                            class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold text-sm uppercase tracking-widest hover:bg-indigo-500 transition-all shadow-xl shadow-indigo-200 disabled:opacity-50">
                            Intégrer la classe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
