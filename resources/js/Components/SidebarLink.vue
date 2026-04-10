<script setup>
import { computed, useAttrs } from 'vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ inheritAttrs: false });
const attrs = useAttrs();

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'group flex items-center gap-3 px-4 py-3 rounded-2xl bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-900/40 transition-all duration-300 scale-[1.02]'
        : 'group flex items-center gap-3 px-4 py-3 rounded-2xl text-slate-400 hover:text-slate-100 hover:bg-white/5 border border-transparent transition-all duration-300'
);

// Determine if we should use a standard <a> tag instead of Inertia <Link>
const isExternal = computed(() => attrs.target === '_blank' || props.href.startsWith('http'));
</script>

<template>
    <component 
        :is="isExternal ? 'a' : Link" 
        :href="href" 
        :class="classes"
        v-bind="attrs"
    >
        <div :class="[
            'shrink-0 transition-all duration-300',
            active ? 'text-white scale-110' : 'text-slate-500 group-hover:text-indigo-400 group-hover:scale-110'
        ]">
            <slot name="icon" />
        </div>
        <span class="text-sm tracking-wide">
            <slot />
        </span>
        <div v-if="active" class="ml-auto w-1.5 h-1.5 rounded-full bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)]"></div>
    </component>
</template>
