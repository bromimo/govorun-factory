<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useToast } from '@/composables/useToast'
import Modal from '@/Components/Modal.vue'
import BotSidebar from '@/Components/Bots/BotSidebar.vue'
import SettingsForm from '@/Components/Bots/SettingsForm.vue'
import ConnectionsTab from '@/Components/Bots/ConnectionsTab.vue'
import MessengerConfigForm from '@/Components/Bots/MessengerConfigForm.vue'
import RouteList from '@/Components/Routes/RouteList.vue'
import FlowList from '@/Components/Flows/FlowList.vue'
import ValidationMessagesForm from '@/Components/Bots/ValidationMessagesForm.vue'
import MediaLibrary from '@/Components/Bots/MediaLibrary.vue'
import { Download, Trash2 } from 'lucide-vue-next'

const props = defineProps({
    bot: Object,
    connections: Array,
    can: Object,
})

const toast = useToast()

const activeTab = ref('settings')
const exportErrors = ref([])
const confirmingDeletion = ref(false)


function exportBot() {
    exportErrors.value = []
    fetch(route('bots.export', props.bot.id), { headers: { Accept: 'application/json' } })
        .then(async (response) => {
            if (response.ok) {
                const blob = await response.blob()
                const url = URL.createObjectURL(blob)
                const a = document.createElement('a')
                a.href = url
                a.download = response.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g, '') || 'bot.zip'
                a.click()
                URL.revokeObjectURL(url)
                toast.success('Бот экспортирован')
            } else {
                const data = await response.json()
                exportErrors.value = data.errors || ['Ошибка экспорта']
            }
        })
}

function deleteBot() {
    router.delete(route('bots.destroy', props.bot.id))
}
</script>

<template>
    <Head :title="bot.name" />
    <AuthenticatedLayout :title="bot.name">
        <template #breadcrumbs>
            <Link :href="route('dashboard')">Главная</Link>
            <span class="sep">›</span>
            <span>{{ bot.name }}</span>
        </template>
        <template #sidebar>
            <BotSidebar :bot="bot" :active-key="activeTab" :on-tab-change="(key) => activeTab = key" />
        </template>

        <template #actions>
            <ErButton v-if="can.export" @click="exportBot">
                <Download :size="13" />Экспорт ZIP
            </ErButton>
            <ErButton v-if="can.delete" variant="danger" @click="confirmingDeletion = true">
                <Trash2 :size="13" />Удалить
            </ErButton>
        </template>

        <div v-if="exportErrors.length" class="export-errors">
            <ul>
                <li v-for="(err, i) in exportErrors" :key="i">{{ err }}</li>
            </ul>
        </div>

        <SettingsForm v-if="activeTab === 'settings'" :bot="bot" :can="can" />
        <RouteList v-else-if="activeTab === 'routes'" :bot-id="bot.id" :routes="bot.routes ?? []" :flows="bot.flows ?? []" :can-update="can.update" />
        <FlowList v-else-if="activeTab === 'flows'" :bot-id="bot.id" :flows="bot.flows ?? []" :can-update="can.update" />
        <ValidationMessagesForm v-else-if="activeTab === 'validation'" :bot="bot" :can="can" />
        <MessengerConfigForm v-else-if="activeTab === 'messengers'" :bot="bot" :can="can" />
        <MediaLibrary v-else-if="activeTab === 'media'" :bot="bot" />
        <ConnectionsTab v-else-if="activeTab === 'connections'" :bot="bot" :connections="connections ?? []" />

        <Modal :show="confirmingDeletion" :title="`Удалить бота «${bot.name}»?`" max-width="md" @close="confirmingDeletion = false">
            <div class="modal-body">
                <p class="modal-desc">Будут удалены все маршруты, flow-диалоги и настройки. Действие необратимо.</p>
                <div class="modal-acts">
                    <ErButton @click="confirmingDeletion = false">Отмена</ErButton>
                    <ErButton variant="danger" @click="deleteBot">Удалить</ErButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.export-errors { background: var(--red-soft); border: 1px solid #e0a8a8; border-radius: var(--r-md); padding: 8px 12px; margin-bottom: 12px; font-size: 12px; color: var(--red); }
.modal-body { padding: 16px 20px 20px; }
.modal-desc { font-size: 12px; color: var(--ink-2); margin: 0 0 20px; }
.modal-acts { display: flex; justify-content: flex-end; gap: 8px; }
</style>