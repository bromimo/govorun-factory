<script setup>
const model = defineModel({ type: Array, default: () => [] });

function update(i, field, value) {
    const next = [...model.value];
    next[i] = { ...next[i], [field]: value };
    model.value = next;
}
function remove(i) {
    model.value = model.value.filter((_, idx) => idx !== i);
}
</script>

<template>
    <div v-if="model.length" class="rml-wrap">
        <div class="rml-title">Сохранённые поля</div>
        <div v-for="(m, i) in model" :key="i" class="rml-row">
            <input :value="m.json_path" @input="update(i, 'json_path', $event.target.value)"
                class="field-input mono flex-1" />
            <span class="rml-arrow">→ state.</span>
            <input :value="m.state_key" @input="update(i, 'state_key', $event.target.value)"
                class="field-input key-w" />
            <button type="button" @click="remove(i)" class="rml-del">✕</button>
        </div>
    </div>
</template>

<style scoped>
.rml-wrap {
    border: 1px solid var(--bdr);
    border-radius: var(--r-sm);
    padding: 8px;
    margin-top: 4px;
}
.rml-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--ink-3); margin-bottom: 6px; }
.rml-row { display: flex; align-items: center; gap: 6px; margin-bottom: 4px; }
.rml-arrow { font-size: 11px; color: var(--ink-4); flex-shrink: 0; white-space: nowrap; }
.rml-del { color: var(--red); background: none; border: none; cursor: pointer; padding: 0 4px; font-size: 13px; flex-shrink: 0; }
.key-w { width: 100px; flex-shrink: 0; }
</style>