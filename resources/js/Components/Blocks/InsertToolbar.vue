<script setup>
import { ref } from 'vue';
import EmojiPickerPopover from './EmojiPickerPopover.vue';
import VariablePickerPopover from './VariablePickerPopover.vue';
import { insertAtCursor } from '@/utils/insertAtCursor';

const props = defineProps({
    target: { type: Object, default: null },
    declaredKeys: { type: Array, default: () => [] },
});

const toolbarRef = ref(null);
const emojiOpen = ref(false);
const varsOpen = ref(false);

function toggle(which) {
    if (which === 'emoji') {
        varsOpen.value = false;
        emojiOpen.value = !emojiOpen.value;
    } else {
        emojiOpen.value = false;
        varsOpen.value = !varsOpen.value;
    }
}

function onEmoji(emoji) {
    insertAtCursor(props.target, emoji);
    emojiOpen.value = false;
}

function onVar(key) {
    insertAtCursor(props.target, `{{ ${key} }}`);
    varsOpen.value = false;
}
</script>

<template>
    <div ref="toolbarRef" class="relative inline-flex items-center gap-1">
        <button type="button" :disabled="!target"
            @click="toggle('emoji')"
            title="Вставить эмодзи"
            aria-label="Вставить эмодзи"
            class="inline-flex h-6 w-6 items-center justify-center text-base text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
            😀
        </button>
        <button type="button" :disabled="!target"
            @click="toggle('vars')"
            title="Вставить переменную"
            aria-label="Вставить переменную"
            class="inline-flex h-6 w-6 items-center justify-center font-mono text-sm text-gray-400 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
            {…}
        </button>
        <EmojiPickerPopover v-if="emojiOpen"
            :anchor="toolbarRef"
            @select="onEmoji"
            @close="emojiOpen = false" />
        <VariablePickerPopover v-if="varsOpen"
            :anchor="toolbarRef"
            :declared-keys="declaredKeys"
            @select="onVar"
            @close="varsOpen = false" />
    </div>
</template>
