<script setup>
defineProps({
    label: String,
    required: Boolean,
    hint: String,
    stacked: Boolean,
})
</script>

<template>
    <div :class="stacked ? 'fld-stacked' : 'fld-classic'">
        <div v-if="!stacked" class="fld-label-r">
            {{ label }}<span v-if="required" class="req"> *</span>
        </div>
        <div class="fld-body">
            <div v-if="stacked && label" class="fld-label-t">
                {{ label }}<span v-if="required" class="req"> *</span>
            </div>
            <slot />
            <div v-if="hint" class="fld-hint">{{ hint }}</div>
        </div>
    </div>
</template>

<style scoped>
.fld-classic {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 14px;
    padding: 6px 0;
    align-items: start;
}
.fld-label-r {
    padding-top: 5px;
    font-size: 12px;
    color: var(--ink-2);
    text-align: right;
    font-weight: 500;
}
.fld-body { display: flex; flex-direction: column; gap: 3px; }
.fld-hint { font-size: 11px; color: var(--ink-3); }
.req { color: var(--red); }
.fld-stacked { padding: 6px 0; }
.fld-label-t {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--ink-3);
    margin-bottom: 4px;
}
</style>