<script setup>
import { watch, onBeforeUnmount } from 'vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import FormattingToolbar from './FormattingToolbar.vue';
import { Spoiler } from './tiptap/Spoiler.js';
import { PlaceholderToken } from './tiptap/PlaceholderToken.js';
import { ExpandableBlockquote } from './tiptap/ExpandableBlockquote.js';
import { parseTelegramHtml, serializeTelegramHtml } from '@/utils/telegramHtml.js';
import InsertToolbar from './InsertToolbar.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    declaredKeys: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: parseTelegramHtml(props.modelValue),
    extensions: [
        StarterKit.configure({
            blockquote: false,
            bulletList: false,
            orderedList: false,
            listItem: false,
            horizontalRule: false,
            heading: false,
        }),
        Underline,
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Spoiler,
        PlaceholderToken,
        ExpandableBlockquote,
    ],
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', serializeTelegramHtml(ed.getHTML()));
    },
});

watch(() => props.modelValue, (newVal) => {
    if (!editor.value) return;
    const current = serializeTelegramHtml(editor.value.getHTML());
    if (newVal !== current) {
        editor.value.commands.setContent(parseTelegramHtml(newVal), false);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

defineExpose({ editor });
</script>

<template>
    <div class="rounded border border-gray-300 focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400">
        <div v-if="editor" class="flex flex-wrap items-center gap-1 border-b border-gray-200 px-2 py-1">
            <FormattingToolbar :editor="editor" />
            <span class="mx-0.5 h-4 border-l border-gray-300" />
            <InsertToolbar :editor="editor" :declared-keys="declaredKeys" />
        </div>
        <EditorContent :editor="editor"
            class="min-h-[5rem] px-3 py-2 text-sm [&_.tiptap]:outline-none" />
    </div>
</template>