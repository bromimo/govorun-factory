<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';

const props = defineProps({
    modelValue: { default: null },
    options: { type: Array, required: true }, // [{ value, label }]
});

const emit = defineEmits(['update:modelValue']);

const wrapEl = ref(null);
const pillEl = ref(null);
const btnEls = ref([]);

const PAD = 3;
let springRaf = null;

function currentIndex() {
    const idx = props.options.findIndex(o => o.value === props.modelValue);
    return idx < 0 ? 0 : idx;
}

function sw() {
    return btnEls.value[0]?.getBoundingClientRect().width ?? 0;
}

function sx(i) {
    return PAD + i * sw();
}

function equalize() {
    if (!btnEls.value.length) return;
    btnEls.value.forEach(b => { if (b) b.style.width = ''; });
    const maxW = Math.ceil(Math.max(...btnEls.value.map(b => b?.getBoundingClientRect().width ?? 0)));
    btnEls.value.forEach(b => { if (b) b.style.width = maxW + 'px'; });
}

function pillNow() {
    if (!pillEl.value) return 0;
    return new DOMMatrix(getComputedStyle(pillEl.value).transform).m41;
}

function springTo(to) {
    cancelAnimationFrame(springRaf);
    let pos = pillNow();
    let vel = 0;
    let last = performance.now();
    const K = 260, B = 13;
    const minX = sx(0);
    const maxX = sx(props.options.length - 1);

    function tick(now) {
        const dt = Math.min((now - last) * 0.001, 0.033);
        last = now;
        vel += (-K * (pos - to) - B * vel) * dt;
        pos += vel * dt;
        if (pos < minX) { pos = minX; if (vel < 0) vel = -vel * 0.3; }
        if (pos > maxX) { pos = maxX; if (vel > 0) vel = -vel * 0.3; }
        if (pillEl.value) pillEl.value.style.transform = `translateX(${pos}px)`;
        if (Math.abs(pos - to) < 0.15 && Math.abs(vel) < 0.3) {
            if (pillEl.value) pillEl.value.style.transform = `translateX(${to}px)`;
            return;
        }
        springRaf = requestAnimationFrame(tick);
    }
    springRaf = requestAnimationFrame(tick);
}

function snapTo(idx, animate = true) {
    idx = Math.max(0, Math.min(props.options.length - 1, idx));
    if (!pillEl.value) return;
    pillEl.value.style.width = sw() + 'px';
    if (animate) {
        springTo(sx(idx));
    } else {
        cancelAnimationFrame(springRaf);
        pillEl.value.style.transform = `translateX(${sx(idx)}px)`;
    }
}

let active = false;
let didDrag = false;
let startCX = 0;
let startPX = 0;
let lastEvX = 0;

function ex(e) { return e.touches ? e.touches[0].clientX : e.clientX; }

function onDown(e) {
    active = true;
    didDrag = false;
    startCX = ex(e);
    startPX = pillNow();
    lastEvX = startCX;
    cancelAnimationFrame(springRaf);
    wrapEl.value?.classList.add('dragging');
    e.preventDefault();
}

function onMove(e) {
    if (!active) return;
    lastEvX = ex(e);
    const minX = sx(0);
    const maxX = sx(props.options.length - 1);
    const newX = Math.max(minX, Math.min(maxX, startPX + lastEvX - startCX));
    if (pillEl.value) {
        pillEl.value.style.width = sw() + 'px';
        pillEl.value.style.transform = `translateX(${newX}px)`;
    }
    e.preventDefault();
}

function onUp(e) {
    if (!active) return;
    active = false;
    wrapEl.value?.classList.remove('dragging');
    if (e) lastEvX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
    const displacement = lastEvX - startCX;
    const ci = currentIndex();
    if (Math.abs(displacement) > 5) {
        didDrag = true;
        const ni = Math.max(0, Math.min(props.options.length - 1, ci + (displacement > 0 ? 1 : -1)));
        snapTo(ni);
        if (ni !== ci) emit('update:modelValue', props.options[ni].value);
    } else {
        snapTo(ci);
    }
}

function onBtnClick(i) {
    if (didDrag) { didDrag = false; return; }
    if (!active) {
        snapTo(i);
        emit('update:modelValue', props.options[i].value);
    }
}

watch(() => props.modelValue, () => {
    if (!active) snapTo(currentIndex());
});

const handleMouseMove = (e) => onMove(e);
const handleTouchMove = (e) => onMove(e);
const handleMouseUp = (e) => onUp(e);
const handleTouchEnd = (e) => onUp(e);
const handleResize = () => { equalize(); snapTo(currentIndex(), false); };

onMounted(() => {
    equalize();
    snapTo(currentIndex(), false);
    window.addEventListener('mousemove', handleMouseMove, { passive: false });
    window.addEventListener('touchmove', handleTouchMove, { passive: false });
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchend', handleTouchEnd);
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    cancelAnimationFrame(springRaf);
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('touchmove', handleTouchMove);
    window.removeEventListener('mouseup', handleMouseUp);
    window.removeEventListener('touchend', handleTouchEnd);
    window.removeEventListener('resize', handleResize);
});

function setBtnRef(el, i) {
    if (el) btnEls.value[i] = el;
}
</script>

<template>
    <div ref="wrapEl" class="tg-wrap"
        @mousedown="onDown"
        @touchstart="onDown">
        <div ref="pillEl" class="tg-pill"></div>
        <button
            v-for="(opt, i) in options"
            :key="String(opt.value)"
            type="button"
            :ref="el => setBtnRef(el, i)"
            :class="['tg-btn', opt.value === modelValue ? 'is-on' : '']"
            @click="onBtnClick(i)"
        >{{ opt.label }}</button>
    </div>
</template>

<style scoped>
.tg-wrap {
    display: inline-flex;
    align-self: flex-start;
    position: relative;
    background: #cdd1da;
    border-radius: 8px;
    padding: 3px;
    box-shadow:
        inset 0 1px 3px rgba(0,0,0,.20),
        inset 0 0 0 1px rgba(0,0,0,.07);
    user-select: none;
    touch-action: pan-y;
}

.tg-pill {
    position: absolute;
    left: 0;
    top: 3px;
    height: calc(100% - 6px);
    background: var(--surface);
    border-radius: 6px;
    pointer-events: none;
    z-index: 0;
    box-shadow: 0 1px 4px rgba(0,0,0,.18), 0 0 0 .5px rgba(0,0,0,.06);
    will-change: transform;
}

.tg-btn {
    position: relative;
    z-index: 1;
    height: 26px;
    padding: 0 12px;
    border: none;
    background: none;
    font-size: 12px;
    font-weight: 500;
    color: var(--ink-3);
    font-family: var(--font);
    white-space: nowrap;
    text-align: center;
    border-radius: 6px;
    cursor: grab;
    transition: color .15s;
    -webkit-tap-highlight-color: transparent;
}
.tg-btn.is-on { color: var(--ink); font-weight: 600; }
.tg-wrap.dragging .tg-btn { cursor: grabbing; }
</style>