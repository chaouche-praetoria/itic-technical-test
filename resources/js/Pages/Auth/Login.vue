<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Connexion" />

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900 mb-2">Bon retour</h1>
            <p class="text-slate-500 font-medium">Connectez-vous pour gérer vos tests.</p>
        </div>

        <div v-if="status" class="mb-6 p-4 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-bold border border-emerald-100">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Email" class="text-slate-700 font-bold mb-2 ml-1" />
                <TextInput
                    id="email"
                    type="email"
                    class="block w-full px-4 py-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="votre@email.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between mb-2 px-1">
                    <InputLabel for="password" value="Mot de passe" class="text-slate-700 font-bold" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-bold text-indigo-600 hover:text-indigo-700 transition-colors"
                    >
                        Oublié ?
                    </Link>
                </div>

                <TextInput
                    id="password"
                    type="password"
                    class="block w-full px-4 py-3 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox name="remember" v-model:checked="form.remember" class="size-5 rounded-lg border-slate-300 text-indigo-600 focus:ring-indigo-500/20" />
                    <span class="ms-3 text-sm text-slate-600 font-bold group-hover:text-slate-900 transition-colors">Se souvenir de moi</span>
                </label>
            </div>

            <div>
                <PrimaryButton
                    class="w-full justify-center py-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-lg shadow-xl shadow-slate-200 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    Se connecter
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-8 pt-8 border-t border-slate-100 text-center">
            <p class="text-slate-500 font-medium">
                Pas encore de compte ? 
                <Link :href="route('register')" class="text-indigo-600 font-bold hover:text-indigo-700 transition-colors">
                    S'inscrire
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>
