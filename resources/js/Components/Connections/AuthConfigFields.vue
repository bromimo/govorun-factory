<script setup>
import ErInput from '@/Components/Ui/ErInput.vue';
import ErSelect from '@/Components/Ui/ErSelect.vue';

const props = defineProps({
    type: String,
    modelValue: Object,
    isEdit: Boolean,
    errors: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['update:modelValue']);

function update(key, value) {
    emit('update:modelValue', { ...props.modelValue, [key]: value });
}
</script>

<template>
    <div class="acf-wrap">
        <template v-if="type === 'api_key'">
            <div class="acf-field">
                <label class="acf-lbl">Название заголовка / параметра</label>
                <ErInput
                    :modelValue="modelValue.key ?? 'X-API-Key'"
                    @update:modelValue="update('key', $event)"
                    placeholder="X-API-Key"
                    long
                />
            </div>
            <div class="acf-field">
                <label class="acf-lbl">Значение ключа</label>
                <ErInput
                    :modelValue="isEdit ? '' : (modelValue.value ?? '')"
                    @update:modelValue="update('value', $event)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущее значение' : 'Значение ключа'"
                    type="password"
                    long
                />
                <div v-if="errors['auth_config.value']" class="acf-err">{{ errors['auth_config.value'] }}</div>
            </div>
            <div class="acf-field">
                <label class="acf-lbl">Передавать в</label>
                <ErSelect
                    :modelValue="modelValue.in ?? 'header'"
                    @update:modelValue="update('in', $event)"
                >
                    <option value="header">Заголовке</option>
                    <option value="query">Query-параметре</option>
                </ErSelect>
                <div v-if="errors['auth_config.in']" class="acf-err">{{ errors['auth_config.in'] }}</div>
            </div>
        </template>

        <template v-else-if="type === 'bearer'">
            <div class="acf-field">
                <label class="acf-lbl">Bearer token</label>
                <ErInput
                    :modelValue="isEdit ? '' : (modelValue.token ?? '')"
                    @update:modelValue="update('token', $event)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущий токен' : 'Bearer token'"
                    type="password"
                    long
                />
                <div v-if="errors['auth_config.token']" class="acf-err">{{ errors['auth_config.token'] }}</div>
                <div v-if="isEdit" class="acf-hint">Пустое значение — оставить текущий токен</div>
            </div>
        </template>

        <template v-else-if="type === 'basic'">
            <div class="acf-field">
                <label class="acf-lbl">Логин</label>
                <ErInput
                    :modelValue="modelValue.login ?? ''"
                    @update:modelValue="update('login', $event)"
                    placeholder="Логин"
                    long
                />
            </div>
            <div class="acf-field">
                <label class="acf-lbl">Пароль</label>
                <ErInput
                    :modelValue="isEdit ? '' : (modelValue.password ?? '')"
                    @update:modelValue="update('password', $event)"
                    :placeholder="isEdit ? 'Оставьте пустым — сохранится текущий пароль' : 'Пароль'"
                    type="password"
                    long
                />
                <div v-if="errors['auth_config.password']" class="acf-err">{{ errors['auth_config.password'] }}</div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.acf-wrap { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
.acf-field { display: flex; flex-direction: column; gap: 4px; }
.acf-lbl { font-size: 11px; font-weight: 600; color: var(--ink-2); }
.acf-hint { font-size: 11px; color: var(--ink-3); }
.acf-err  { font-size: 11px; color: var(--red); }
</style>