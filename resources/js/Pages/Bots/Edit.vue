<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ErButton from '@/Components/Ui/ErButton.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Modal from '@/Components/Modal.vue'
import SettingsForm from '@/Components/Bots/SettingsForm.vue'
import MessengerConfigForm from '@/Components/Bots/MessengerConfigForm.vue'
import RouteList from '@/Components/Routes/RouteList.vue'
import FlowList from '@/Components/Flows/FlowList.vue'
import ValidationMessagesForm from '@/Components/Bots/ValidationMessagesForm.vue'
import MediaLibrary from '@/Components/Bots/MediaLibrary.vue'
import {
    Settings,
    Route,
    Workflow,
    ShieldCheck,
    MessageSquare,
    Image,
    Plug,
    Download,
    Trash2,
} from 'lucide-vue-next'

const props = defineProps({
    bot: Object,
    can: Object,
})

const activeTab = ref('settings')
const exportErrors = ref([])
const confirmingDeletion = ref(false)

const sidebarGroups = [
    {
        label: 'Конфигурация',
        items: [
            { key: 'settings', label: 'Основные', icon: Settings },
            { key: 'messengers', label: 'Мессенджеры', icon: MessageSquare },
            { key: 'validation', label: 'Валидация', icon: ShieldCheck },
        ],
    },
    {
        label: 'Контент',
        items: [
            { key: 'routes', label: 'Маршруты', icon: Route, count: () => props.bot.routes?.length ?? 0 },
            { key: 'flows', label: 'Flow-диалоги', icon: Workflow, count: () => props.bot.flows?.length ?? 0 },
            { key: 'media', label: 'Медиатека', icon: Image },
        ],
    },
    {
        label: 'Интеграции',
        items: [
            { key: 'connections', label: 'Подключения', icon: Plug, link: () => route('bot-connections.index', props.bot.id) },
        ],
    },
]

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
        <template #sidebar>
            <div
                v-for="group in sidebarGroups"
                :key="group.label"
                class="side-group"
            >
                <div class="side-grp-h">{{ group.label }}</div>
                <template v-for="item in group.items" :key="item.key">
                    <Link
                        v-if="item.link"
                        :href="item.link()"
                        class="side-item"
                        :class="{ act: route().current('bot-connections.*') }"
                    >
                        <component :is="item.icon" :size="13" class="side-icon" />
                        {{ item.label }}
                    </Link>
                    <button
                        v-else
                        class="side-item"
                        :class="{ act: activeTab === item.key }"
                        @click="activeTab = item.key"
                    >
                        <component :is="item.icon" :size="13" class="side-icon" />
                        {{ item.label }}
                        <span v-if="item.count" class="side-cnt">{{ item.count() }}</span>
                    </button>
                </template>
            </div>
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

        <Modal :show="confirmingDeletion" max-width="md" @close="confirmingDeletion = false">
            <div class="modal-body">
                <h2 class="modal-title">Удалить бота «{{ bot.name }}»?</h2>
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
.side-group { padding: 0; }
.side-grp-h {
    padding: 6px 12px 6px 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--ink-3);
    letter-spacing: .04em;
    background: var(--surface-2);
    border-top: 1px solid var(--bdr-l);
    border-bottom: 1px solid var(--bdr-l);
}
.side-item {
    display: flex;
    align-items: center;
    gap: 7px;
    width: 100%;
    padding: 5px 12px 5px 12px;
    color: var(--ink-2);
    cursor: pointer;
    font-size: 12px;
    border-left: 3px solid transparent;
    background: none;
    border-top: none;
    border-right: none;
    border-bottom: none;
    font-family: var(--font);
    text-align: left;
    text-decoration: none;
}
.side-item:hover { background: var(--surface-3); }
.side-item.act { background: var(--blue-soft); color: var(--blue-d); font-weight: 600; border-left-color: var(--blue); }
.side-icon { flex-shrink: 0; opacity: .65; }
.side-item.act .side-icon { opacity: 1; }
.side-cnt { font-size: 10px; color: var(--ink-4); font-family: var(--mono); margin-left: auto; }

.export-errors { background: var(--red-soft); border: 1px solid #e0a8a8; border-radius: var(--r-md); padding: 8px 12px; margin-bottom: 12px; font-size: 12px; color: var(--red); }
.modal-body { padding: 20px 24px; }
.modal-title { font-size: 15px; font-weight: 600; color: var(--ink); margin: 0 0 8px; }
.modal-desc { font-size: 12px; color: var(--ink-2); margin: 0 0 20px; }
.modal-acts { display: flex; justify-content: flex-end; gap: 8px; }
</style>