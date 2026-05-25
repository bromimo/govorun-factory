<script setup>
import { ref } from 'vue';

const props = defineProps({
    editor: { type: Object, required: true },
});

const linkOpen = ref(false);
const linkUrl = ref('');

function openLink() {
    linkUrl.value = props.editor.getAttributes('link').href ?? '';
    linkOpen.value = true;
}

function applyLink() {
    const url = linkUrl.value.trim();
    if (url) {
        props.editor.chain().focus().setLink({ href: url }).run();
    } else {
        props.editor.chain().focus().unsetLink().run();
    }
    linkUrl.value = '';
    linkOpen.value = false;
}

function cancelLink() {
    linkUrl.value = '';
    linkOpen.value = false;
}

const btn = 'inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded px-1 text-xs text-gray-600 hover:bg-gray-100';
const active = 'bg-gray-200 text-gray-900';
</script>

<template>
    <div class="inline-flex flex-wrap items-center gap-0.5">
        <button type="button" :class="[btn, editor.isActive('bold') ? active : '']"
            @click="editor.chain().focus().toggleBold().run()" title="Жирный (Ctrl+B)">
            <strong>B</strong>
        </button>
        <button type="button" :class="[btn, editor.isActive('italic') ? active : '']"
            @click="editor.chain().focus().toggleItalic().run()" title="Курсив (Ctrl+I)">
            <em>I</em>
        </button>
        <button type="button" :class="[btn, editor.isActive('underline') ? active : '']"
            @click="editor.chain().focus().toggleUnderline().run()" title="Подчёркивание (Ctrl+U)">
            <span class="underline">U</span>
        </button>
        <button type="button" :class="[btn, editor.isActive('strike') ? active : '']"
            @click="editor.chain().focus().toggleStrike().run()" title="Зачёркивание">
            <span class="line-through">S</span>
        </button>
        <button type="button" :class="[btn, editor.isActive('code') ? active : '']"
            @click="editor.chain().focus().toggleCode().run()" title="Моноширинный">
            <code>&lt;&gt;</code>
        </button>
        <button type="button" :class="[btn, editor.isActive('spoiler') ? active : '']"
            @click="editor.chain().focus().toggleSpoiler().run()" title="Спойлер">
            🙈
        </button>

        <!-- Link -->
        <div class="relative">
            <button type="button" :class="[btn, editor.isActive('link') ? active : '']"
                @click="openLink" title="Ссылка">
                🔗
            </button>
            <div v-if="linkOpen"
                class="absolute left-0 top-7 z-20 flex items-center gap-1 rounded border border-gray-200 bg-white p-2 shadow-md">
                <input v-model="linkUrl" type="text" placeholder="https://..."
                    class="w-44 rounded border-gray-300 text-xs"
                    @keydown.enter.prevent="applyLink"
                    @keydown.escape.prevent="cancelLink" />
                <button type="button" @click="applyLink"
                    class="text-xs text-indigo-600 hover:text-indigo-800">OK</button>
                <button type="button" @click="cancelLink"
                    class="text-xs text-gray-400 hover:text-gray-600">✕</button>
            </div>
        </div>

        <!-- Blockquote -->
        <button type="button" :class="[btn, editor.isActive('blockquote') ? active : '']"
            @click="editor.chain().focus().toggleBlockquote().run()" title="Цитата">
            ❝
        </button>

        <!-- Expandable toggle (only when inside blockquote) -->
        <label v-if="editor.isActive('blockquote')"
            class="flex cursor-pointer items-center gap-1 rounded px-1 text-xs text-gray-500 hover:bg-gray-100">
            <input type="checkbox" class="h-3 w-3 rounded"
                :checked="editor.getAttributes('blockquote').expandable"
                @change="editor.chain().focus().toggleExpandable().run()" />
            exp.
        </label>
    </div>
</template>