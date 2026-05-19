<script setup>
import { useForm } from '@inertiajs/vue3';
import ErFormSection from '@/Components/Ui/ErFormSection.vue'
import ErFormField from '@/Components/Ui/ErFormField.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import ErInput from '@/Components/Ui/ErInput.vue'
import ErTextarea from '@/Components/Ui/ErTextarea.vue'
import ErSelect from '@/Components/Ui/ErSelect.vue'

const props = defineProps({
    bot: Object,
    can: Object,
});

const form = useForm({
    name: props.bot.name,
    description: props.bot.description ?? '',
    config: {
        environment: props.bot.config?.environment ?? 'development',
        debug: props.bot.config?.debug ?? true,
        state_storage: props.bot.config?.state_storage ?? 'file',
    },
});

function save() {
    form.put(route('bots.update', props.bot.id));
}

function reset() {
    form.reset();
}
</script>

<template>
    <form @submit.prevent="save">
        <ErFormSection title="Основные настройки" :collapsible="false">
            <ErFormField label="Название" :required="true">
                <ErInput v-model="form.name" long />
                <p v-if="form.errors.name" class="fld-err">{{ form.errors.name }}</p>
            </ErFormField>

            <ErFormField label="Описание">
                <ErTextarea v-model="form.description" :rows="3" />
                <p v-if="form.errors.description" class="fld-err">{{ form.errors.description }}</p>
            </ErFormField>
        </ErFormSection>

        <ErFormSection title="Конфигурация" :collapsible="true">
            <ErFormField label="Окружение">
                <ErSelect v-model="form.config.environment">
                    <option value="development">Development</option>
                    <option value="production">Production</option>
                </ErSelect>
            </ErFormField>

            <ErFormField label="State Storage">
                <ErSelect v-model="form.config.state_storage">
                    <option value="file">File</option>
                    <option value="database">Database</option>
                    <option value="cache">Cache</option>
                </ErSelect>
            </ErFormField>

            <ErFormField label="Debug mode">
                <label class="chk-row">
                    <input v-model="form.config.debug" type="checkbox" id="debug" class="chk" />
                    <span class="chk-lbl">Включить режим отладки</span>
                </label>
            </ErFormField>
        </ErFormSection>

        <div v-if="can.update" class="form-acts">
            <ErButton variant="primary" type="submit" :disabled="form.processing">Сохранить</ErButton>
            <ErButton type="button" @click="reset">Отменить</ErButton>
            <span v-if="form.recentlySuccessful" class="saved-msg">Сохранено</span>
        </div>
    </form>
</template>

<style scoped>
.fld-err {
    font-size: 11px;
    color: var(--red);
    margin: 0;
}

.chk-row {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
}

.chk {
    width: 14px;
    height: 14px;
    cursor: pointer;
    accent-color: var(--blue);
}

.chk-lbl {
    font-size: 12px;
    color: var(--ink-2);
}

.form-acts {
    padding: 10px 0;
    display: flex;
    gap: 8px;
    border-top: 1px solid var(--bdr-l);
    margin-top: 8px;
    align-items: center;
}

.saved-msg {
    font-size: 12px;
    color: var(--green, #2e7d32);
}
</style>