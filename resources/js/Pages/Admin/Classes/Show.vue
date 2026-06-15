<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import QRCode from 'qrcode';

const props = defineProps({
    classroom: Object,
    joinUrl: String,
    evaluations: Array,
});

const qrDataUrl = ref('');
const copied = ref(false);

onMounted(async () => {
    qrDataUrl.value = await QRCode.toDataURL(props.joinUrl, { width: 240, margin: 1 });
});

async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch (e) { /* fall through to legacy */ }
    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
    } catch (e) {
        return false;
    }
}

async function copyLink() {
    const ok = await copyToClipboard(props.joinUrl);
    if (ok) {
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } else {
        window.prompt('Copiez le lien d\'invitation :', props.joinUrl);
    }
}

const inviteForm = useForm({ emails: [] });
const emailInput = ref('');

function addEmail() {
    const value = emailInput.value.trim();
    if (value && !inviteForm.emails.includes(value)) {
        inviteForm.emails.push(value);
    }
    emailInput.value = '';
}

function removeEmail(i) {
    inviteForm.emails.splice(i, 1);
}

function sendInvites() {
    if (inviteForm.emails.length === 0) return;
    inviteForm.post(route('admin.classes.invite', props.classroom.id), {
        preserveScroll: true,
        onSuccess: () => { inviteForm.reset('emails'); },
    });
}

function removeStudent(studentId) {
    if (confirm('Retirer cet étudiant de la classe ?')) {
        router.delete(route('admin.classes.students.destroy', [props.classroom.id, studentId]), { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Classe — ${classroom.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ classroom.name }}</h2>
                    <p class="text-xs text-slate-500 font-medium">
                        Niveau {{ classroom.academic_level?.name }} · {{ classroom.students.length }} étudiant(s)
                    </p>
                </div>
                <Link :href="route('admin.classes.edit', classroom.id)" class="text-xs font-bold text-slate-500 hover:text-slate-800">Éditer</Link>
            </div>
        </template>

        <div class="py-6 animate-reveal">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- QR + join link -->
                <div class="premium-card p-6 flex flex-col items-center text-center">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-4">Rejoindre via QR code</p>
                    <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR code" class="rounded-xl border border-slate-100" />
                    <button @click="copyLink" class="mt-4 text-xs font-bold text-indigo-600 hover:text-indigo-800">
                        {{ copied ? 'Lien copié !' : 'Copier le lien d\'invitation' }}
                    </button>
                    <p class="mt-2 text-[10px] text-slate-400 break-all">{{ joinUrl }}</p>
                </div>

                <!-- Email invitations -->
                <div class="premium-card p-6 lg:col-span-2">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-4">Inviter par email</p>
                    <div class="flex gap-2">
                        <input v-model="emailInput" @keydown.enter.prevent="addEmail" type="email" placeholder="email@etudiant.fr"
                            class="flex-1 rounded-lg border-slate-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                        <button @click="addEmail" type="button" class="bg-slate-100 text-slate-700 px-4 rounded-lg font-bold text-xs hover:bg-slate-200">Ajouter</button>
                    </div>
                    <div v-if="inviteForm.emails.length" class="flex flex-wrap gap-2 mt-3">
                        <span v-for="(e, i) in inviteForm.emails" :key="i" class="inline-flex items-center gap-1.5 bg-indigo-50 text-indigo-700 text-xs font-medium px-3 py-1.5 rounded-full">
                            {{ e }}
                            <button @click="removeEmail(i)" class="text-indigo-400 hover:text-indigo-700">&times;</button>
                        </span>
                    </div>
                    <button @click="sendInvites" :disabled="inviteForm.processing || !inviteForm.emails.length"
                        class="mt-4 bg-slate-900 text-white px-5 py-2.5 rounded-lg hover:bg-slate-800 font-bold text-xs disabled:opacity-40">
                        Envoyer les invitations
                    </button>

                    <div v-if="classroom.invitations.length" class="mt-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Invitations envoyées</p>
                        <ul class="space-y-1">
                            <li v-for="inv in classroom.invitations" :key="inv.id" class="flex items-center justify-between text-xs text-slate-600">
                                <span>{{ inv.email }}</span>
                                <span :class="inv.status === 'accepted' ? 'text-emerald-600' : 'text-amber-500'" class="font-bold uppercase tracking-wider text-[10px]">{{ inv.status === 'accepted' ? 'Acceptée' : 'En attente' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Students -->
                <div class="premium-card p-6 lg:col-span-2">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-4">Étudiants</p>
                    <table class="w-full text-[13px]">
                        <tbody class="divide-y divide-slate-50">
                            <tr v-if="classroom.students.length === 0">
                                <td class="py-8 text-center text-slate-300 italic">Aucun étudiant inscrit</td>
                            </tr>
                            <tr v-for="s in classroom.students" :key="s.id" class="hover:bg-slate-50/80">
                                <td class="py-3 font-bold text-slate-800">{{ s.first_name }} {{ s.last_name }}</td>
                                <td class="py-3 text-slate-500">{{ s.email }}</td>
                                <td class="py-3 text-slate-400 text-xs">{{ s.phone }}</td>
                                <td class="py-3 text-right">
                                    <button @click="removeStudent(s.id)" class="text-xs font-bold text-rose-500 hover:text-rose-700">Retirer</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Evaluations -->
                <div class="premium-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-400">Évaluations</p>
                        <Link :href="route('admin.evaluations.create')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">+ Nouvelle</Link>
                    </div>
                    <ul class="space-y-2">
                        <li v-if="evaluations.length === 0" class="text-slate-300 italic text-sm">Aucune évaluation</li>
                        <li v-for="ev in evaluations" :key="ev.id" class="flex items-center justify-between">
                            <Link :href="route('admin.evaluations.edit', ev.id)" class="text-sm font-bold text-slate-700 hover:text-indigo-600">{{ ev.title }}</Link>
                            <span :class="ev.is_published ? 'text-emerald-600' : 'text-slate-400'" class="text-[10px] font-bold uppercase tracking-wider">{{ ev.is_published ? 'Publiée' : 'Brouillon' }}</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
