<script setup>
import { Bot, Workflow, Download } from 'lucide-vue-next'
import ErToast from '@/Components/Ui/ErToast.vue'
</script>

<template>
    <div class="er-guest">
        <aside class="er-guest-side">
            <div class="er-guest-brand">
                <div class="er-brand-logo">Г</div>
                <div>
                    <div class="er-brand-name">Говорун</div>
                    <div class="er-brand-tagline">Конструктор чат-ботов</div>
                </div>
            </div>
            <p class="er-guest-desc">
                Платформа для создания и управления чат-ботами без написания кода
            </p>
            <div class="er-guest-features">
                <div class="er-feat">
                    <div class="er-feat-icon"><Bot :size="14" /></div>
                    <span>Мультиканальные боты</span>
                </div>
                <div class="er-feat">
                    <div class="er-feat-icon"><Workflow :size="14" /></div>
                    <span>Flow-конструктор диалогов</span>
                </div>
                <div class="er-feat">
                    <div class="er-feat-icon"><Download :size="14" /></div>
                    <span>Экспорт в готовый код</span>
                </div>
            </div>
            <div class="er-guest-copy">© 2026 Говорун</div>
        </aside>

        <div class="er-guest-main">
            <div class="er-guest-utilbar">
                <a href="#" class="er-util-link">Помощь</a>
                <span class="er-util-sep">·</span>
                <a href="#" class="er-util-link">Связаться с администратором</a>
            </div>
            <div class="er-guest-content">
                <slot />
            </div>
        </div>
        <ErToast />
    </div>
</template>

<style scoped>
.er-guest { display: flex; height: 100vh; font-family: var(--font); font-size: 12px; line-height: 1.4; color: var(--ink); -webkit-font-smoothing: antialiased; }
.er-guest * { box-sizing: border-box; }

.er-guest-side {
    width: 208px;
    flex-shrink: 0;
    background: linear-gradient(180deg, #264a85 0%, #1d3a6e 50%, #162d57 100%);
    color: var(--nav-text);
    padding: 24px 18px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.er-guest-brand { display: flex; align-items: center; gap: 10px; }
.er-brand-logo {
    width: 26px;
    height: 26px;
    border-radius: 4px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1d3a6e;
    font-weight: 700;
    font-size: 13px;
    flex-shrink: 0;
}
.er-brand-name { font-weight: 700; color: #fff; font-size: 14px; }
.er-brand-tagline { font-size: 10px; color: var(--nav-text); }
.er-guest-desc { font-size: 12px; color: var(--nav-text); line-height: 1.5; margin: 0; }
.er-guest-features { display: flex; flex-direction: column; gap: 10px; }
.er-feat { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #c8d4e8; }
.er-feat-icon {
    width: 26px;
    height: 26px;
    border-radius: 4px;
    background: rgba(90,140,212,.3);
    border: 1px solid rgba(90,140,212,.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ab8e4;
    flex-shrink: 0;
}
.er-guest-copy { margin-top: auto; font-size: 10px; color: rgba(200,212,232,.5); }

.er-guest-main { flex: 1; display: flex; flex-direction: column; background: var(--bg); overflow: hidden; }
.er-guest-utilbar {
    height: 38px;
    background: var(--surface);
    border-bottom: 1px solid var(--bdr);
    display: flex;
    align-items: center;
    padding: 0 20px;
    gap: 8px;
    flex-shrink: 0;
}
.er-util-link { font-size: 12px; color: var(--blue); text-decoration: none; }
.er-util-link:hover { text-decoration: underline; }
.er-util-sep { color: var(--bdr-d); }
.er-guest-content { flex: 1; display: flex; align-items: center; justify-content: center; overflow-y: auto; }
</style>
