<script setup>
import { ref, computed } from 'vue';
import StateWarning from './StateWarning.vue';

const model = defineModel({ type: Object, default: () => ({ field: '' }) });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
});

const sources = ['message.text', 'message.action', 'user.id', 'user.firstName', 'user.lastName', 'user.email'];

const isVariable = ref(!sources.includes(model.value.field) && model.value.field !== '');

const uninitializedKeys = computed(() => {
    const field = model.value.field;
    if (!isVariable.value || !field) return [];
    if (props.declaredStateKeys.includes(field)) return [];
    if (props.allStateKeys.includes(field)) return [field];
    return [];
});

const undeclaredKeys = computed(() => {
    const field = model.value.field;
    if (!isVariable.value || !field) return [];
    if (props.declaredStateKeys.includes(field)) return [];
    if (props.allStateKeys.includes(field)) return [];
    return [field];
});

function setMode(variable) {
    isVariable.value = variable;
    model.value.field = '';
}
</script>

<template>
    <div>
        <label class="field-lbl">Поле для проверки</label>

        <div class="toggle-group mt-1">
            <button type="button" @click="setMode(false)"
                :class="['toggle-btn', !isVariable ? 'is-on' : '']">
                Источник
            </button>
            <button type="button" @click="setMode(true)"
                :class="['toggle-btn', isVariable ? 'is-on' : '']">
                Переменная
            </button>
        </div>

        <select v-if="!isVariable" v-model="model.field" class="field-sel mt-1">
            <option value="" disabled>Выберите источник</option>
            <optgroup label="Сообщение">
                <option value="message.text">message.text — текст сообщения</option>
                <option value="message.action">message.action — действие кнопки</option>
            </optgroup>
            <optgroup label="Пользователь">
                <option value="user.id">user.id — ID пользователя</option>
                <option value="user.firstName">user.firstName — имя</option>
                <option value="user.lastName">user.lastName — фамилия</option>
                <option value="user.email">user.email — email</option>
            </optgroup>
        </select>

        <input v-else v-model="model.field" type="text"
            class="field-input mono mt-1"
            placeholder="name" />
        <p v-if="isVariable && !uninitializedKeys.length && !undeclaredKeys.length" class="field-hint">Имя переменной из блока «Сохранить состояние»</p>
        <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />

        <p class="field-hint mt-1">Значения задаются на исходящих связях (edge labels)</p>
    </div>
</template>

<style scoped>
.field-lbl { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--ink-3); }
.field-hint { font-size: 11px; color: var(--ink-4); margin-top: 3px; }
.field-input, .field-sel {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    width: 100%;
    box-sizing: border-box;
    display: block;
}
.field-input.mono { font-family: var(--mono, monospace); }
.field-input:focus, .field-sel:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.mt-1 { margin-top: 4px; }
.mt-1.field-sel { margin-top: 4px; }
.toggle-group { display: inline-flex; border: 1px solid var(--bdr-d); border-radius: var(--r-sm); background: var(--surface); padding: 2px; }
.toggle-btn { padding: 2px 10px; border-radius: calc(var(--r-sm) - 1px); font-size: 11px; color: var(--ink-2); background: none; border: none; cursor: pointer; font-family: var(--font); }
.toggle-btn.is-on { background: var(--blue); color: #fff; }
.toggle-btn:not(.is-on):hover { background: var(--surface-2); }
</style>