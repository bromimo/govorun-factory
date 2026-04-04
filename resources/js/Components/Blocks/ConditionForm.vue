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
        <label class="block text-xs font-medium text-gray-500">Поле для проверки</label>

        <div class="mt-1 flex items-center gap-2 text-xs">
            <button type="button" @click="setMode(false)"
                class="rounded px-2 py-0.5"
                :class="!isVariable ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-500 hover:text-gray-700'">
                Источник
            </button>
            <button type="button" @click="setMode(true)"
                class="rounded px-2 py-0.5"
                :class="isVariable ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-gray-500 hover:text-gray-700'">
                Переменная
            </button>
        </div>

        <select v-if="!isVariable" v-model="model.field"
            class="mt-1 w-full rounded border-gray-300 text-sm">
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
            class="mt-1 w-full rounded border-gray-300 text-sm font-mono placeholder-gray-400"
            placeholder="name" />
        <p v-if="isVariable && !uninitializedKeys.length && !undeclaredKeys.length" class="mt-0.5 text-xs text-gray-400">Имя переменной из блока «Сохранить состояние»</p>
        <StateWarning :uninitialized-keys="uninitializedKeys" :undeclared-keys="undeclaredKeys" />

        <p class="mt-1 text-xs text-gray-400">Значения задаются на исходящих связях (edge labels)</p>
    </div>
</template>
