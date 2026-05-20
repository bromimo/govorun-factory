<script setup>
import { computed } from 'vue';
import RequestSection from './ApiCall/RequestSection.vue';
import ResponsePicker from './ApiCall/ResponsePicker.vue';
import ErrorBehavior from './ApiCall/ErrorBehavior.vue';

const model = defineModel({ type: Object });
const props = defineProps({
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botId: { type: [Number, String], default: null },
});

const onError = computed({
    get: () => model.value?.on_error ?? 'stop_flow',
    set: (val) => { model.value = { ...model.value, on_error: val }; },
});
</script>

<template>
    <div class="space-y-4">
        <section>
            <h4 class="sec-h">Запрос</h4>
            <RequestSection v-model="model" :bot-id="botId" />
        </section>

        <section>
            <h4 class="sec-h">Проба и маппинг ответа</h4>
            <ResponsePicker v-model="model" :bot-id="botId" />
        </section>

        <section>
            <h4 class="sec-h">Поведение при ошибке</h4>
            <ErrorBehavior v-model="onError" />
        </section>
    </div>
</template>

<style scoped>
.sec-h {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--ink-3);
    margin-bottom: 8px;
}
</style>