<script setup>
import { computed } from 'vue';
import ReplyTextForm from './ReplyTextForm.vue';
import ReplyKeyboardForm from './ReplyKeyboardForm.vue';
import SaveStateForm from './SaveStateForm.vue';
import AskTextForm from './AskTextForm.vue';
import AskKeyboardForm from './AskKeyboardForm.vue';
import ConditionForm from './ConditionForm.vue';
import ReplyMediaForm from './ReplyMediaForm.vue';
import ApiCallForm from './ApiCallForm.vue';

const model = defineModel({ type: Object });
const props = defineProps({
    type: String,
    allStateKeys: { type: Array, default: () => [] },
    declaredStateKeys: { type: Array, default: () => [] },
    botValidationMessages: { type: Object, default: () => ({}) },
});

const formComponent = computed(() => ({
    reply_text: ReplyTextForm,
    reply_keyboard: ReplyKeyboardForm,
    reply_media: ReplyMediaForm,
    save_state: SaveStateForm,
    ask_text: AskTextForm,
    ask_keyboard: AskKeyboardForm,
    condition: ConditionForm,
    api_call: ApiCallForm,
})[props.type] ?? null);
</script>

<template>
    <component v-if="formComponent" :is="formComponent" v-model="model"
        :all-state-keys="allStateKeys" :declared-state-keys="declaredStateKeys"
        :bot-validation-messages="botValidationMessages" />
    <p v-else class="text-xs text-gray-400">Нет параметров для этого типа</p>
</template>
