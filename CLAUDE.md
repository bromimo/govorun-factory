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
```

## Architecture

Schema-first visual bot builder: **Vue UI → JSON Schema (DB) → PHP Code Generator → ZIP Export**.

**Stack:** Laravel 13, PHP 8.3+, Vue 3 + Inertia.js, Tailwind CSS, Vue Flow, TipTap, Pest 4 / PHPUnit 12, SQLite (dev/test).

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

### Data Model

`User` (admin/editor/viewer) → `Bot` (config, messenger_config as JSON) → `BotRoute` (entry points with type/match/handler) + `BotFlow` (dialog graphs stored as JSON with nodes/edges) + `BotMedia` (библиотека загруженных файлов: photo/video/audio/document/animation, хранение в `bots/{bot_id}/media/`) + `BotConnection` (переиспользуемые HTTP-подключения: slug, base_url, auth_type, auth_config encrypted:array). `Plugin` — extensible block types.

Observers регистрируются атрибутом `#[ObservedBy(...)]` на модели (`Bot`, `BotRoute`, `BotFlow`), не через `Model::observe()` в провайдере.

### Authorization

Role-based via `UserRole` enum + `BotPolicy`. Admin: full access. Editor: own bots only. Viewer: read-only. Custom `RoleMiddleware` for admin-only routes (/users).

### Route Structure

All routes in `routes/web.php` under `auth` middleware, grouped by prefix: `bots/`, `bots/{bot}/routes/`, `bots/{bot}/flows/`, `bots/{bot}/media/`, `bots/{bot}/profile-photo/`, `bots/{bot}/connections/` (index/store/update/destroy + POST `{connection}/test` throttle:30,1), `bots/{bot}/export`, `profile/`, `users/` (admin-only), `plugins/` (admin-only).
Роуты пробрасываются во фронт через Ziggy (`tightenco/ziggy`).

### Frontend

Inertia.js SFC pages in `resources/js/Pages/` (точки входа: `Bots/Edit.vue`, `Bots/Media/Index.vue`, `Flows/Edit.vue`). Reusable components in `resources/js/Components/`. Alias `@/` → `resources/js/` (см. `jsconfig.json`). Key areas:
- `Blocks/` — shared block form components (used in both route editor and flow editor). Диспетчер форм — `BlockFormResolver.vue` (по типу узла рендерит нужную форму).
- `Routes/` — route list, editor drawer, block list
- `Flows/` — Vue Flow canvas, node palette, properties panels, custom nodes in `nodes/`. Drag-and-drop из палитры на канвас — composable `useFlowDragDrop`.
- `Bots/MediaLibrary.vue` + `Blocks/MediaLibraryModal.vue` + `Blocks/MediaPicker.vue` — библиотека медиа и выбор файлов в формах reply_media.
- `Bots/Connections/Index.vue` — страница переиспользуемых HTTP-подключений бота (таблица + drawer `Connections/ConnectionDrawer.vue`). AJAX JSON-ответ через `wantsJson()` для загрузки списка в форме ноды.
- `Blocks/ApiCall/` — форма ноды api_call: `RequestSection.vue` (подключение/метод/путь/заголовки/тело), `ResponsePicker.vue` (визуальный JSON-tree picker маппинга ответа), `ErrorBehavior.vue` (stop_flow/continue/branch), `JsonTree.vue`, `ResponseMappingList.vue`, `StateSampleEditor.vue`.

**TipTap** используется как rich text editor для текстов нод/сообщений (`Blocks/RichTextEditor.vue` + `FormattingToolbar.vue` + `InsertToolbar.vue`); результат — Telegram-HTML, проходящий через `TelegramHtml::sanitize()`.

### Flow Editor

Vue Flow canvas with 10 логических типов узлов (ask_text, ask_keyboard, reply_text, reply_keyboard, reply_media, save_state, condition, api_call, on_complete, on_cancel). Vue-компоненты унифицированы: `AskNode` покрывает оба `ask_*`, `ReplyNode` — все `reply_*` (физических .vue ≈ 8 на 10 типов). Drag-and-drop from palette. Selected state managed inside `FlowCanvas.vue` and exposed via `defineExpose`. Node/edge properties edited in right panel.

### Validation

Runtime input validation on `ask_text`/`ask_keyboard` nodes. Rules stored in `node.data.validation` as array of `{ name, params?, message? }` (Laravel-style: required, email, numeric, min, max, regex, etc.). Default error messages configurable per bot via `bot.config.validation_messages`. Rule definitions and defaults in `Blocks/validationRules.js`. UI component: `Blocks/ValidationEditor.vue`. Shield icon on canvas nodes with active validation.

Дефолтные тексты — в `resources/validation-messages.json` (плоский `{ruleName: template}` с именованными плейсчолдерами `{value}`, `{min}`, `{max}`). **Синхронизированная копия с `govorun-framework/resources/validation-messages.json`** — при изменении править обе. UI-форма (`Bots/ValidationMessagesForm.vue`) умеет Export/Import полного слепка JSON.

### Enums

`UserRole` (admin/editor/viewer), `RouteType` (command/phrase/pattern/action/event/media/location/contact/referral/fallback), `HandlerType` (controller/flow), `ConnectionAuthType` (none/api_key/bearer/basic). All backed enums in PHP, stored as strings in DB — never use `enum` column type in migrations.

## Code Style

- PHPDocs на русском для всех классов: описание на первой строке сразу после `/**`. Теги: `@param`, `@return`, `@throws`.
- `use`-импорты по возрастанию длины строки.
- Vue Flow custom nodes must have `defineOptions({ inheritAttrs: false })` to prevent slot prop leaking.
