<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <Head title="Compte en attente" />
    <div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl shadow-slate-200 border border-slate-100 p-10 text-center animate-reveal">
            <div class="mb-8 flex justify-center">
                <div class="size-20 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 ring-4 ring-indigo-500/5">
                    <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            
            <h1 class="text-3xl font-black text-slate-900 mb-4 tracking-tight">Accès restreint</h1>
            <p class="text-slate-500 font-medium leading-relaxed mb-8">
                Votre inscription a bien été enregistrée. Cependant, un administrateur doit valider votre compte avant que vous ne puissiez accéder au tableau de bord.
            </p>
            
            <div class="space-y-4">
                <Link :href="route('logout')" method="post" as="button"
                    class="w-full py-4 bg-slate-900 text-white rounded-2xl font-bold hover:bg-slate-800 transition-all active:scale-[0.98] shadow-xl shadow-slate-200">
                    Se déconnecter
                </Link>
                <p class="text-xs text-slate-400 font-medium">
                    Une question ? Contactez le support ITIC Paris.
                </p>
            </div>
        </div>
    </div>
</template>
