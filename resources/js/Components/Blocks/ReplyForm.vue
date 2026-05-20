<script setup>
import { computed } from 'vue';
import StateWarning from './StateWarning.vue';
import MediaPicker from './MediaPicker.vue';
import KeyboardSection from './KeyboardSection.vue';
import RichTextEditor from './RichTextEditor.vue';
import { useStateWarnings } from './useStateWarnings.js';
import { stripTelegramHtml } from '@/utils/telegramHtml.js';

const model = defineModel({ type: Object, default: () => ({ text: '', media: null, keyboard: null }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botId: { type: [Number, String], default: null },
});

const { uninitializedKeys, partiallyInitializedKeys, undeclaredKeys } = useStateWarnings(
    () => stripTelegramHtml(model.value.text ?? ''),
    () => props.allStateKeys,
    () => props.declaredStateKeys,
    () => props.possiblyDeclaredStateKeys,
);

const text = computed({
    get: () => model.value.text ?? '',
    set: (val) => { model.value = { ...model.value, text: val }; },
});

const media = computed({
    get: () => model.value.media ?? null,
    set: (val) => { model.value = { ...model.value, media: val }; },
});

const keyboard = computed({
    get: () => model.value.keyboard ?? null,
    set: (val) => { model.value = { ...model.value, keyboard: val }; },
});

function addMedia() {
    if (!media.value) {
        media.value = { type: 'photo', url: '' };
    }
}

function addKeyboard() {
    if (!keyboard.value) {
        keyboard.value = { type: 'inline', buttons: [] };
    }
}
</script>

<template>
    <div class="rf-wrap">
        <div>
            <label class="field-lbl">Текст ответа</label>
            <RichTextEditor v-model="text" class="mt-1"
                placeholder="Привет, {{user.firstName}}!"
                :declared-keys="declaredStateKeys" />
            <StateWarning :uninitialized-keys="uninitializedKeys"
                :partially-initialized-keys="partiallyInitializedKeys"
                :undeclared-keys="undeclaredKeys" />
        </div>

        <MediaPicker v-if="media" v-model="media" :bot-id="props.botId" />
        <button v-else type="button" @click="addMedia" class="er-btn sm">
            + Добавить медиа
        </button>

        <KeyboardSection v-if="keyboard" v-model="keyboard" :declared-keys="declaredStateKeys" />
        <button v-else type="button" @click="addKeyboard" class="er-btn sm">
            + Добавить клавиатуру
        </button>
    </div>
</template>

<style scoped>
.rf-wrap { display: flex; flex-direction: column; gap: 10px; }
</style>