# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Админка доступна по адресу http://govorun-factory

## Commands

```bash
# Development (runs PHP server, queue, logs, Vite concurrently)
composer run dev

# Or separately
php artisan serve
npm run dev

# Build frontend for production
npm run build

# Run all tests (Pest/PHPUnit, SQLite in-memory)
php artisan test

# Run single test file
php artisan test tests/Feature/BotControllerTest.php

# Run single test by name
php artisan test --filter="can create a route for a bot"

# Lint PHP
./vendor/bin/pint

# Database
php artisan migrate
php artisan db:seed

# Бэкап БД (MySQL → .backups/, хранится один актуальный файл)
php artisan db:backup
```

## Architecture

Schema-first visual bot builder: **Vue UI → JSON Schema (DB) → PHP Code Generator → ZIP Export**.

**Stack:** Laravel 13, PHP 8.3+, Vue 3 + Inertia.js, Tailwind CSS, Vue Flow, TipTap, Pest 4 / PHPUnit 12. БД: **MySQL** для dev (см. `.env`, реальные данные — не запускать `migrate:fresh`/`db:wipe`!), SQLite `:memory:` только для тестов (см. `phpunit.xml`).

### Code Generator (ядро проекта)

`App\Services\CodeGenerator\CodeGeneratorService` — оркестратор. Пошагово:
1. Копирует `resources/stubs/skeleton/` (заготовка проекта) в output-директорию.
2. Под-генераторы рендерят артефакты через Blade-шаблоны из `resources/views/stubs/`:
   - `ConfigGenerator` → `config/*.php`, `.env.example`
   - `RouteGenerator` → `routes/messenger.php`
   - `ControllerGenerator` → `app/Controllers/*.php`
   - `FlowGenerator` → `app/Flows/*.php` (step-based, см. ниже)
   - `BotProfileGenerator` → `config/bot_profile.php` + бинарь аватара (только если включён Telegram)
   - `ComposerGenerator` → `composer.json`
   - `ConnectionGenerator` → `config/connections.php` + дополняет `.env.example` переменными `CONN_<SLUG>_*`

`KeyboardCodeBuilder` — статический сериализатор inline/reply-клавиатуры из схемы в PHP-код `Govorun\Messaging\Keyboard`; используется `Controller`- и `Flow`-генераторами.

ZIP-экспорт делает `ExportService`; артефакты попадают в `storage/exports/`.
Валидация схемы бота — `App\Services\SchemaValidator` (возвращает `App\Services\ValidationResult`).

**Целевой фреймворк — `govorun/framework` (step-based Flow):**
`FlowGenerator` превращает граф нод в класс с массивом `$steps` и методами `{name}Step(Step $step)`,
где каждый шаг использует `$step->ask()` и `$step->receive()`, а `$this->nextStep()` продвигает поток.
Lifecycle-методы: `onComplete()`, `onCancel()`. См. `docs/plan-flow-generator-step-based.md`.
Локальные репозитории фреймворка — `C:\domains\govorun-framework` и `govorun-skeleton`.

`api_call`-нода генерирует `$this->http()->connection('<slug>')-><method>(...)` с маппингом ответа через `data_get()` и тремя вариантами `on_error`: `stop_flow` → `$this->cancel()`, `continue` → запись в `state.api_error`, `branch` → `$this->goTo()` по ребру с `sourceHandle=on_error`. Зависит от трейта `MakesHttpCalls` в `govorun/framework` (реализован отдельно).

### Runtime Services (не генератор)

- `TelegramHtml` — санитизация HTML до whitelist Telegram (`<b>`, `<i>`, `<a>`, `<span class="tg-spoiler">` и т.д.). Применяется при сохранении текстов reply/ask-нод.
- `MediaOptimizerService` — оптимизация загружаемых медиа под лимиты Telegram (50 МБ, JPEG quality 85, max 5000px). Возвращает tmp_path + метаданные.
- `TelegramProfileVideoConverter` — конвертация видео для аватара бота.
- `Services\Http\ConnectionRequestService` — выполняет тестовый HTTP-запрос из UI редактора подключений (`POST bot-connections/{conn}/test`, throttle:30,1). Собирает URL/auth/headers/body, рендерит `{{state.*}}` плейсхолдеры, перед отправкой проверяет URL через `SsrfGuard`, ограничивает тело ответа 1 МБ, таймаут 10 сек. Возвращает `TestRequestResult` (status, durationMs, headers, bodyRaw/bodyJson, truncated, error). DTO запроса — `RequestDraft`.
- `Services\Http\SsrfGuard` — запрещает не-http(s) схемы и резолвит DNS A/AAAA: блокирует приватные/зарезервированные IP через `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`.
- `Services\Http\TemplatePlaceholderRenderer` — подставляет `{{state.*}}` в path/headers/query/body шаблонах.

