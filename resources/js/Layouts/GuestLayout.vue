<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-slate-50 relative overflow-hidden px-6 selection:bg-indigo-100 selection:text-indigo-700">
        <!-- Decor -->
        <div class="absolute -top-40 -left-40 size-96 bg-indigo-100/50 rounded-full blur-3xl opacity-60"></div>
        <div class="absolute -bottom-40 -right-40 size-96 bg-purple-100/50 rounded-full blur-3xl opacity-60"></div>

        <div class="w-full sm:max-w-md relative animate-reveal">
            <!-- Brand -->
            <div class="flex flex-col items-center mb-10">
                <Link href="/" class="group flex flex-col items-center gap-4">
                    <ApplicationLogo class="h-12 w-auto fill-slate-900 group-hover:scale-105 transition-transform" />
                </Link>
            </div>

            <!-- Card -->
            <div class="premium-card p-10 overflow-hidden relative">
                <slot />
            </div>
            
            <p class="mt-8 text-center text-slate-500 text-sm font-medium">
                © 2026 TestPlatform. Tous droits réservés.
            </p>
        </div>
    </div>
</template>
