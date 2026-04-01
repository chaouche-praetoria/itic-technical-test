<script setup>
import { Link } from '@inertiajs/vue3';
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
                    <div class="size-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-xl shadow-indigo-200 group-hover:scale-110 transition-transform">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
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