### Data Model

`User` (admin/editor/viewer) → `Bot` (config, messenger_config as JSON, cached `used_media_count`/`used_media_size`) → `BotRoute` (entry points с type/match/handler + `status: EntityStatus`) + `BotFlow` (dialog graphs as JSON nodes/edges + `status: EntityStatus` + кешированные счётчики узлов) + `BotMedia` (библиотека загруженных файлов: photo/video/audio/document/animation, хранение в `bots/{bot_id}/media/`) + `BotConnection` (переиспользуемые HTTP-подключения: slug, base_url, auth_type, auth_config encrypted:array). `Plugin` — extensible block types.

`Bot::recomputeMediaStats(int $botId)` пересчитывает `used_media_count`/`used_media_size` через `extractUsedMediaIds()` — вызывается из observer'ов после изменения flows/routes.

Observers регистрируются атрибутом `#[ObservedBy(...)]` на модели (`Bot`, `BotRoute`, `BotFlow`), не через `Model::observe()` в провайдере.

### Authorization

Role-based via `UserRole` enum + `BotPolicy`/`BotConnectionPolicy`. Admin: full access. Editor: own bots only. Viewer: read-only. Custom `RoleMiddleware` (alias `role` в `bootstrap/app.php`) для admin-only роутов (`/users`, `/plugins`).

### Entity Status (active / inactive / draft)

`EntityStatus` enum применяется и к `BotRoute`, и к `BotFlow`. Storage — `string(16)` (см. правило проекта: никаких DB enum). Бизнес-правила:

- При создании сущность всегда `draft` (см. `BotRouteController::store`, `BotFlowController::store`).
- Активация требует прохождения `SchemaValidator::validateSingleRoute()` / `validateSingleFlow()` — иначе 422.
- Активация дочернего phrase-маршрута требует активного родителя.
- Деактивация родителя (`inactive`/`draft`) каскадно переводит активных детей в `inactive`. Деактивация flow каскадно переводит ссылающиеся маршруты в `draft` и обнуляет `flow_id` (UI показывает `affected_routes_count`).
- В `PUT update` поле `status` **отрезается** через `array_diff_key(..., ['status' => true])` — статус меняется только через PATCH `.../status`. Но если после `update` сущность стала невалидной, сама падает в `draft` с `session()->flash('auto_drafted_reasons', $errors)` — фронт показывает причины тостом.
- `CodeGeneratorService::generate()` и `SchemaValidator::validate()` обрабатывают **только** `EntityStatus::Active`. `inactive`/`draft` не попадают в экспорт.
- UI: `Components/Ui/StatusBadge.vue` — интерактивный badge с popup-меню; рендерит popup через `Teleport to="body"` (иначе клипается в `overflow:hidden` контейнерах таблиц).

### Route Structure

All routes in `routes/web.php` under `auth` middleware. Корневой `/` → `BotController@index` (имя `dashboard`, рендерит `Dashboard/Index.vue`). Группы по префиксу:
- `bots/` — CRUD + загрузка/просмотр аватара (`profile-photo`)
- `bots/{bot}/routes/` — CRUD, `POST reorder`, **`PATCH {route}/status`** (`bot-routes.change-status`)
- `bots/{bot}/flows/` — CRUD, **`PATCH {flow}/status`** + **`GET {flow}/status/impact`** (`bot-flows.status-impact` — список маршрутов, падающих в draft при деактивации flow)
- `bots/{bot}/media/` — index/store/file/destroy
- `bots/{bot}/connections/` — store/update/destroy + `POST {connection}/test` throttle:30,1 (single-action `BotConnectionTestController`)
- `bots/{bot}/export`
- `profile/`
- `users/` (admin-only через `role:admin`)
- `plugins/` (admin-only)

Роуты пробрасываются во фронт через Ziggy (`tightenco/ziggy`).

### Frontend

