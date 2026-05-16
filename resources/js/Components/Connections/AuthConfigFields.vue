<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: String,
    modelValue: Object,
    isEdit: Boolean,
});
const emit = defineEmits(['update:modelValue']);

const v = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function update(key, value) {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
}
</script>

<template>
    <div class="mt-3 space-y-2">
        <template v-if="type === 'api_key'">
            <div>
                <label class="text-xs text-gray-500">Название заголовка / параметра</label>
                <input
                    :value="modelValue.key ?? 'X-API-Key'"
                    @input="update('key', $event.target.value)"
                    placeholder="X-API-Key"
                    class="mt-1 w-full rounded border-gray-300 font-mono text-sm"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500">Значение ключа</label>
                <input
                    :value="isEdit ? '' : (modelValue.value ?? '')"
                    @input="update('value', $event.target.value)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущее значение' : 'Значение ключа'"
                    type="password"
                    class="mt-1 w-full rounded border-gray-300 text-sm"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500">Передавать в</label>
                <select
                    :value="modelValue.in ?? 'header'"
                    @change="update('in', $event.target.value)"
                    class="mt-1 w-full rounded border-gray-300 text-sm"
                >
                    <option value="header">Заголовке</option>
                    <option value="query">Query-параметре</option>
                </select>
            </div>
        </template>

        <template v-else-if="type === 'bearer'">
            <div>
                <label class="text-xs text-gray-500">Bearer token</label>
                <input
                    :value="isEdit ? '' : (modelValue.token ?? '')"
                    @input="update('token', $event.target.value)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущий токен' : 'Bearer token'"
                    type="password"
                    class="mt-1 w-full rounded border-gray-300 text-sm"
                />
                <div v-if="isEdit" class="mt-1 text-xs text-gray-400">Пустое значение — оставить текущий токен</div>
            </div>
        </template>

        <template v-else-if="type === 'basic'">
            <div>
                <label class="text-xs text-gray-500">Логин</label>
                <input
                    :value="modelValue.login ?? ''"
                    @input="update('login', $event.target.value)"
                    placeholder="Логин"
                    class="mt-1 w-full rounded border-gray-300 text-sm"
                />
            </div>
            <div>
                <label class="text-xs text-gray-500">Пароль</label>
                <input
                    :value="isEdit ? '' : (modelValue.password ?? '')"
                    @input="update('password', $event.target.value)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущий пароль' : 'Пароль'"
                    type="password"
                    class="mt-1 w-full rounded border-gray-300 text-sm"
                />
            </div>
        </template>
    </div>
</template>