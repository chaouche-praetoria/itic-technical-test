<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Inscription" />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Créer un compte</h1>
            <p class="text-slate-500 font-medium">Commencez à évaluer vos talents dès aujourd'hui.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="name" value="Nom complet" class="text-slate-700 font-bold mb-2 ml-1" />
                <TextInput
                    id="name"
                    type="text"
                    class="block w-full px-4 py-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Jean Dupont"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" class="text-slate-700 font-bold mb-2 ml-1" />
                <TextInput
                    id="email"
                    type="email"
                    class="block w-full px-4 py-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="votre@email.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Mot de passe" class="text-slate-700 font-bold mb-2 ml-1" />
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full px-4 py-2 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirmation" class="text-slate-700 font-bold mb-2 ml-1" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="block w-full px-4 py-2 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                </div>
                <InputError class="col-span-full mt-1" :message="form.errors.password" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-lg shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    S'inscrire
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-8 pt-8 border-t border-slate-100 text-center">
            <p class="text-slate-500 font-medium">
                Déjà un compte ? 
                <Link :href="route('login')" class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors">
                    Se connecter
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
