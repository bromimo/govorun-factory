<script setup>
const model = defineModel({ type: String, default: 'stop_flow' });
</script>

<template>
    <div class="eb-list">
        <label class="eb-opt"><input type="radio" :value="'stop_flow'" v-model="model" /> Остановить сценарий</label>
        <label class="eb-opt"><input type="radio" :value="'continue'" v-model="model" /> Продолжить (записать ошибку в <code class="eb-code">state.api_error</code>)</label>
        <label class="eb-opt"><input type="radio" :value="'branch'" v-model="model" /> Перейти по ветке <code class="eb-code">on_error</code></label>
    </div>
</template>

<style scoped>
.eb-list { display: flex; flex-direction: column; gap: 6px; }
.eb-opt { display: flex; align-items: baseline; gap: 6px; font-size: 12px; color: var(--ink-2); cursor: pointer; }
.eb-code { font-family: var(--mono); font-size: 11px; background: var(--surface-3); border-radius: var(--r-sm); padding: 0 3px; }
</style>