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
.eb-list {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    overflow: hidden;
}
.eb-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    font-size: 12px;
    color: var(--ink-2);
    cursor: pointer;
    background: var(--surface);
    border-left: 2px solid transparent;
    transition: background .1s, color .1s;
}
.eb-opt + .eb-opt { border-top: 1px solid var(--bdr-l); }
.eb-opt:has(input:checked) {
    background: var(--blue-soft);
    border-left-color: var(--blue);
    color: var(--ink);
}
.eb-opt:not(:has(input:checked)):hover { background: var(--surface-2); }
.eb-opt input[type="radio"] { accent-color: var(--blue); flex-shrink: 0; }
.eb-code {
    font-family: var(--mono);
    font-size: 11px;
    background: var(--surface-3);
    border-radius: var(--r-sm);
    padding: 0 3px;
}
</style>