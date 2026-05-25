<script setup>
defineProps({
    color: { type: String, default: 'nt' },
    dot: Boolean,
})
</script>

<template>
    <span class="er-stat" :class="color">
        <span v-if="dot" class="er-stat-dot"></span>
        <slot />
    </span>
</template>

<style scoped>
.er-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 1px 7px;
    font-size: 11px;
    font-weight: 500;
    border: 1px solid;
    border-radius: 2px;
    line-height: 1.4;
    white-space: nowrap;
}
.er-stat-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.er-stat.gr { background: var(--green-soft); color: var(--green); border-color: #a8d090; }
.er-stat.rd { background: var(--red-soft); color: var(--red); border-color: #e0a8a8; }
.er-stat.or { background: var(--orange-soft); color: var(--orange); border-color: #e4c08a; }
.er-stat.yl { background: var(--yellow-soft); color: var(--yellow); border-color: #d8c890; }
.er-stat.bl { background: var(--blue-soft); color: var(--blue-d); border-color: #a4bce4; }
.er-stat.nt { background: #eaedf0; color: var(--ink-3); border-color: #c5cdd5; }
</style>
