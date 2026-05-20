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
    if (field === 'key') {
        value = value.replace(/\./g, '');
    }
    const next = [...variables.value];
    next[index] = { ...next[index], [field]: value };
    variables.value = next;
}
</script>

<template>
    <div class="ssf-wrap">
        <div v-for="(v, i) in variables" :key="i" class="ssf-row">
            <input :value="v.key" @input="updateVariable(i, 'key', $event.target.value)" type="text"
                class="field-input mono key-col" placeholder="key" />

            <span class="ssf-eq">=</span>

            <select :value="v.source" @change="updateVariable(i, 'source', $event.target.value)"
                class="field-sel flex-1">
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

            <button v-if="variables.length > 1" @click="removeVariable(i)" type="button" class="ssf-del">
                <svg class="del-icon" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <button @click="addVariable" type="button" class="add-btn ssf-full">
            + Добавить переменную
        </button>
    </div>
</template>

<style scoped>
.ssf-wrap { display: flex; flex-direction: column; gap: 6px; }
.ssf-row { display: flex; align-items: center; gap: 4px; }
.ssf-eq { flex-shrink: 0; font-size: 11px; color: var(--ink-4); }
.ssf-del { flex-shrink: 0; padding: 3px; border-radius: var(--r-sm); background: none; border: none; cursor: pointer; color: var(--ink-4); display: flex; align-items: center; }
.ssf-del:hover { color: var(--red); background: var(--red-soft); }
.del-icon { width: 14px; height: 14px; }
.ssf-full { width: 100%; justify-content: center; }
.key-col { width: 80px; flex-shrink: 0; }
</style>