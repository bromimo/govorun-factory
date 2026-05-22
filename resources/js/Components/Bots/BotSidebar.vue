<script setup>
import { Settings, MessageSquare, ShieldCheck, Route, Workflow, Image, Plug } from 'lucide-vue-next';

const props = defineProps({
    bot: Object,
    activeKey: String,
    onTabChange: Function,
});

const groups = [
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
            { key: 'connections', label: 'Подключения', icon: Plug, count: () => props.bot.connections?.length ?? 0 },
        ],
    },
];
</script>

<template>
    <div v-for="group in groups" :key="group.label" class="side-group">
        <div class="side-grp-h">{{ group.label }}</div>
        <template v-for="item in group.items" :key="item.key">
            <button
                class="side-item"
                :class="{ act: activeKey === item.key }"
                @click="onTabChange(item.key)"
            >
                <component :is="item.icon" :size="13" class="side-icon" />
                {{ item.label }}
                <span v-if="item.count && item.count()" class="side-cnt">{{ item.count() }}</span>
            </button>
        </template>
    </div>
</template>

<style scoped>
.side-group { padding: 0; }
.side-grp-h {
    padding: 6px 10px 5px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--ink-3);
    background: linear-gradient(180deg, #f4f6f8 0%, #e8ecf0 100%);
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
</style>
