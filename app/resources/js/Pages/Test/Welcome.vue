<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    session: Object,
    candidate: Object,
});

const starting = ref(false);

function startTest() {
    starting.value = true;
    router.post(route('test.begin', props.session.token));
}
</script>

<template>
    <Head title="Bienvenue - Session de test" />

    <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 md:p-8 font-sans">
        <div class="max-w-4xl w-full">
            <div class="bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200 border border-slate-100 overflow-hidden animate-reveal">
                
                <div class="flex flex-col md:flex-row">
                    <!-- Left Side: Branding / Info -->
                    <div class="md:w-1/3 bg-slate-900 p-10 text-white flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -bottom-10 -left-10 opacity-10">
                            <svg class="size-64" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        
                        <div class="relative z-10">
                            <div class="size-16 rounded-2xl bg-white/10 backdrop-blur text-white flex items-center justify-center font-bold text-2xl mb-8 border border-white/20">
                                {{ candidate.name.charAt(0) }}
                            </div>
                            <h2 class="text-3xl font-extrabold leading-tight mb-2">{{ candidate.name }}</h2>
                            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Candidat invité</p>
                        </div>

                        <div class="relative z-10 mt-20 space-y-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Durée du test</p>
                                <p class="text-xl font-bold">{{ session.duration_minutes }} minutes</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] mb-1">Plateforme</p>
                                <p class="text-lg font-bold">ITIC Paris <span class="text-rose-500">Assessment</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Instructions -->
                    <div class="md:w-2/3 p-10 lg:p-14">
                        <div class="mb-10">
                            <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-4 py-1.5 rounded-full uppercase tracking-wider border border-indigo-100">Prêt à commencer ?</span>
                            <h1 class="text-3xl font-black text-slate-900 mt-4 leading-tight">
                                {{ session.template_name }}
                            </h1>
                            <p class="text-slate-500 font-medium mt-2">{{ session.domain_name }}</p>
                        </div>

                        <div class="space-y-8 mb-12">
                            <div class="flex gap-5">
                                <div class="size-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Le temps est compté</h4>
                                    <p class="text-slate-500 text-sm mt-1 leading-relaxed">Une fois le test lancé, le chronomètre de {{ session.duration_minutes }} minutes ne pourra plus être arrêté.</p>
                                </div>
                            </div>

                            <div class="flex gap-5">
                                <div class="size-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Environnement calme conseillé</h4>
                                    <p class="text-slate-500 text-sm mt-1 leading-relaxed">Assurez-vous d'avoir une connexion stable et de ne pas être dérangé pendant toute la durée de l'épreuve.</p>
                                </div>
                            </div>

                            <div class="flex gap-5">
                                <div class="size-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Système anti-triche</h4>
                                    <p class="text-slate-500 text-sm mt-1 leading-relaxed">Le changement d'onglet, le copier-coller et les sorties de fenêtre sont surveillés et journalisés.</p>
                                </div>
                            </div>
                        </div>

                        <button @click="startTest" :disabled="starting"
                            class="w-full bg-slate-900 text-white py-5 rounded-[1.5rem] font-black text-sm uppercase tracking-[0.2em] shadow-2xl shadow-slate-200 hover:bg-slate-800 transition-all active:scale-[0.98] disabled:opacity-50 flex items-center justify-center gap-4">
                            <span v-if="!starting">Commencer le test</span>
                            <div v-else class="size-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                            <svg v-if="!starting" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7-7 7"/></svg>
                        </button>

                        <p class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-8">En cliquant sur commencer, vous acceptez les conditions du test.</p>
                    </div>
                </div>

            </div>

            <!-- Footer Small -->
            <div class="mt-10 px-10 flex flex-col md:flex-row items-center justify-between gap-4 opacity-40">
                <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">© 2024 ITIC Paris Tech Assessment</span>
                <div class="flex items-center gap-6">
                    <span class="text-[9px] font-bold text-slate-500 uppercase tracking-widest flex items-center gap-2">
                        <div class="size-1.5 rounded-full bg-emerald-500"></div>
                        Système opérationnel
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-reveal {
    animation: reveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes reveal {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
