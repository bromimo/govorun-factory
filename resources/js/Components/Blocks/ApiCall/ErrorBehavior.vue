<script setup>
const model = defineModel({ type: String, default: 'stop_flow' });

const options = [
    { value: 'stop_flow', label: 'Остановить сценарий' },
    { value: 'continue', label: 'Продолжить' },
    { value: 'branch',   label: 'Перейти по ветке' },
];
</script>

<template>
    <div class="eb-list">
        <label v-for="opt in options" :key="opt.value"
            :class="['eb-opt', model === opt.value ? 'is-on' : '']">
            <input type="radio" :value="opt.value" v-model="model" class="eb-radio" />
            <span class="eb-dot"><span class="eb-dot-inner" /></span>
            <span class="eb-text">
                <template v-if="opt.value === 'stop_flow'">Остановить сценарий</template>
                <template v-else-if="opt.value === 'continue'">
                    Продолжить (записать ошибку в <code class="eb-code">state.api_error</code>)
                </template>
                <template v-else>
                    Перейти по ветке <code class="eb-code">on_error</code>
                </template>
            </span>
        </label>
    </div>
</template>

<style scoped>
.eb-list {
    display: flex;
    flex-direction: column;
    border: 1px solid var(--bdr-d);
    border-radius: var(--r-sm);
    overflow: hidden;
}

.eb-opt {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    font-size: 12px;
    color: var(--ink-3);
    cursor: pointer;
    background: var(--surface);
    border-left: 2px solid transparent;
    transition: background .1s, color .1s, border-color .1s;
}
.eb-opt + .eb-opt { border-top: 1px solid var(--bdr-l); }
.eb-opt.is-on {
    background: var(--blue-soft);
    border-left-color: var(--blue);
    color: var(--ink);
}
.eb-opt:not(.is-on):hover { background: var(--surface-2); }

.eb-radio { display: none; }

.eb-dot {
    flex-shrink: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 1.5px solid var(--bdr-d);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color .1s, background .1s;
}
.eb-opt.is-on .eb-dot {
    border-color: var(--blue);
    background: var(--blue);
}
.eb-dot-inner {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--surface);
    opacity: 0;
    transition: opacity .1s;
}
.eb-opt.is-on .eb-dot-inner { opacity: 1; }

.eb-text { line-height: 1.4; }

.eb-code {
    font-family: var(--mono);
    font-size: 11px;
    background: var(--surface-3);
    border-radius: var(--r-sm);
    padding: 0 3px;
}
</style>