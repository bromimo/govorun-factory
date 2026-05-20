<script setup>
import { ref, computed } from 'vue';
import KeyboardPreview from './KeyboardPreview.vue';
import KeyboardEditorModal from './KeyboardEditorModal.vue';
import ConfirmModal from '@/Components/Ui/ConfirmModal.vue';

const model = defineModel({ type: Object, default: null });
const props = defineProps({
    required: { type: Boolean, default: false },
    declaredKeys: { type: Array, default: () => [] },
});

const editorOpen = ref(false);
const pendingType = ref(null);

const keyboardType = computed(() => model.value?.type ?? 'inline');

const buttons = computed({
    get: () => model.value?.buttons ?? [],
    set: (val) => {
        model.value = { ...(model.value ?? { type: 'inline' }), buttons: val };
    },
});

const resize = computed({
    get: () => model.value?.resize ?? false,
    set: (val) => { model.value = { ...(model.value ?? { type: 'reply', resize: false, oneTime: false, buttons: [] }), resize: val }; },
});

const oneTime = computed({
    get: () => model.value?.oneTime ?? false,
    set: (val) => { model.value = { ...(model.value ?? { type: 'reply', resize: false, oneTime: false, buttons: [] }), oneTime: val }; },
});

const hasButtons = computed(() => {
    const b = model.value?.buttons;
    return Array.isArray(b) && b.some(row => Array.isArray(row) && row.length > 0);
});

function switchType(newType) {
    if (newType === keyboardType.value) return;
    if (hasButtons.value) {
        pendingType.value = newType;
        return;
    }
    applyType(newType);
}

function applyType(newType = pendingType.value) {
    pendingType.value = null;
    if (newType === 'reply') {
        model.value = { type: 'reply', resize: false, oneTime: false, buttons: [] };
    } else {
        model.value = { type: 'inline', buttons: [] };
    }
}

function clear() {
    model.value = null;
}
</script>

<template>
    <div class="ks-wrap">
        <div class="ks-header">
            <label class="field-lbl">Клавиатура</label>
            <button v-if="!required && model" type="button" @click="clear" class="field-del">
                Удалить
            </button>
        </div>

        <div class="toggle-group">
            <button type="button" @click="switchType('inline')"
                :class="['toggle-btn', keyboardType === 'inline' ? 'is-on' : '']">
                Inline
            </button>
            <button type="button" @click="switchType('reply')"
                :class="['toggle-btn', keyboardType === 'reply' ? 'is-on' : '']">
                Reply
            </button>
        </div>

        <div v-if="keyboardType === 'reply'" class="ks-checks">
            <label class="ks-check">
                <input type="checkbox" v-model="resize" />
                Подогнать размер
            </label>
            <label class="ks-check">
                <input type="checkbox" v-model="oneTime" />
                Скрыть после нажатия
            </label>
        </div>

        <KeyboardPreview :model-value="buttons" :keyboard-type="keyboardType" />

        <button type="button" @click="editorOpen = true" class="ks-edit-btn">
            {{ hasButtons ? 'Редактировать клавиатуру' : 'Добавить кнопки' }}
        </button>

        <KeyboardEditorModal v-model="buttons" v-model:open="editorOpen"
            :declared-keys="props.declaredKeys" :keyboard-type="keyboardType" />

        <ConfirmModal
            :show="!!pendingType"
            title="Сменить тип клавиатуры?"
            message="Все настроенные кнопки будут удалены."
            confirm-label="Продолжить"
            variant="primary"
            @confirm="applyType()"
            @cancel="pendingType = null"
        />
    </div>
</template>

<style scoped>
.ks-wrap { display: flex; flex-direction: column; gap: 6px; }
.ks-header { display: flex; align-items: center; justify-content: space-between; }
.field-lbl { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-3); }
.field-del { font-size: 11px; color: var(--red); background: none; border: none; cursor: pointer; padding: 0; font-family: var(--font); }
.field-del:hover { opacity: .8; }
.toggle-group { display: inline-flex; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: var(--surface); padding: 2px; }
.toggle-btn { padding: 2px 10px; border-radius: calc(var(--r-sm) - 1px); font-size: 11px; color: var(--ink-2); background: none; border: none; cursor: pointer; font-family: var(--font); }
.toggle-btn.is-on { background: var(--blue); color: #fff; }
.toggle-btn:not(.is-on):hover { background: var(--surface-2); }
.ks-checks { display: flex; gap: 12px; }
.ks-check { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--ink-2); cursor: pointer; }
.ks-edit-btn {
    border-radius: var(--r-sm);
    border: 1px solid var(--bdr-d);
    padding: 4px 10px;
    font-size: 12px;
    color: var(--ink-2);
    background: var(--surface);
    cursor: pointer;
    font-family: var(--font);
    transition: background .1s;
}
.ks-edit-btn:hover { background: var(--surface-2); }
</style>