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
