<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Bot, Users, Puzzle, Bell, LogOut, Search } from 'lucide-vue-next'

defineProps({
    title: String,
    subtitle: String,
    flush: Boolean,
})
</script>

<template>
    <div class="er-app">
        <header class="er-topbar">
            <div class="er-brand">
                <div class="er-brand-logo">Г</div>
                <span class="er-brand-name">Говорун</span>
            </div>

            <nav class="er-tnav">
                <Link
                    :href="route('dashboard')"
                    class="er-tnav-item"
                    :class="{ act: route().current('dashboard') || route().current('bots.*') }"
                >
                    <Bot :size="14" />Боты
                </Link>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('users.index')"
                    class="er-tnav-item"
                    :class="{ act: route().current('users.*') }"
                >
                    <Users :size="14" />Пользователи
                </Link>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('plugins.index')"
                    class="er-tnav-item"
                    :class="{ act: route().current('plugins.*') }"
                >
                    <Puzzle :size="14" />Плагины
                </Link>
            </nav>

            <div class="er-tsp"></div>

            <div class="er-search-top">
                <Search :size="12" /><span>Поиск...</span>
            </div>

            <div class="er-tright">
                <button class="er-tib" type="button" title="Уведомления">
                    <Bell :size="14" />
                </button>
                <div class="er-user-block">
                    <div>
                        <div class="er-user-name">{{ $page.props.auth.user.name }}</div>
                        <div class="er-user-role">{{ $page.props.auth.user.role }}</div>
                    </div>
                    <button class="er-tib" type="button" title="Выйти" @click="router.post(route('logout'))">
                        <LogOut :size="14" />
                    </button>
                </div>
            </div>
        </header>

        <div v-if="$slots.subbar" class="er-subbar">
            <slot name="subbar" />
        </div>

        <div class="er-body">
            <aside v-if="$slots.sidebar" class="er-side">
                <slot name="sidebar" />
            </aside>

            <main class="er-ctt" :class="{ flush }">
                <nav v-if="$slots.breadcrumbs" class="er-bcr">
                    <slot name="breadcrumbs" />
                </nav>
                <div v-if="title || $slots.actions" class="er-ph">
                    <div>
                        <h1 class="er-ph-t">{{ title }}</h1>
                        <p v-if="subtitle" class="er-ph-s">{{ subtitle }}</p>
                    </div>
                    <div v-if="$slots.actions" class="er-ph-a">
                        <slot name="actions" />
                    </div>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
.er-app {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100vh;
    font-family: var(--font);
    font-size: 12px;
    line-height: 1.4;
    color: var(--ink);
    background: var(--bg);
    -webkit-font-smoothing: antialiased;
}
.er-app * { box-sizing: border-box; }

.er-topbar {
    height: 36px;
    background: linear-gradient(180deg, #264a85 0%, #1d3a6e 100%);
    border-bottom: 1px solid #0c2050;
    display: flex;
    align-items: stretch;
    flex-shrink: 0;
    color: var(--nav-text);
    font-size: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,.15);
}
.er-brand {
    padding: 0 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-right: 1px solid rgba(255,255,255,.1);
}
.er-brand-logo {
    width: 22px;
    height: 22px;
    border-radius: 3px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1d3a6e;
    font-weight: 700;
    font-size: 11px;
}
.er-brand-name { font-weight: 600; color: #fff; font-size: 13px; }
.er-tnav { display: flex; align-items: stretch; }
.er-tnav-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px;
    color: var(--nav-text);
    text-decoration: none;
    border-right: 1px solid rgba(255,255,255,.06);
    transition: background .1s;
    font-weight: 500;
    font-size: 12px;
    border-bottom: 2px solid transparent;
}
.er-tnav-item:hover { background: rgba(255,255,255,.06); color: #fff; }
.er-tnav-item.act { background: rgba(0,0,0,.20); color: #fff; border-bottom-color: #5a8cd4; }
.er-tsp { flex: 1; }
.er-search-top {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 10px;
    height: 24px;
    margin: 6px 10px;
    background: rgba(0,0,0,.2);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 3px;
    color: var(--nav-text);
    font-size: 11px;
    width: 220px;
    cursor: default;
}
.er-tright { display: flex; align-items: center; padding-right: 8px; }
.er-tib {
    padding: 0 10px;
    height: 36px;
    display: flex;
    align-items: center;
    cursor: pointer;
    color: var(--nav-text);
    background: none;
    border: none;
    transition: background .1s;
}
.er-tib:hover { background: rgba(255,255,255,.08); color: #fff; }
.er-user-block {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 10px;
    border-left: 1px solid rgba(255,255,255,.08);
    height: 100%;
}
.er-user-name { color: #fff; font-weight: 500; font-size: 12px; }
.er-user-role { color: var(--nav-text); font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }

.er-subbar {
    height: 32px;
    background: linear-gradient(180deg, #fff 0%, #eef1f4 100%);
    border-bottom: 1px solid var(--bdr);
    display: flex;
    align-items: stretch;
    padding: 0 12px;
    flex-shrink: 0;
}

.er-body { flex: 1; display: flex; overflow: hidden; }
.er-side {
    width: 220px;
    background: var(--surface);
    border-right: 1px solid var(--bdr);
    overflow-y: auto;
    flex-shrink: 0;
    padding: 6px 0;
}
.er-ctt { flex: 1; overflow-y: auto; padding: 14px 18px; background: var(--bg); }
.er-ctt.flush { padding: 0; overflow: hidden; display: flex; flex-direction: column; }

.er-bcr { display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--ink-3); margin-bottom: 8px; }
.er-bcr :deep(a) { color: var(--blue); text-decoration: none; }
.er-bcr :deep(a:hover) { text-decoration: underline; }
.er-bcr :deep(.sep) { color: var(--bdr-d); }

.er-ph { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--bdr-l); gap: 16px; }
.er-ph-t { font-size: 18px; font-weight: 600; letter-spacing: -.01em; margin: 0; color: var(--ink); }
.er-ph-s { font-size: 11px; color: var(--ink-3); margin-top: 2px; margin-bottom: 0; }
.er-ph-a { display: flex; align-items: center; gap: 6px; }
</style>
