<script setup>
const model = defineModel({ type: String, default: 'stop_flow' });
</script>

<template>
    <div class="space-y-2 text-sm">
        <label class="block"><input type="radio" :value="'stop_flow'" v-model="model" /> Остановить сценарий</label>
        <label class="block"><input type="radio" :value="'continue'" v-model="model" /> Продолжить (записать ошибку в <code>state.api_error</code>)</label>
        <label class="block"><input type="radio" :value="'branch'" v-model="model" /> Перейти по ветке <code>on_error</code></label>
    </div>
</template>