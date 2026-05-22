<script setup>
import { onMounted, onUnmounted, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
    show:      { type: Boolean, default: false },
    title:     { type: String,  default: '' },
    width:     { type: String,  default: '480px' },
    closeable: { type: Boolean, default: true },
})

const emit = defineEmits(['close'])

const close = () => { if (props.closeable) emit('close') }

const onKeydown = (e) => { if (e.key === 'Escape' && props.show) { e.preventDefault(); close() } }

watch(() => props.show, (val) => { document.body.style.overflow = val ? 'hidden' : '' })

onMounted(() => {
    document.addEventListener('keydown', onKeydown)
    if (props.show) document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
})
</script>

<template>
    <Teleport to="body">
        <Transition name="er-drawer">
            <div v-if="show" class="er-drawer-root">
                <div class="er-drawer-bd" @click="close" />
                <div class="er-drawer-panel" :style="{ width }">
                    <div v-if="title" class="er-drawer-hdr">
                        <span class="er-drawer-title">{{ title }}</span>
                        <button v-if="closeable" class="er-drawer-x" type="button" @click="close">
                            <X :size="14" />
                        </button>
                    </div>
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.er-drawer-root {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    justify-content: flex-end;
    font-family: var(--font);
    font-size: 12px;
}
.er-drawer-bd {
    position: absolute;
    inset: 0;
    background: rgba(26, 34, 48, 0.4);
}
.er-drawer-panel {
    position: relative;
    z-index: 1;
    height: 100%;
    background: var(--surface);
    border-left: 1px solid var(--bdr);
    box-shadow: -4px 0 24px rgba(0,0,0,.12);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.er-drawer-hdr {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--bdr);
}
.er-drawer-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
}
.er-drawer-x {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    min-width: 28px;
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-3);
    border-radius: var(--r-sm);
}
.er-drawer-x:hover { background: var(--surface-3); color: var(--ink); }

.er-drawer-enter-active { transition: opacity .2s ease-out; }
.er-drawer-leave-active { transition: opacity .15s ease-in; }
.er-drawer-enter-from .er-drawer-bd,
.er-drawer-leave-to   .er-drawer-bd { opacity: 0; }
.er-drawer-enter-from .er-drawer-panel,
.er-drawer-leave-to   .er-drawer-panel { transform: translateX(100%); }
.er-drawer-enter-active .er-drawer-panel,
.er-drawer-leave-active .er-drawer-panel { transition: transform .22s ease-out; }
</style>