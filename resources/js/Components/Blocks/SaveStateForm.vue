<script setup>
const model = defineModel({ type: Object, default: () => ({ key: '', source: '' }) });
</script>

<template>
    <div class="space-y-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Ключ (key)</label>
            <input v-model="model.key" type="text" class="mt-1 w-full rounded border-gray-300 text-sm placeholder-gray-400" placeholder="name" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">Источник (source)</label>
            <select v-model="model.source" class="mt-1 w-full rounded border-gray-300 text-sm">
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
        </div>
    </div>
</template>
