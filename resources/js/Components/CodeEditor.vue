<script setup>
import { Codemirror } from 'vue-codemirror';
import { javascript } from '@codemirror/lang-javascript';
import { python } from '@codemirror/lang-python';
import { php } from '@codemirror/lang-php';
import { java } from '@codemirror/lang-java';
import { cpp } from '@codemirror/lang-cpp';
import { oneDark } from '@codemirror/theme-one-dark';
import { computed } from 'vue';

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
</script>

<template>
    <div class="code-editor-container" :style="{ height: height }">
        <codemirror
            :model-value="modelValue"
            :placeholder="placeholder"
            :style="{ height: height }"
            :autofocus="true"
            :indent-with-tab="true"
            :tab-size="4"
            :extensions="extensions"
            :disabled="readonly"
            @change="handleChange"
        />
    </div>
</template>

<style>
.code-editor-container {
    border-radius: 0.5rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.cm-editor {
    height: 100% !important;
}
.cm-scroller {
    font-family: 'Fira Code', 'Fira Mono', monospace !important;
}
</style>
