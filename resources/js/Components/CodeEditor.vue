<script setup>
import { Codemirror } from 'vue-codemirror';
import { javascript } from '@codemirror/lang-javascript';
import { python } from '@codemirror/lang-python';
import { php } from '@codemirror/lang-php';
import { java } from '@codemirror/lang-java';
import { cpp } from '@codemirror/lang-cpp';
import { oneDark } from '@codemirror/theme-one-dark';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: String,
    language: {
        type: String,
        default: 'javascript'
    },
    dark: {
        type: Boolean,
        default: false
    },
    placeholder: {
        type: String,
        default: 'Écrivez votre code ici...'
    },
    readonly: {
        type: Boolean,
        default: false
    },
    height: {
        type: String,
        default: '300px'
    }
});

const emit = defineEmits(['update:modelValue']);

const isFullscreen = ref(false);

const extensions = computed(() => {
    const exts = [];
    
    if (props.dark) {
        exts.push(oneDark);
    }

    switch (props.language) {
        case 'javascript':
        case 'typescript':
            exts.push(javascript());
            break;
        case 'python':
            exts.push(python());
            break;
        case 'php':
            exts.push(php());
            break;
        case 'java':
            exts.push(java());
            break;
        case 'cpp':
        case 'c':
            exts.push(cpp());
            break;
    }

    return exts;
});

function handleChange(value) {
    emit('update:modelValue', value);
}

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    if (isFullscreen.value) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}
</script>

<template>
    <div class="code-editor-wrapper" :class="{ 'is-fullscreen': isFullscreen }">
        <div class="code-editor-header flex justify-between items-center px-4 py-2 bg-slate-100 border-b border-slate-200">
            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ language }}</span>
            <button type="button" @click="toggleFullscreen" class="p-1 hover:bg-slate-200 rounded transition-colors text-slate-600" :title="isFullscreen ? 'Quitter le plein écran' : 'Passer en plein écran'">
                <svg v-if="!isFullscreen" xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M21 8V5a2 2 0 0 0-2-2h-3"/><path d="M3 16v3a2 2 0 0 0 2 2h3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 3 0 6 6 0"/><path d="m9 21 0-6-6 0"/><path d="m21 9-6-6"/><path d="m3 15 6 6"/></svg>
            </button>
        </div>
        <div class="code-editor-container" :style="{ height: isFullscreen ? 'calc(100vh - 40px)' : height }">
            <codemirror
                :model-value="modelValue"
                :placeholder="placeholder"
                :style="{ height: '100%' }"
                :autofocus="true"
                :indent-with-tab="true"
                :tab-size="4"
                :extensions="extensions"
                :disabled="readonly"
                @change="handleChange"
            />
        </div>
    </div>
</template>

<style>
.code-editor-wrapper {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: white;
}
.code-editor-wrapper.is-fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    border-radius: 0;
}
.cm-editor {
    height: 100% !important;
}
.cm-scroller {
    font-family: 'Fira Code', 'Fira Mono', monospace !important;
}
/* Assure que les lignes sont visibles même si le code est court */
.cm-content {
    min-height: 100px;
}
</style>
