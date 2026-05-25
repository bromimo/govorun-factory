<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useDirtyGuard } from '@/composables/useDirtyGuard';
import { useToast } from '@/composables/useToast';
import { validationRuleDefs, defaultMessages } from '../Blocks/validationRules.js';
import ErButton from '@/Components/Ui/ErButton.vue';
import ErInput from '@/Components/Ui/ErInput.vue';
import ErFormSection from '@/Components/Ui/ErFormSection.vue';

const props = defineProps({
    bot: Object,
    can: Object,
});

const toast = useToast();

const fileInput = ref(null);
const importError = ref('');

const existing = props.bot.config?.validation_messages ?? {};

const form = useForm({
    config: {
        ...props.bot.config,
        validation_messages: Object.fromEntries(
            validationRuleDefs.map(r => [r.name, existing[r.name] ?? ''])
        ),
    },
});

useDirtyGuard(() => form.isDirty);

function save() {
    const messages = { ...form.config.validation_messages };
    Object.keys(messages).forEach(k => { if (!messages[k]) delete messages[k]; });

    form.transform(data => ({
        config: { ...data.config, validation_messages: Object.keys(messages).length ? messages : undefined },
    })).put(route('bots.update', props.bot.id));
}

function hasParams(ruleName) {
    return validationRuleDefs.find(r => r.name === ruleName)?.params?.length > 0;
}

function exportJson() {
    const overrides = form.config.validation_messages;
    const snapshot = Object.fromEntries(
        Object.keys(defaultMessages).map(k => [k, overrides[k]?.trim() || defaultMessages[k]])
    );
    const blob = new Blob([JSON.stringify(snapshot, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `validation-messages-${props.bot.name}.json`;
    a.click();
    URL.revokeObjectURL(url);
    toast.success('validation-messages.json скачана');
}

function triggerImport() {
    fileInput.value.click();
}

async function onFilePicked(e) {
    importError.value = '';
    const file = e.target.files[0];
    if (!file) return;

    try {
        const text = await file.text();
        const parsed = JSON.parse(text);

        if (typeof parsed !== 'object' || Array.isArray(parsed) || parsed === null) {
            throw new Error('Ожидался JSON-объект');
        }

        for (const [key, value] of Object.entries(parsed)) {
            if (typeof value !== 'string') continue;
            if (!(key in form.config.validation_messages)) continue;
            form.config.validation_messages[key] = value === defaultMessages[key] ? '' : value;
        }
    } catch (err) {
        importError.value = `Ошибка импорта: ${err.message}`;
    } finally {
        e.target.value = '';
    }
}
</script>

<template>
    <form @submit.prevent="save" class="space-y-2">
        <p class="text-xs text-gray-500 mb-3">
            Настройте тексты сообщений об ошибках валидации для этого бота.
            Пустые поля используют значения по умолчанию.
        </p>

        <ErFormSection title="Сообщения об ошибках">
            <div v-for="rule in validationRuleDefs" :key="rule.name" class="vm-row">
                <label class="vm-label">{{ rule.label }}</label>
                <div class="vm-field">
                    <ErInput
                        v-model="form.config.validation_messages[rule.name]"
                        :placeholder="defaultMessages[rule.name]"
                        :long="true"
                    />
                    <p v-if="hasParams(rule.name)" class="vm-hint">
                        Используйте {value}, {min}, {max} для подстановки параметров
                    </p>
                </div>
            </div>
        </ErFormSection>

        <div class="flex flex-wrap items-center gap-2 pt-2">
            <ErButton v-if="can.update" type="submit" variant="primary" :disabled="form.processing">
                Сохранить
            </ErButton>
            <ErButton type="button" @click="exportJson">
                Экспорт JSON
            </ErButton>
            <ErButton v-if="can.update" type="button" @click="triggerImport">
                Импорт JSON
            </ErButton>
            <input ref="fileInput" type="file" accept="application/json,.json" class="hidden" @change="onFilePicked">
            <span v-if="form.recentlySuccessful" class="text-xs text-green-600">Сохранено</span>
        </div>

        <p v-if="importError" class="text-xs text-red-600">{{ importError }}</p>
    </form>
</template>

<style scoped>
.vm-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 6px 0;
    border-bottom: 1px solid var(--bdr);
}
.vm-row:last-child { border-bottom: none; }
.vm-label {
    width: 180px;
    flex-shrink: 0;
    font-size: 12px;
    color: var(--ink);
    padding-top: 5px;
}
.vm-field { flex: 1; }
.vm-hint { font-size: 11px; color: var(--ink-3); margin-top: 2px; }
</style>
