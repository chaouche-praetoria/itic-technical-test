<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import SidebarLink from '@/Components/SidebarLink.vue';
import Toast from '@/Components/Toast.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const page = usePage();
</script>

<template>
    <div>
    <div class="min-h-screen bg-slate-50 font-sans">
        <!-- Sidebar Navigation -->
        <aside class="fixed inset-y-0 left-0 z-50 hidden w-72 bg-slate-900 shadow-2xl lg:block border-r border-white/5">
            <div class="flex h-20 items-center px-8 mb-4">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-3 group">
                    <div class="size-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 group-hover:scale-110 transition-all duration-300">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-black text-white leading-none tracking-tight">TestPlatform</span>
                        <span class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.3em] mt-1">Admin Panel</span>
                    </div>
                </Link>
            </div>
            
            <div class="px-4 py-2">
                <div class="px-4 mb-4">
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Menu Principal</span>
                </div>
                <nav class="space-y-1.5">
                    <SidebarLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        </template>
                        Dashboard
                    </SidebarLink>

                    <SidebarLink :href="route('admin.questions.index')" :active="route().current('admin.questions.*')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        Banque de questions
                    </SidebarLink>

                    <SidebarLink :href="route('admin.templates.index')" :active="route().current('admin.templates.*')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </template>
                        Templates de test
                    </SidebarLink>

                    <SidebarLink :href="route('admin.candidates.index')" :active="route().current('admin.candidates.*')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </template>
                        Candidats
                    </SidebarLink>

                    <SidebarLink :href="route('admin.domains.index')" :active="route().current('admin.domains.*')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </template>
                        Domaines
                    </SidebarLink>

                    <SidebarLink :href="route('admin.levels.index')" :active="route().current('admin.levels.*')">
                        <template #icon>
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 012-2H7a2 2 0 01-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m-1 4h1m-5 10l-2-3m2 3l2-3" /></svg>
                        </template>
                        Niveaux
                    </SidebarLink>
                </nav>
            </div>

            <div class="absolute bottom-0 w-full p-4 space-y-3">
                <div class="bg-indigo-600/10 rounded-3xl p-6 mb-4 border border-indigo-500/10">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2">Besoin d'aide ?</p>
                    <p class="text-[11px] text-slate-400 leading-relaxed mb-4">Consultez la documentation ou contactez le support ITIC.</p>
                    <button class="w-full py-2.5 bg-indigo-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-900/20">Support</button>
                </div>

                <div class="flex items-center gap-3 px-4 py-3 bg-slate-800/30 rounded-2xl border border-white/5">
                    <div class="size-10 rounded-full bg-gradient-to-tr from-slate-700 to-slate-600 flex items-center justify-center text-white font-black text-sm border border-white/10 shadow-inner">
                        {{ $page.props.auth.user.name.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-white truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] font-medium text-slate-500 truncate">{{ $page.props.auth.user.email }}</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center gap-3 px-4 py-3 text-xs font-bold text-slate-400 hover:text-rose-400 hover:bg-rose-400/5 rounded-2xl transition-all group border border-transparent hover:border-rose-400/10">
                    <svg class="size-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Déconnexion
                </Link>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="lg:pl-72">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 h-20 bg-white/70 backdrop-blur-xl border-b border-slate-200/50 px-6 sm:px-8 lg:px-10">
                <div class="flex h-full items-center justify-between">
                    <div class="flex items-center gap-6 lg:hidden">
                         <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2.5 -ml-2 text-slate-500 rounded-xl hover:bg-slate-100 transition-colors">
                             <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                         </button>
                         <span class="text-xl font-black text-indigo-600 tracking-tight">TestPlatform</span>
                    </div>

                    <div v-if="$slots.header" class="hidden lg:block flex-1">
                        <slot name="header" />
                    </div>

                    <div class="flex items-center gap-4">
                        <button class="p-2.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all duration-300">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Responsive Navigation Drawer (Mobile) -->
            <Transition name="slide">
                <div v-if="showingNavigationDropdown" class="lg:hidden fixed inset-0 z-[60] bg-slate-900/60 backdrop-blur-md" @click="showingNavigationDropdown = false">
                    <div class="fixed inset-y-0 left-0 w-80 bg-slate-900 shadow-2xl border-r border-white/5" @click.stop>
                        <div class="flex h-20 items-center px-8 justify-between border-b border-white/5">
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black">TP</div>
                                <span class="text-white font-black tracking-tight">Admin Menu</span>
                            </div>
                            <button @click="showingNavigationDropdown = false" class="p-2 text-slate-400 hover:text-white transition-colors">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <nav class="p-6 space-y-2">
                            <SidebarLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                                <template #icon>
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                </template>
                                Dashboard
                            </SidebarLink>
                            <SidebarLink :href="route('admin.questions.index')" :active="route().current('admin.questions.*')">
                                <template #icon>
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </template>
                                Questions
                            </SidebarLink>
                            <SidebarLink :href="route('admin.templates.index')" :active="route().current('admin.templates.*')">
                                <template #icon>
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </template>
                                Templates
                            </SidebarLink>
                            <SidebarLink :href="route('admin.candidates.index')" :active="route().current('admin.candidates.*')">
                                <template #icon>
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                </template>
                                Candidats
                            </SidebarLink>
                        </nav>
                        
                        <div class="absolute bottom-0 w-full p-6 border-t border-white/5 bg-slate-900/80 backdrop-blur-xl">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="size-12 rounded-full bg-indigo-600 flex items-center justify-center text-white font-black text-lg">
                                    {{ $page.props.auth.user.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-white font-bold leading-none">{{ $page.props.auth.user.name }}</p>
                                    <p class="text-slate-500 text-xs mt-1">{{ $page.props.auth.user.email }}</p>
                                </div>
                            </div>
                            <Link :href="route('logout')" method="post" as="button" class="w-full flex items-center justify-center gap-3 px-4 py-4 bg-rose-500/10 text-rose-500 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-rose-500 hover:text-white transition-all">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>

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

.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
}

.slide-enter-from > div,
.slide-leave-to > div {
    transform: translateX(-100%);
}
</style>
