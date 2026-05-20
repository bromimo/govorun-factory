<script setup>
const model = defineModel({ type: Object, default: () => ({}) });
const props = defineProps({ keys: { type: Array, default: () => [] } });

function update(key, value) {
    model.value = { ...model.value, [key]: value };
}
</script>

<template>
    <details class="sse-details" v-if="keys.length">
        <summary class="sse-summary">Подставить состояние ({{ keys.length }})</summary>
        <div v-for="k in keys" :key="k" class="sse-row">
            <span class="sse-key">{{ k }}</span>
            <input :value="model[k] ?? ''" @input="update(k, $event.target.value)"
                class="field-input flex-1" />
        </div>
    </details>
</template>

<style scoped>
.sse-details {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 6px 8px;
}
.sse-summary { cursor: pointer; font-size: 12px; color: var(--ink-2); user-select: none; }
.sse-row { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
.sse-key { font-family: var(--mono, monospace); font-size: 11px; color: var(--ink-3); width: 100px; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; }
.field-input {
    height: 26px;
    padding: 0 8px;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    background: #fff;
    color: var(--ink);
    font-size: 12px;
    font-family: var(--font);
    box-sizing: border-box;
    min-width: 0;
}
.field-input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 2px rgba(58,114,196,.2); }
.flex-1 { flex: 1; }
</style>
