<script setup>
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps({
    variant: { type: String, default: 'info', validator: v => ['info', 'success', 'warning', 'error'].includes(v) },
    title: { type: String, default: null },
    closable: { type: Boolean, default: false },
});

defineEmits(['close']);

const visible = ref(true);
function close() {
    visible.value = false;
}
</script>

<template>
    <div v-if="visible" class="er-alert" :class="variant" v-bind="$attrs" role="alert">
        <div class="er-alert-body">
            <strong v-if="title" class="er-alert-title">{{ title }}</strong>
            <div class="er-alert-text"><slot /></div>
        </div>
        <button
            v-if="closable"
            type="button"
            class="er-alert-x"
            title="Закрыть"
            @click="close(); $emit('close');"
        >×</button>
    </div>
</template>

<style scoped>
.er-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 12px;
    border: 1px solid var(--bdr);
    border-radius: var(--r-md);
    font-size: 12px;
    color: var(--ink);
}
.er-alert.info { background: var(--blue-soft); border-color: var(--blue); }
.er-alert.success { background: var(--green-soft); border-color: var(--green); }
.er-alert.warning { background: var(--orange-soft); border-color: var(--orange); }
.er-alert.error { background: var(--red-soft); border-color: var(--red); }

.er-alert-body { flex: 1; min-width: 0; }
.er-alert-title {
    display: block;
    font-weight: 600;
    margin-bottom: 2px;
    font-size: 12px;
}
.er-alert-text { line-height: 1.4; }

.er-alert-x {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ink-3);
    font-size: 16px;
    line-height: 1;
    padding: 0 4px;
    min-height: 24px;
}
.er-alert-x:hover { color: var(--ink); }
</style>
