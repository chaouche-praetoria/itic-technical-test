<script setup>
import { ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let nextId = 0;

function add(message, type) {
    const id = nextId++;
    // Clean up message if it's too long or if it contains the link prefix we might want to hide
    const displayMessage = message.length > 200 ? message.substring(0, 197) + '...' : message;
    
    toasts.value.push({ id, message: displayMessage, type });
    setTimeout(() => remove(id), 5000);
}

function remove(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) add(flash.success, 'success');
        if (flash?.error)   add(flash.error,   'error');
    },
    { deep: true }
);

// Check for initial flash messages on mount
onMounted(() => {
    if (page.props.flash?.success) add(page.props.flash.success, 'success');
    if (page.props.flash?.error) add(page.props.flash.error, 'error');
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-8 right-8 z-[9999] flex flex-col gap-3 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    @click="remove(toast.id)"
                    :class="[
                        'pointer-events-auto flex items-start gap-4 p-5 rounded-2xl shadow-2xl cursor-pointer text-sm font-bold min-w-[320px] max-w-md border backdrop-blur-xl transition-all hover:scale-[1.02] active:scale-[0.98]',
                        toast.type === 'success'
                            ? 'bg-emerald-500/95 text-white border-emerald-400/20 shadow-emerald-500/20'
                            : 'bg-rose-600/95 text-white border-rose-400/20 shadow-rose-500/20',
                    ]"
                >
                    <div class="shrink-0 size-6 rounded-lg bg-white/20 flex items-center justify-center border border-white/20">
                        <svg v-if="toast.type === 'success'" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="flex-1 pt-0.5">
                        <p class="leading-relaxed drop-shadow-sm">{{ toast.message }}</p>
                    </div>
                    <button @click.stop="remove(toast.id)" class="shrink-0 text-white/50 hover:text-white transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(40px) scale(0.9);
}
.toast-move {
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
