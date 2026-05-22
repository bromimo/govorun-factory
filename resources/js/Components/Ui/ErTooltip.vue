<script setup>
import { ref, computed, onUnmounted } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    content: { type: String, required: true },
    maxWidth: { type: Number, default: 320 },
    delay: { type: Number, default: 300 },
});

const visible = ref(false);
const triggerEl = ref(null);
const position = ref({ top: 0, left: 0 });
let showTimerId = null;

function show() {
    if (!triggerEl.value) return;
    showTimerId = setTimeout(() => {
        const rect = triggerEl.value.getBoundingClientRect();
        position.value = {
            top: rect.bottom + 6 + window.scrollY,
            left: rect.left + rect.width / 2 + window.scrollX,
        };
        visible.value = true;
    }, props.delay);
}

function hide() {
    if (showTimerId !== null) {
        clearTimeout(showTimerId);
        showTimerId = null;
    }
    visible.value = false;
}

onUnmounted(() => {
    if (showTimerId !== null) {
        clearTimeout(showTimerId);
    }
});

const style = computed(() => ({
    top: `${position.value.top}px`,
    left: `${position.value.left}px`,
    maxWidth: `${props.maxWidth}px`,
}));
</script>

<template>
    <span
        ref="triggerEl"
        class="er-tip-trigger"
        v-bind="$attrs"
        @mouseenter="show"
        @mouseleave="hide"
        @focus="show"
        @blur="hide"
    >
        <slot />
    </span>
    <Teleport to="body">
        <Transition name="er-tip">
            <div
                v-if="visible && content"
                class="er-tip"
                :style="style"
                role="tooltip"
            >
                {{ content }}
            </div>
        </Transition>
    </Teleport>
</template>

<style>
.er-tip-trigger {
    display: inline-block;
}
.er-tip {
    position: absolute;
    transform: translateX(-50%);
    background: var(--ink);
    color: var(--surface);
    font-size: 11px;
    line-height: 1.4;
    padding: 6px 10px;
    border-radius: var(--r-md);
    box-shadow: var(--sh-md);
    z-index: 10000;
    pointer-events: none;
    white-space: pre-wrap;
    word-break: break-word;
}
.er-tip-enter-from,
.er-tip-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(-2px);
}
.er-tip-enter-active,
.er-tip-leave-active {
    transition: opacity .12s, transform .12s;
}
</style>