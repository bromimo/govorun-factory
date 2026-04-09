<script setup>
import { computed } from 'vue';

const model = defineModel({ type: Object, default: () => ({ variables: [{ key: '', source: '' }] }) });

const variables = computed({
    get() {
        if (Array.isArray(model.value.variables)) return model.value.variables;
        if (model.value.key !== undefined) return [{ key: model.value.key, source: model.value.source }];
        return [{ key: '', source: '' }];
    },
    set(val) {
        model.value = { ...model.value, variables: val };
    },
});

function addVariable() {
    variables.value = [...variables.value, { key: '', source: '' }];
}

function removeVariable(index) {
    const next = variables.value.filter((_, i) => i !== index);
    variables.value = next.length ? next : [{ key: '', source: '' }];
}

function updateVariable(index, field, value) {
    const next = [...variables.value];
    next[index] = { ...next[index], [field]: value };
    variables.value = next;
}
</script>

<template>
    <div class="space-y-2">
        <div v-for="(v, i) in variables" :key="i" class="flex items-center gap-1.5">
            <input :value="v.key" @input="updateVariable(i, 'key', $event.target.value)" type="text"
                class="w-24 shrink-0 rounded border-gray-300 px-2 py-1.5 text-sm font-mono placeholder-gray-400" placeholder="key" />

            <span class="shrink-0 text-xs text-gray-400">=</span>

            <select :value="v.source" @change="updateVariable(i, 'source', $event.target.value)"
                class="min-w-0 flex-1 rounded border-gray-300 px-2 py-1.5 text-sm">
                <option value="" disabled>источник</option>
                <optgroup label="Сообщение">
                    <option value="message.text">message.text</option>
                    <option value="message.action">message.action</option>
                </optgroup>
                <optgroup label="Пользователь">
                    <option value="user.id">user.id</option>
                    <option value="user.firstName">user.firstName</option>
                    <option value="user.lastName">user.lastName</option>
                    <option value="user.email">user.email</option>
                </optgroup>
            </select>

            <button v-if="variables.length > 1" @click="removeVariable(i)" type="button"
                class="shrink-0 rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-500">
                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <button @click="addVariable" type="button"
            class="w-full rounded-md border border-dashed border-gray-300 px-3 py-1.5 text-xs text-gray-500 hover:border-gray-400 hover:text-gray-700">
            + Добавить переменную
        </button>
    </div>
</template>
