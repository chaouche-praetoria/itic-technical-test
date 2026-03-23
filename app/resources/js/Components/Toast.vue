<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let nextId = 0;

function add(message, type) {
    const id = nextId++;
    toasts.value.push({ id, message, type });
    setTimeout(() => remove(id), 4000);
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
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    @click="remove(toast.id)"
                    :class="[
                        'pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-xl shadow-xl cursor-pointer text-sm font-medium min-w-[260px] max-w-sm',
                        toast.type === 'success'
                            ? 'bg-emerald-500 text-white'
                            : 'bg-red-500 text-white',
                    ]"
                >
                    <svg v-if="toast.type === 'success'" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ toast.message }}</span>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(110%);
}
.toast-move {
    transition: transform 0.3s ease;
}
</style>
