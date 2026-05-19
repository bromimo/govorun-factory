<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    delta: String,
    color: { type: String, default: 'blue' },
    icon: Object,
})
</script>

<template>
    <div class="er-counter">
        <div class="er-counter-icon" :class="color">
            <component v-if="icon" :is="icon" :size="20" />
        </div>
        <div class="er-counter-body">
            <div class="er-counter-l">{{ label }}</div>
            <div class="er-counter-v">{{ value }}</div>
            <div v-if="delta" class="er-counter-d">{{ delta }}</div>
        </div>
    </div>
</template>

<style scoped>
.er-counter { background: var(--surface); border: 1px solid var(--bdr); border-radius: var(--r-md); overflow: hidden; display: flex; align-items: stretch; }
.er-counter-icon { width: 48px; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.er-counter-icon.blue { background: linear-gradient(180deg, #5a8cd4, #2a5ca0); }
.er-counter-icon.green { background: linear-gradient(180deg, #6aa860, #3a7a3a); }
.er-counter-icon.orange { background: linear-gradient(180deg, #e0a050, #c87020); }
.er-counter-icon.red { background: linear-gradient(180deg, #d06060, #b03030); }
.er-counter-body { padding: 8px 12px; flex: 1; }
.er-counter-l { font-size: 11px; color: var(--ink-3); font-weight: 500; }
.er-counter-v { font-size: 20px; font-weight: 600; color: var(--ink); letter-spacing: -.02em; font-family: var(--mono); margin-top: 1px; }
.er-counter-d { font-size: 10px; color: var(--green); margin-top: 1px; }
</style>