<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({ evaluation: Object, students: Array });

const form = useForm({ student_ids: [] });

const copiedId = ref(null);
const linkLoadingId = ref(null);

async function copyLink(student) {
    linkLoadingId.value = student.id;
    try {
        const res = await axios.post(route('admin.evaluations.student-link', [props.evaluation.id, student.id]));
        await navigator.clipboard.writeText(res.data.url);
        copiedId.value = student.id;
        setTimeout(() => { if (copiedId.value === student.id) copiedId.value = null; }, 1800);
    } catch (e) {
        alert('Impossible de générer le lien.');
    } finally {
        linkLoadingId.value = null;
    }
}

const allSelected = computed(() =>
    props.students.length > 0 && form.student_ids.length === props.students.length
);

function toggleAll() {
    form.student_ids = allSelected.value ? [] : props.students.map(s => s.id);
}

function selectNotYetInvited() {
    form.student_ids = props.students.filter(s => !s.invited_at).map(s => s.id);
}

function send() {
    if (form.student_ids.length === 0) return;
    form.post(route('admin.evaluations.send.store', props.evaluation.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('student_ids'),
    });
}

const statusLabel = {
    pending: 'Non commencée', in_progress: 'En cours', completed: 'Terminée',
    pending_review: 'À corriger', expired: 'Expirée',
};
</script>

<template>
    <Head :title="`Envoyer — ${evaluation.title}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Envoyer l'évaluation</h2>
                    <p class="text-xs text-slate-500 font-medium">{{ evaluation.title }} · {{ evaluation.classroom.name }} ({{ evaluation.classroom.level }})</p>
                </div>
                <Link :href="route('admin.evaluations.index')" class="text-xs font-bold text-slate-500 hover:text-slate-800">Retour</Link>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-4">

                <div v-if="!evaluation.is_published" class="premium-card p-5 border-l-4 border-amber-400 bg-amber-50/40">
                    <p class="font-bold text-amber-700 text-sm">L'évaluation est en brouillon.</p>
                    <p class="text-amber-600 text-xs mt-1">Publiez-la d'abord pour pouvoir l'envoyer aux étudiants.</p>
                    <Link :href="route('admin.evaluations.edit', evaluation.id)" class="inline-block mt-3 text-xs font-bold text-indigo-600 hover:text-indigo-800">Aller publier →</Link>
                </div>

                <div class="premium-card overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <label class="flex items-center gap-2 text-xs font-bold text-slate-600 cursor-pointer">
                            <input type="checkbox" :checked="allSelected" @change="toggleAll" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                            Tout sélectionner
                        </label>
                        <button type="button" @click="selectNotYetInvited" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">Sélectionner les non-invités</button>
                    </div>

                    <table class="w-full text-[13px]">
                        <tbody class="divide-y divide-slate-50">
                            <tr v-if="students.length === 0">
                                <td class="px-6 py-12 text-center text-slate-300 italic">Aucun étudiant dans cette classe</td>
                            </tr>
                            <tr v-for="s in students" :key="s.id" class="hover:bg-slate-50/80">
                                <td class="px-6 py-3 w-10">
                                    <input type="checkbox" :value="s.id" v-model="form.student_ids" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                </td>
                                <td class="px-2 py-3">
                                    <p class="font-bold text-slate-800">{{ s.first_name }} {{ s.last_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ s.email }}</p>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span v-if="s.attempt_status" class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ statusLabel[s.attempt_status] }}</span>
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <span v-if="s.invited_at" class="text-[11px] text-emerald-600 font-medium">Envoyé le {{ s.invited_at }}</span>
                                    <span v-else class="text-[11px] text-slate-300">Jamais envoyé</span>
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <button type="button" @click="copyLink(s)" :disabled="linkLoadingId === s.id"
                                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 disabled:opacity-50">
                                        {{ copiedId === s.id ? 'Lien copié !' : (linkLoadingId === s.id ? '…' : 'Copier le lien') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="send" :disabled="form.processing || form.student_ids.length === 0 || !evaluation.is_published"
                        class="bg-slate-900 text-white px-6 py-3 rounded-lg hover:bg-slate-800 font-bold text-xs disabled:opacity-40">
                        Envoyer à {{ form.student_ids.length }} étudiant(s)
                    </button>
                    <p class="text-[11px] text-slate-400">Un email avec le lien personnel est envoyé à chaque destinataire sélectionné.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
