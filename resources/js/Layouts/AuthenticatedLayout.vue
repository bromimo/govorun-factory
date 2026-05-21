<script setup>
import { Link, router } from '@inertiajs/vue3'
import { Bot, Users, Puzzle, Bell, Search, Settings, Menu } from 'lucide-vue-next'
import { computed, ref, onMounted } from 'vue'
import ErToast from '@/Components/Ui/ErToast.vue'

const props = defineProps({
    title: String,
    subtitle: String,
    flush: Boolean,
})

const user = computed(() => window.$page?.props?.auth?.user)

const navOpen = ref(true)

onMounted(() => {
    const saved = localStorage.getItem('er-nav-open')
    if (saved !== null) {
        navOpen.value = saved !== 'false'
    }
})

function toggleNav() {
    navOpen.value = !navOpen.value
    localStorage.setItem('er-nav-open', navOpen.value)
}
</script>

<template>
    <div class="er-app">
        <nav class="er-leftnav" :class="{ collapsed: !navOpen }">
            <div class="er-leftnav-inner">
                <div class="er-leftnav-brand">
                    <div class="er-brand-logo">Г</div>
                    <span class="er-brand-name">Говорун</span>
                </div>

                <div class="er-leftnav-sec">Разделы</div>

                <Link
                    :href="route('dashboard')"
                    class="er-leftnav-item"
                    :class="{ act: route().current('dashboard') || route().current('bots.*') }"
                >
                    <span class="er-leftnav-icon"><Bot :size="14" /></span>
                    Боты
                </Link>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('users.index')"
                    class="er-leftnav-item"
                    :class="{ act: route().current('users.*') }"
                >
                    <span class="er-leftnav-icon"><Users :size="14" /></span>
                    Пользователи
                </Link>
                <Link
                    v-if="$page.props.auth.user.role === 'admin'"
                    :href="route('plugins.index')"
                    class="er-leftnav-item"
                    :class="{ act: route().current('plugins.*') }"
                >
                    <span class="er-leftnav-icon"><Puzzle :size="14" /></span>
                    Плагины
                </Link>

                <div class="er-leftnav-sep"></div>

                <div class="er-leftnav-user">
                    <div class="er-leftnav-av">
                        {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div class="er-leftnav-uinfo">
                        <div class="er-leftnav-uname">{{ $page.props.auth.user.name }}</div>
                        <div class="er-leftnav-urole">{{ $page.props.auth.user.role }}</div>
                    </div>
                    <button class="er-leftnav-logout" type="button" title="Выйти" @click="router.post(route('logout'))">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>

        <div class="er-main">
            <div class="er-utilbar">
                <button class="er-utilbtn-icon" type="button" :title="navOpen ? 'Скрыть меню' : 'Показать меню'" @click="toggleNav">
                    <Menu :size="14" />
                </button>
                <div v-if="false" class="er-utilbar-search">
                    <!-- TODO: реализовать поиск (см. docs/audit) -->
                    <Search :size="12" style="flex-shrink:0;color:var(--ink-4)" />
                    <span>Поиск...</span>
                </div>
                <div style="flex:1"></div>
                <button v-if="false" class="er-utilbtn-icon" type="button" title="Уведомления">
                    <!-- TODO: реализовать уведомления -->
                    <Bell :size="14" />
                </button>
                <button v-if="false" class="er-utilbtn-icon" type="button" title="Настройки">
                    <!-- TODO: реализовать настройки -->
                    <Settings :size="14" />
                </button>
            </div>

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
        <ErToast />
    </div>
</template>

<style scoped>
.er-app {
    display: flex;
    flex-direction: row;
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

/* ── Left nav ── */
.er-leftnav {
    width: 208px;
    background: linear-gradient(180deg, #264a85 0%, #1d3a6e 60%, #162d57 100%);
    border-right: 1px solid #0c2050;
    flex-shrink: 0;
    color: var(--nav-text);
    box-shadow: 2px 0 4px rgba(0,0,0,.10);
    overflow: hidden;
    transition: width .22s ease, border-right-color .22s ease;
}
.er-leftnav.collapsed {
    width: 0;
    border-right-color: transparent;
}
.er-leftnav-inner {
    width: 208px;
    min-width: 208px;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    overflow-x: hidden;
}
.er-leftnav-brand {
    padding: 10px 14px;
    border-bottom: 1px solid rgba(255,255,255,.10);
    display: flex;
    align-items: center;
    gap: 9px;
    flex-shrink: 0;
    background: rgba(0,0,0,.15);
}
.er-brand-logo {
    width: 26px;
    height: 26px;
    border-radius: 3px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1d3a6e;
    font-weight: 700;
    font-size: 11px;
    flex-shrink: 0;
}
.er-brand-name { font-size: 13px; font-weight: 600; color: #fff; }

.er-leftnav-sec {
    padding: 10px 12px 4px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.42);
}
.er-leftnav-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 6px 12px;
    color: var(--nav-text);
    text-decoration: none;
    cursor: pointer;
    font-weight: 500;
    font-size: 12px;
    transition: background .1s;
    border-left: 3px solid transparent;
}
.er-leftnav-item:hover { background: rgba(255,255,255,.06); color: #fff; }
.er-leftnav-item.act { background: rgba(0,0,0,.25); color: #fff; border-left-color: #5a8cd4; font-weight: 600; }
.er-leftnav-icon {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    opacity: .75;
    display: flex;
    align-items: center;
    justify-content: center;
}
.er-leftnav-item.act .er-leftnav-icon { opacity: 1; }

.er-leftnav-sep {
    height: 1px;
    background: rgba(255,255,255,.10);
    margin: 6px 12px;
}

.er-leftnav-user {
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,.10);
    padding: 9px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0,0,0,.15);
    flex-shrink: 0;
}
.er-leftnav-av {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: linear-gradient(135deg, #5a8cd4, #2a5ca0);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border: 1.5px solid rgba(255,255,255,.2);
}
.er-leftnav-uinfo { flex: 1; min-width: 0; }
.er-leftnav-uname { font-size: 12px; color: #fff; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.er-leftnav-urole { font-size: 10px; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .04em; }
.er-leftnav-logout {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    cursor: pointer;
    color: rgba(255,255,255,.5);
    border-radius: 3px;
    flex-shrink: 0;
    transition: background .1s, color .1s;
}
.er-leftnav-logout:hover { background: rgba(255,255,255,.10); color: #fff; }

/* ── Main area ── */
.er-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    min-width: 0;
}

.er-utilbar {
    height: 38px;
    background: #fff;
    border-bottom: 1px solid var(--bdr);
    display: flex;
    align-items: center;
    padding: 0 14px;
    gap: 10px;
    flex-shrink: 0;
    box-shadow: 0 1px 2px rgba(20,30,50,.05);
}
.er-utilbar-search {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 10px;
    height: 26px;
    background: var(--surface-2);
    border: 1px solid var(--bdr);
    border-radius: 3px;
    color: var(--ink-3);
    font-size: 12px;
    flex: 1;
    max-width: 380px;
    cursor: default;
}
.er-utilbtn-icon {
    width: 26px;
    height: 26px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 3px;
    cursor: pointer;
    color: var(--ink-3);
    background: none;
    border: none;
    position: relative;
    transition: background .1s;
}
.er-utilbtn-icon:hover { background: var(--surface-2); }

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