<script setup>
import { computed } from 'vue';
import ReplyForm from './ReplyForm.vue';
import AskForm from './AskForm.vue';
import SaveStateForm from './SaveStateForm.vue';
import ConditionForm from './ConditionForm.vue';
import ApiCallForm from './ApiCallForm.vue';

const model = defineModel({ type: Object });
const props = defineProps({
    type: String,
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    possiblyDeclaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const formComponent = computed(() => ({
    reply: ReplyForm,
    ask: AskForm,
    save_state: SaveStateForm,
    condition: ConditionForm,
    api_call: ApiCallForm,
})[props.type] ?? null);
</script>

<template>
    <component v-if="formComponent" :is="formComponent" v-model="model"
        :all-state-keys="allStateKeys" :declared-state-keys="declaredStateKeys"
        :possibly-declared-state-keys="possiblyDeclaredStateKeys"
        v-bind="type === 'ask' ? { 'bot-validation-messages': botValidationMessages } : {}" />
    <p v-else class="text-xs text-gray-400">Нет параметров для этого типа</p>
</template>