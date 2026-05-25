<script setup>
import { computed } from 'vue';
import ErStepper from '@/Components/Ui/ErStepper.vue';
import ErrorBehavior from './ApiCall/ErrorBehavior.vue';
import RequestSection from './ApiCall/RequestSection.vue';
import ResponsePicker from './ApiCall/ResponsePicker.vue';

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

const step1Done = computed(() => !!model.value?.url && !!model.value?.method);
const step2Done = computed(() => Array.isArray(model.value?.response_mapping) && model.value.response_mapping.length > 0);
</script>

<template>
    <div class="space-y-4">
        <section>
            <ErStepper :n="1" :total="3" title="Запрос" :state="step1Done ? 'done' : 'active'" />
            <RequestSection v-model="model" :bot-id="botId" />
        </section>

        <section>
            <ErStepper :n="2" :total="3" title="Проба и маппинг ответа" :state="step2Done ? 'done' : (step1Done ? 'active' : 'default')" />
            <ResponsePicker v-model="model" :bot-id="botId" />
        </section>

        <section>
            <ErStepper :n="3" :total="3" title="Поведение при ошибке" :state="step2Done ? 'active' : 'default'" />
            <ErrorBehavior v-model="onError" />
        </section>
    </div>
</template>