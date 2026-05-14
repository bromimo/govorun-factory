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

**Stack:** Laravel 13, PHP 8.3+, Vue 3 + Inertia.js, Tailwind CSS, Vue Flow, Pest, SQLite (dev/test).

### Code Generator (ядро проекта)

`App\Services\CodeGenerator\CodeGeneratorService` — оркестратор. Пошагово:
1. Копирует `resources/stubs/skeleton/` (заготовка проекта) в output-директорию.
2. Под-генераторы рендерят артефакты через Blade-шаблоны из `resources/views/stubs/`:
   - `ConfigGenerator` → `config/*.php`, `.env.example`
   - `RouteGenerator` → `routes/messenger.php`
   - `ControllerGenerator` → `app/Controllers/*.php`
   - `FlowGenerator` → `app/Flows/*.php` (step-based, см. ниже)
   - `ComposerGenerator` → `composer.json`

ZIP-экспорт делает `ExportService`; артефакты попадают в `storage/exports/`.
Валидация схемы бота — `App\Services\SchemaValidator` (возвращает `App\Services\ValidationResult`).

**Целевой фреймворк — `govorun/framework` (step-based Flow):**
`FlowGenerator` превращает граф нод в класс с массивом `$steps` и методами `{name}Step(Step $step)`,
где каждый шаг использует `$step->ask()` и `$step->receive()`, а `$this->nextStep()` продвигает поток.
Lifecycle-методы: `onComplete()`, `onCancel()`. См. `docs/plan-flow-generator-step-based.md`.
Локальные репозитории фреймворка — `C:\domains\govorun-framework` и `govorun-skeleton`.

### Data Model

`User` (admin/editor/viewer) → `Bot` (config, messenger_config as JSON) → `BotRoute` (entry points with type/match/handler) + `BotFlow` (dialog graphs stored as JSON with nodes/edges). `Plugin` — extensible block types.

### Authorization

Role-based via `UserRole` enum + `BotPolicy`. Admin: full access. Editor: own bots only. Viewer: read-only. Custom `RoleMiddleware` for admin-only routes (/users).

### Route Structure

All routes in `routes/web.php` under `auth` middleware, grouped by prefix: `bots/`, `bots/{bot}/routes/`, `bots/{bot}/flows/`, `bots/{bot}/export`, `profile/`, `users/` (admin-only), `plugins/` (admin-only).
Роуты пробрасываются во фронт через Ziggy (`tightenco/ziggy`).

### Frontend

Inertia.js SFC pages in `resources/js/Pages/`. Reusable components in `resources/js/Components/`. Alias `@/` → `resources/js/` (см. `jsconfig.json`). Key areas:
- `Blocks/` — shared block form components (used in both route editor and flow editor)
- `Routes/` — route list, editor drawer, block list
- `Flows/` — Vue Flow canvas, node palette, properties panels, custom nodes in `nodes/`

### Flow Editor

Vue Flow canvas with 10 custom node types (ask_text, ask_keyboard, reply_text, reply_keyboard, reply_media, save_state, condition, api_call, on_complete, on_cancel). Drag-and-drop from palette. Selected state managed inside `FlowCanvas.vue` and exposed via `defineExpose`. Node/edge properties edited in right panel.

### Validation

Runtime input validation on `ask_text`/`ask_keyboard` nodes. Rules stored in `node.data.validation` as array of `{ name, params?, message? }` (Laravel-style: required, email, numeric, min, max, regex, etc.). Default error messages configurable per bot via `bot.config.validation_messages`. Rule definitions and defaults in `Blocks/validationRules.js`. UI component: `Blocks/ValidationEditor.vue`. Shield icon on canvas nodes with active validation.

Дефолтные тексты — в `resources/validation-messages.json` (плоский `{ruleName: template}` с именованными плейсчолдерами `{value}`, `{min}`, `{max}`). **Синхронизированная копия с `govorun-framework/resources/validation-messages.json`** — при изменении править обе. UI-форма (`Bots/ValidationMessagesForm.vue`) умеет Export/Import полного слепка JSON.

### Enums

`UserRole` (admin/editor/viewer), `RouteType` (command/phrase/pattern/action/event/media/location/contact/referral/fallback), `HandlerType` (controller/flow). All backed enums in PHP, stored as strings in DB — never use `enum` column type in migrations.

## Code Style

- PHPDocs на русском для всех классов: описание на первой строке сразу после `/**`. Теги: `@param`, `@return`, `@throws`.
- `use`-импорты по возрастанию длины строки.
- Vue Flow custom nodes must have `defineOptions({ inheritAttrs: false })` to prevent slot prop leaking.
