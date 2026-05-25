<script setup>
defineProps({ loading: Boolean })
</script>

<template>
    <div class="er-tbl-wrap">
        <div v-if="$slots.toolbar" class="er-tbl-toolbar">
            <slot name="toolbar" />
        </div>
        <div class="er-tbl-scroll">
            <table class="er-table">
                <thead><slot name="thead" /></thead>
                <tbody><slot /></tbody>
            </table>
        </div>
        <div v-if="$slots.paging" class="er-tbl-paging">
            <slot name="paging" />
        </div>
    </div>
</template>

<style scoped>
.er-tbl-wrap { border: 1px solid var(--bdr); border-radius: var(--r-md); overflow: hidden; background: var(--surface); }
.er-tbl-toolbar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-bottom: 1px solid var(--bdr);
}
.er-tbl-scroll { overflow-x: auto; }
.er-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.er-table :deep(thead th) {
    text-align: left;
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 600;
    color: var(--ink-2);
    border-bottom: 1px solid var(--bdr);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    white-space: nowrap;
    user-select: none;
}
.er-table :deep(tbody td) {
    padding: 6px 10px;
    border-bottom: 1px solid var(--bdr-l);
    vertical-align: middle;
}
.er-table :deep(tbody tr:hover) { background: var(--blue-soft); }
.er-table :deep(tbody tr.sel) { background: #d8e6f8; }
.er-table :deep(tbody tr:nth-child(even):not(:hover)) { background: var(--surface-2); }
.er-table :deep(tbody tr:last-child td) { border-bottom: none; }
.er-tbl-paging {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
    border-top: 1px solid var(--bdr);
    font-size: 11px;
    color: var(--ink-3);
}
</style>