<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import Toast from '@/Components/Toast.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div>
    <div class="min-h-screen bg-slate-50 font-sans">
        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-50 hidden w-64 border-r border-slate-200 bg-white lg:block">
            <div class="flex h-16 items-center px-6 border-b border-slate-100">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-2 group">
                    <div class="size-8 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold bg-clip-text text-transparent bg-gradient-to-r from-slate-900 to-slate-700">TestPlatform</span>
                </Link>
            </div>
            
            <nav class="p-4 space-y-1">
                <NavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </NavLink>
                <NavLink :href="route('admin.questions.index')" :active="route().current('admin.questions.*')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Questions
                </NavLink>
                <NavLink :href="route('admin.templates.index')" :active="route().current('admin.templates.*')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Templates
                </NavLink>
                <NavLink :href="route('admin.candidates.index')" :active="route().current('admin.candidates.*')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Candidats
                </NavLink>
                <NavLink :href="route('admin.domains.index')" :active="route().current('admin.domains.*')" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Domaines
                </NavLink>
            </nav>

            <div class="absolute bottom-0 w-full p-4 border-t border-slate-100 space-y-2">
                <div class="flex items-center gap-3 px-3 py-2">
                    <div class="size-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900 truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $page.props.auth.user.email }}</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all group">
                    <svg class="size-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:pl-64">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 h-16 bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-4 sm:px-6 lg:px-8">
                <div class="flex h-full items-center justify-between">
                    <div class="flex items-center gap-4 lg:hidden">
                         <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 -ml-2 text-slate-500 rounded-lg hover:bg-slate-100">
                             <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                         </button>
                         <span class="text-lg font-bold text-indigo-600">TestPlatform</span>
                    </div>

                    <div v-if="$slots.header" class="hidden sm:block">
                        <slot name="header" />
                    </div>

                    <div class="flex items-center gap-2">
                        <button class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Responsive Navigation Drawer (Mobile) -->
            <div v-if="showingNavigationDropdown" class="lg:hidden fixed inset-0 z-[60] bg-slate-900/50 backdrop-blur-sm" @click="showingNavigationDropdown = false">
                <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-xl" @click.stop>
                    <!-- Small duplication for mobile but keeps it clean -->
                     <div class="flex h-16 items-center px-6 border-b border-slate-100">
                        <span class="text-lg font-bold text-indigo-600">TestPlatform</span>
                    </div>
                    <nav class="p-4 space-y-1">
                        <!-- Mobile links -->
                        <ResponsiveNavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">Dashboard</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.questions.index')" :active="route().current('admin.questions.*')">Questions</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.templates.index')" :active="route().current('admin.templates.*')">Templates</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.candidates.index')" :active="route().current('admin.candidates.*')">Candidats</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.domains.index')" :active="route().current('admin.domains.*')">Domaines</ResponsiveNavLink>
                    </nav>
                </div>
            </div>

            <!-- Page Title (Responsive) -->
            <div v-if="$slots.header" class="p-6 bg-white border-b border-slate-200 sm:hidden">
                <slot name="header" />
            </div>

            <!-- Main Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <Transition name="page" mode="out-in">
                    <div :key="page.url" class="animate-reveal">
                        <slot />
                    </div>
                </Transition>
            </main>
        </div>
    </div>
    </div>

    <Toast />
</template>

<style scoped>
.page-enter-active,
.page-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}
.page-enter-from,
.page-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
