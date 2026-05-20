<script setup>
import { ref, computed } from 'vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';
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

const pendingMode = ref(null);

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
        if ((model.value.validation?.length ?? 0) > 0) {
            pendingMode.value = newMode;
            return;
        }
        applyMode(newMode);
    } else {
        const hasButtons = Array.isArray(model.value.keyboard?.buttons)
            && model.value.keyboard.buttons.some(row => Array.isArray(row) && row.length > 0);
        if (hasButtons) {
            pendingMode.value = newMode;
            return;
        }
        applyMode(newMode);
    }
}

function applyMode(newMode) {
    pendingMode.value = null;
    if (newMode === 'callback') {
        model.value = {
            ...model.value,
            mode: 'callback',
            validation: [],
            keyboard: model.value.keyboard ?? { type: 'inline', buttons: [] },
        };
    } else {
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
    <div class="af-wrap">
        <div>
            <label class="field-lbl">Режим</label>
            <div class="toggle-group">
                <button type="button" @click="setMode('text')"
                    :class="['toggle-btn', isText ? 'is-on' : '']">
                    Текст
                </button>
                <button type="button" @click="setMode('callback')"
                    :class="['toggle-btn', isCallback ? 'is-on' : '']">
                    Выбор кнопкой
                </button>
            </div>
        </div>

        <div>
            <label class="field-lbl">Текст вопроса</label>
            <RichTextEditor v-model="text" class="mt-1"
                placeholder="Как вас зовут?"
                :declared-keys="declaredStateKeys" />
            <StateWarning :uninitialized-keys="uninitializedKeys"
                :partially-initialized-keys="partiallyInitializedKeys"
                :undeclared-keys="undeclaredKeys" />
        </div>

        <MediaPicker v-if="media" v-model="media" />
        <button v-else type="button" @click="addMedia" class="add-btn">
            + Добавить медиа
        </button>

        <div>
            <label class="field-lbl">Имя шага</label>
            <div class="af-step-row">
                <input :value="model.stepName ?? ''" type="text"
                    @input="onStepNameInput($event)"
                    class="field-input mono"
                    :placeholder="autoStepName() || 'askName'" />
                <button v-if="model.stepName" type="button" @click="model.stepName = ''"
                    class="af-clear">
                    <svg class="af-clear-icon" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="field-hint">
                camelCase. Например: <span class="field-mono">askName</span>
            </p>
            <p v-if="stepNameWarning" class="field-warn">{{ stepNameWarning }}</p>
        </div>

        <ValidationEditor v-if="isText" v-model="validation"
            :bot-validation-messages="botValidationMessages" />

        <KeyboardSection v-if="isCallback" v-model="keyboard" :required="true" :declared-keys="declaredStateKeys" />
    </div>

    <ConfirmModal
        :show="!!pendingMode"
        title="Сменить режим?"
        :message="pendingMode === 'callback'
            ? 'Правила валидации будут удалены при переключении в режим «Выбор кнопкой».'
            : 'Настроенная клавиатура будет удалена при переключении в режим «Текст».'"
        confirm-label="Продолжить"
        variant="primary"
        @confirm="applyMode(pendingMode)"
        @cancel="pendingMode = null"
    />
</template>

<style scoped>
.af-wrap { display: flex; flex-direction: column; gap: 10px; }
.af-step-row { display: flex; gap: 6px; align-items: center; margin-top: 4px; }
.af-step-row .field-input { flex: 1; }
.af-clear { flex-shrink: 0; color: var(--ink-4); background: none; border: none; cursor: pointer; padding: 2px; display: flex; align-items: center; border-radius: var(--r-sm); }
.af-clear:hover { color: var(--red); }
.af-clear-icon { width: 14px; height: 14px; }
</style>