<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';
import ErBadge from '@/Components/Ui/ErBadge.vue';

const props = defineProps({
    status: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['change']);

const open = ref(false);
const root = ref(null);

const meta = {
    active:   { label: 'активен',   color: 'gr', dot: true  },
    inactive: { label: 'неактивен', color: 'nt', dot: false },
    draft:    { label: 'черновик',  color: 'yl', dot: false },
};

const current = computed(() => meta[props.status] ?? meta.draft);

const options = ['active', 'inactive', 'draft'];

function toggle() {
    if (props.disabled) return;
    open.value = !open.value;
}

function pick(value) {
    open.value = false;
    if (value === props.status) return;
    emit('change', value);
}

function onDocClick(e) {
    if (root.value && !root.value.contains(e.target)) open.value = false;
}
function onEsc(e) {
    if (e.key === 'Escape') open.value = false;
}

document.addEventListener('click', onDocClick);
document.addEventListener('keydown', onEsc);
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocClick);
    document.removeEventListener('keydown', onEsc);
});
</script>

<template>
    <span ref="root" class="sb-host">
        <button type="button" class="sb-btn" :disabled="disabled" @click.stop="toggle">
            <ErBadge :color="current.color" :dot="current.dot">
                {{ current.label }}<span v-if="!disabled" class="sb-arr">▾</span>
            </ErBadge>
        </button>
        <div v-if="open" class="sb-popup">
            <button
                v-for="opt in options"
                :key="opt"
                type="button"
                class="sb-item"
                @click.stop="pick(opt)"
            >
                <span class="sb-ic" :class="meta[opt].color"></span>
                {{ meta[opt].label }}
                <span v-if="opt === status" class="sb-check">✓</span>
            </button>
        </div>
    </span>
</template>

<style scoped>
.sb-host { position: relative; display: inline-block; }
.sb-btn { background: none; border: 0; padding: 0; cursor: pointer; }
.sb-btn:disabled { cursor: default; }
.sb-arr { font-size: 8px; opacity: .7; margin-left: 4px; }
.sb-popup {
    position: absolute; top: calc(100% + 4px); left: 0;
    min-width: 140px; background: #fff;
    border: 1px solid var(--bdr); border-radius: var(--r-md);
    box-shadow: var(--sh-md); padding: 4px; z-index: 10;
}
.sb-item {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 5px 8px; cursor: pointer;
    border: 0; background: none; text-align: left;
    border-radius: var(--r-sm); font-size: 12px; color: var(--ink);
}
.sb-item:hover { background: var(--surface-2); }
.sb-ic { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
.sb-ic.gr { background: var(--green); }
.sb-ic.nt { background: transparent; border: 1px solid var(--bdr-d); }
.sb-ic.yl { background: var(--yellow); }
.sb-check { margin-left: auto; color: var(--blue); font-weight: 700; }
</style>