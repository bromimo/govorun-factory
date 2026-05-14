<script setup>
import { ref, computed } from 'vue';
import VarsHint from './VarsHint.vue';
import StateWarning from './StateWarning.vue';
import MediaPicker from './MediaPicker.vue';
import KeyboardSection from './KeyboardSection.vue';
import ValidationEditor from './ValidationEditor.vue';
import RichTextEditor from './RichTextEditor.vue';
import { useStateWarnings } from './useStateWarnings.js';
import { toCamelCase, sanitizeIdentifier, identifierWarning } from '@/utils/translit';
import { stripTelegramHtml } from '@/utils/telegramHtml.js';

const model = defineModel({
    type: Object,
    default: () => ({ mode: 'text', stepName: '', text: '', media: null, validation: [], keyboard: null }),
});
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const stepNameWarning = ref('');
let stepNameWarningTimer = null;

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

const validation = computed({
    get: () => model.value.validation ?? [],
    set: (val) => { model.value = { ...model.value, validation: val }; },
});

function autoStepName() {
    const body = toCamelCase(stripTelegramHtml(model.value.text ?? ''));
    if (!body) return '';
    return 'ask' + body.charAt(0).toUpperCase() + body.slice(1);
}

function onStepNameInput(e) {
    const raw = e.target.value;
    const clean = sanitizeIdentifier(raw);
    const warning = identifierWarning(raw);

    if (warning) {
        stepNameWarning.value = warning;
        clearTimeout(stepNameWarningTimer);
        stepNameWarningTimer = setTimeout(() => { stepNameWarning.value = ''; }, 3000);
    } else {
        stepNameWarning.value = '';
    }

    model.value = { ...model.value, stepName: clean };
}

function setMode(newMode) {
    if (newMode === model.value.mode) return;

    if (newMode === 'callback') {
        // Уходим в callback — слетит validation (text-only), нужен confirm если есть правила
        if ((model.value.validation?.length ?? 0) > 0) {
            if (!confirm('При переключении в режим «Выбор кнопкой» правила валидации будут удалены. Продолжить?')) {
                return;
            }
        }
        model.value = {
            ...model.value,
            mode: 'callback',
            validation: [],
            keyboard: model.value.keyboard ?? { type: 'inline', buttons: [] },
        };
    } else {
        // Уходим в text — слетит keyboard (callback-only), нужен confirm если есть кнопки
        const hasButtons = Array.isArray(model.value.keyboard?.buttons)
            && model.value.keyboard.buttons.some(row => Array.isArray(row) && row.length > 0);
        if (hasButtons) {
            if (!confirm('При переключении в режим «Текст» настроенная клавиатура будет удалена. Продолжить?')) {
                return;
            }
        }
        model.value = {
            ...model.value,
            mode: 'text',
            keyboard: null,
        };
    }
}

function addMedia() {
    if (!media.value) {
        media.value = { type: 'photo', url: '' };
    }
}

const isText = computed(() => model.value.mode === 'text');
const isCallback = computed(() => model.value.mode === 'callback');
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Режим</label>
            <div class="inline-flex rounded-md border border-gray-300 bg-white p-0.5 text-xs">
                <button type="button" @click="setMode('text')"
                    :class="['px-3 py-1 rounded', isText ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                    Текст
                </button>
                <button type="button" @click="setMode('callback')"
                    :class="['px-3 py-1 rounded', isCallback ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50']">
                    Выбор кнопкой
                </button>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-500">Текст вопроса</label>
            <RichTextEditor v-model="text" class="mt-1"
                placeholder="Как вас зовут?"
                :declared-keys="declaredStateKeys" />
            <StateWarning :uninitialized-keys="uninitializedKeys"
                :partially-initialized-keys="partiallyInitializedKeys"
                :undeclared-keys="undeclaredKeys" />
            <VarsHint />
        </div>

        <MediaPicker v-if="media" v-model="media" />
        <button v-else type="button" @click="addMedia"
            class="text-xs text-indigo-600 hover:text-indigo-800">
            + Добавить медиа
        </button>

        <div>
            <label class="block text-xs font-medium text-gray-500">Имя шага</label>
            <div class="mt-1 flex gap-2">
                <input :value="model.stepName ?? ''" type="text"
                    @input="onStepNameInput($event)"
                    class="w-full rounded border-gray-300 text-sm font-mono placeholder-gray-400"
                    :placeholder="autoStepName() || 'askName'" />
                <button v-if="model.stepName" type="button" @click="model.stepName = ''"
                    class="shrink-0 text-gray-400 hover:text-red-500">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">
                camelCase. Например: <span class="font-mono">askName</span>
            </p>
            <p v-if="stepNameWarning" class="mt-1 text-xs text-amber-600">{{ stepNameWarning }}</p>
        </div>

        <ValidationEditor v-if="isText" v-model="validation"
            :bot-validation-messages="botValidationMessages" />

        <KeyboardSection v-if="isCallback" v-model="keyboard" :required="true" />
    </div>
</template>