Inertia.js SFC pages в `resources/js/Pages/` (точки входа: `Dashboard/Index.vue` — список ботов, `Bots/Edit.vue` — табы `settings`/`routes`/`flows`/`messengers`/`connections`/`validation`/`media` (имя таба читается из query `?tab=...`), `Flows/Edit.vue`, `Routes/Edit.vue`). Reusable components — в `resources/js/Components/`. Alias `@/` → `resources/js/` (см. `jsconfig.json`). Key areas:
- `Ui/` — собственная design system с префиксом `Er*` (ErButton, ErBadge, ErDrawer, ErFormField, ErTable, ErTabs, ErInput, ErSelect, ErToast, ErTooltip, ErStepper, ErEmpty, ErCounterCard, ErAlert, ErTextarea), плюс `StatusBadge`, `ConfirmModal`, `ToggleGroup`. **Использовать эти компоненты вместо сырых HTML-инпутов.**
- `Blocks/` — shared block form components (используются и в route-editor, и во flow-editor). Диспетчер форм — `BlockFormResolver.vue` (по типу узла рендерит нужную форму).
- `Routes/` — route list, editor drawer, block list.
- `Flows/` — Vue Flow canvas (`FlowCanvas.vue`), node palette, properties panels, custom nodes в `nodes/`. Drag-and-drop из палитры на канвас — composable `useFlowDragDrop`. Кастомные edges — `edges/EditableEdge.vue`.
- `Bots/MediaLibrary.vue` + `Blocks/MediaLibraryModal.vue` + `Blocks/MediaPicker.vue` — библиотека медиа и выбор файлов в формах reply/ask с media.
- `Bots/ConnectionsTab.vue` + `Connections/ConnectionDrawer.vue` — переиспользуемые HTTP-подключения бота (вкладка `connections` в `Bots/Edit.vue`).
- `Blocks/ApiCall/` — форма ноды api_call: `RequestSection.vue` (подключение/метод/путь/заголовки/тело), `ResponsePicker.vue` (визуальный JSON-tree picker маппинга ответа), `ErrorBehavior.vue` (stop_flow/continue/branch), `JsonTree.vue`, `ResponseMappingList.vue`, `StateSampleEditor.vue`.
- `composables/useToast.js`, `composables/useDirtyGuard.js` — общие helpers; `utils/` — `debounce`, `translit`, `useClickOutside`, `useFloatingPosition`, `telegramHtml`, `systemVars`, `insertAtCursor`.

**TipTap** используется как rich text editor для текстов нод/сообщений (`Blocks/RichTextEditor.vue` + `FormattingToolbar.vue` + `InsertToolbar.vue`); результат — Telegram-HTML, проходящий через `TelegramHtml::sanitize()`.

### Flow Editor

Vue Flow canvas with 10 логических типов узлов (ask_text, ask_keyboard, reply_text, reply_keyboard, reply_media, save_state, condition, api_call, on_complete, on_cancel). Vue-компоненты унифицированы: `AskNode` покрывает оба `ask_*`, `ReplyNode` — все `reply_*` (физических .vue ≈ 8 на 10 типов). Drag-and-drop from palette. Selected state managed inside `FlowCanvas.vue` and exposed via `defineExpose`. Node/edge properties edited in right panel.

### Validation

Runtime input validation on `ask_text`/`ask_keyboard` nodes. Rules stored in `node.data.validation` as array of `{ name, params?, message? }` (Laravel-style: required, email, numeric, min, max, regex, etc.). Default error messages configurable per bot via `bot.config.validation_messages`. Rule definitions and defaults in `Blocks/validationRules.js`. UI component: `Blocks/ValidationEditor.vue`. Shield icon on canvas nodes with active validation.

Дефолтные тексты — в `resources/validation-messages.json` (плоский `{ruleName: template}` с именованными плейсчолдерами `{value}`, `{min}`, `{max}`). **Синхронизированная копия с `govorun-framework/resources/validation-messages.json`** — при изменении править обе. UI-форма (`Bots/ValidationMessagesForm.vue`) умеет Export/Import полного слепка JSON.

### Enums

`UserRole` (admin/editor/viewer), `RouteType` (command/phrase/pattern/action/event/media/location/contact/referral/fallback), `HandlerType` (controller/flow), `ConnectionAuthType` (none/api_key/bearer/basic), `EntityStatus` (active/inactive/draft). All backed enums в PHP, в БД хранятся как строки — **никогда не использовать `enum` column type в миграциях** (`string(16)` + `default('...')`).

## Git Workflow

- Feature-ветка создаётся от `develop` (`git checkout develop && git pull && git checkout -b feat/...`).
- После завершения реализации: пушить ветку и создавать PR в `develop` без вопросов — не спрашивать, не предлагать альтернативы.
- PR создаётся через URL (не через `gh`): `https://github.com/bromimo/govorun-factory/compare/develop...<branch>` — выводить ссылку + title + body для копирования.

## Code Style

- PHPDocs на русском для всех классов: описание на первой строке сразу после `/**`. Теги: `@param`, `@return`, `@throws`.
- `use`-импорты по возрастанию длины строки.
- Vue Flow custom nodes must have `defineOptions({ inheritAttrs: false })` to prevent slot prop leaking.
