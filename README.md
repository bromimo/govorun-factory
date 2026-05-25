# govorun-factory

Веб-админка для визуального проектирования Telegram-ботов и экспорта их в виде готового PHP-проекта на базе `govorun/framework`.

> **Админка локально:** http://govorun-factory

## О проекте

Govorun Factory — это **schema-first builder**: бот целиком описывается в БД как набор JSON-схем (маршруты, графы диалогов, конфиг мессенджеров, подключения, медиа), а затем фабрика рендерит из этой схемы полноценное Laravel-приложение, которое уже умеет принимать апдейты Telegram, обрабатывать команды и вести диалоги.

```
 Vue-редактор (Inertia)           Laravel + MySQL                   PHP Code Generator                  Готовый бот
┌────────────────────┐    save     ┌──────────────────────┐         ┌──────────────────────┐         ┌────────────────────┐
│ Routes / Flows /   │ ──────────▶ │ bots, bot_routes,    │ ──────▶ │ Blade-шаблоны →      │ ──────▶ │ ZIP с composer.json│
│ Messengers / Media │             │ bot_flows, ...       │  export │ app/Controllers,     │  ZIP    │ + skeleton +       │
│ + Connections      │             │  (JSON-схемы)        │         │ app/Flows, config/*  │         │ govorun/framework  │
└────────────────────┘             └──────────────────────┘         └──────────────────────┘         └────────────────────┘
```

## Стек

- **Backend:** Laravel 13, PHP 8.3+, MySQL (dev), SQLite `:memory:` (тесты)
- **Frontend:** Vue 3 + Inertia.js, Tailwind CSS, [Vue Flow](https://vueflow.dev/) (canvas для диалогов), [TipTap](https://tiptap.dev/) (rich-text для сообщений), собственный UI-кит `Components/Ui/Er*`
- **Тесты:** Pest 4 / PHPUnit 12
- **Линтер PHP:** Laravel Pint
- **Целевой фреймворк генератора:** [`govorun/framework`](https://packagist.org/packages/govorun/framework) (локально — `C:\domains\govorun-framework`)

## Быстрый старт

```bash
# Установка
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed   # создаёт admin-пользователя

# Dev (PHP-сервер + queue + pail + Vite одновременно)
composer run dev

# Или по отдельности
php artisan serve
npm run dev
```

После сборки админка открывается на http://govorun-factory (или http://127.0.0.1:8000, если не настроен hosts). Авторизация — `admin@example.com / password` (см. `AdminSeeder`).

Прод-сборка фронта: `npm run build`.

## Архитектура

### Слои

```
app/
  Models/                Bot, BotRoute, BotFlow, BotMedia, BotConnection, Plugin, User
  Enums/                 UserRole, RouteType, HandlerType, ConnectionAuthType, EntityStatus
  Http/Controllers/      CRUD ботов, маршрутов, флоу, медиа, подключений + Export
  Observers/             пересчёт статистики и updated_by при изменениях
  Policies/              авторизация BotPolicy, BotConnectionPolicy
  Services/
    CodeGenerator/       сердце фабрики (см. ниже)
    Http/                ConnectionRequestService + SsrfGuard для тестирования подключений
    SchemaValidator      проверка схемы бота перед экспортом и при смене статусов
    TelegramHtml         санитизация HTML-разметки сообщений
    MediaOptimizerService оптимизация загружаемых медиа под лимиты Telegram
    ExportService        упаковка сгенерированного проекта в ZIP

resources/
  js/Pages/              Inertia SFC: Dashboard, Bots/Edit, Flows/Edit, Routes/Edit, ...
  js/Components/         Ui/, Blocks/, Flows/, Routes/, Bots/, Connections/, ...
  stubs/skeleton/        заготовка проекта-бота, копируется при экспорте
  views/stubs/           Blade-шаблоны для генерации PHP-кода
  validation-messages.json   дефолтные тексты ошибок валидации (синхронизирован с framework)
```

### Кодогенерация

`App\Services\CodeGenerator\CodeGeneratorService` — оркестратор. Алгоритм:

1. Копирует `resources/stubs/skeleton/` (заготовка проекта) в output-директорию.
2. Под-генераторы рендерят артефакты через Blade-шаблоны из `resources/views/stubs/`:
   - `ConfigGenerator` → `config/app.php`, `config/messenger.php`, `config/database.php`, `.env.example`
   - `RouteGenerator` → `routes/messenger.php`
   - `ControllerGenerator` → `app/Controllers/**/*.php` (с поддержкой групп для phrase-родителей)
   - `FlowGenerator` → `app/Flows/*.php` (step-based: массив `$steps` и методы `{name}Step(Step $step)`)
   - `BotProfileGenerator` → `config/bot_profile.php` + бинарь аватара
   - `ComposerGenerator` → `composer.json` с зависимостью от `govorun/framework`
   - `ConnectionGenerator` → `config/connections.php` + переменные `CONN_<SLUG>_*` в `.env.example`
3. `ExportService` упаковывает всё в ZIP в `storage/exports/`.

Только сущности со статусом `active` попадают в экспорт — `inactive`/`draft` фильтруются.

### Целевой фреймворк (govorun/framework)

Сгенерированный бот — Laravel-приложение, которое использует пакет `govorun/framework`. Контроллеры маршрутов и Flow-классы используют API фреймворка:

- **Контроллеры маршрутов** наследуют от базового контроллера фреймворка и вызывают `$this->reply(...)`, `$this->replyKeyboard(...)`, `$this->replyMedia(...)`.
- **Flow-классы** имеют массив `$steps` и методы `{name}Step(Step $step)`. Внутри шага — `$step->ask(...)` / `$step->receive()`. Переход — `$this->nextStep()` / `$this->goTo('step_name')`. Lifecycle — `onComplete()` / `onCancel()`.
- **`api_call`-нода** генерирует `$this->http()->connection('<slug>')-><method>(...)` через трейт `MakesHttpCalls`.

## Как работать с админкой

Типичный сценарий создания бота:

1. **Создать бота** на dashboard → задать имя.
2. **Настроить мессенджер** (вкладка `messengers`): включить Telegram, ввести bot-токен, опционально — display-данные (имя, описание).
3. **Загрузить медиа** (`media`) — единая библиотека на бота: фото, видео, документы, аудио, анимации.
4. **Создать переиспользуемые HTTP-подключения** (`connections`): base_url, тип auth (none/Bearer/Basic/API-key), дефолтные заголовки. Каждое подключение можно протестировать кнопкой Test (под `SsrfGuard`-ом — приватные сети заблокированы).
5. **Создать маршруты** (`routes`): команды (`/start`), фразы, паттерны, медиа, контакт, локация, фоллбэк. У каждого маршрута — `handler_type`:
   - `controller` — линейный обработчик с блоками `reply`, `save_state`, ...
   - `flow` — отсылка к диалогу (см. ниже)
6. **Спроектировать диалоги** (`flows`): открыть Flow-редактор, перетащить узлы из палитры на canvas (Vue Flow), связать рёбрами. Поддерживаемые узлы — `ask`, `reply`, `save_state`, `condition`, `api_call`, `on_complete`, `on_cancel`.
7. **Настроить тексты ошибок валидации** (`validation`) — переопределить дефолты для конкретного бота. Дефолты лежат в `resources/validation-messages.json` (синхронизирован с фреймворком).
8. **Активировать** сущности → каждая по умолчанию `draft`. Активация проходит через валидацию.
9. **Экспортировать** ZIP → задеплоить на сервер, прописать `.env`, запустить.

## Жизненный цикл сущностей (EntityStatus)

`BotRoute` и `BotFlow` имеют три статуса:

| Статус | Смысл |
|---|---|
| `draft` | Создаётся по умолчанию. Не валидируется, не экспортируется. |
| `active` | Полностью валиден, попадает в экспорт. Активация проходит через `SchemaValidator::validateSingleRoute/Flow`. |
| `inactive` | Временно отключён вручную. В экспорт не попадает, но валидность сохранена. |

Каскадные правила:
- Активация дочернего phrase-маршрута требует активного родителя.
- Деактивация родителя (`inactive`/`draft`) переводит активных детей в `inactive`.
- Деактивация flow переводит ссылающиеся маршруты в `draft` и обнуляет их `flow_id`.
- При `PUT update` поле `status` отрезается — менять статус можно только через `PATCH .../status`.
- Если после `update` сущность стала невалидной, она автоматически уходит в `draft` (фронт показывает причины тостом из `session()->flash('auto_drafted_reasons', ...)`).

UI-виджет: `Components/Ui/StatusBadge.vue` — кликабельная плашка с popup-меню.

## Роли и доступы

- **admin** — полный доступ, плюс админ-только разделы `/users` и `/plugins`.
- **editor** — может создавать ботов, видит и редактирует только свои.
- **viewer** — read-only ко всем ботам.

Авторизация — стандартные Laravel-policies (`BotPolicy`, `BotConnectionPolicy`). Кастомный `RoleMiddleware` (alias `role`) защищает админ-роуты.

## Команды

```bash
# Тесты
php artisan test                                              # все
php artisan test tests/Feature/BotControllerTest.php          # один файл
php artisan test --filter="can create a route for a bot"      # по имени

# Линтер
./vendor/bin/pint

# БД
php artisan migrate
php artisan db:seed
php artisan db:backup    # дамп MySQL → .backups/, хранится один актуальный файл
```

## Кодстайл

- **PHP** проверяется Pint. PHPDoc на русском, описание класса — на первой строке сразу после `/**`, без пустой строки. Теги: `@param`, `@return`, `@throws`.
- **`use`-импорты** в PHP — по возрастанию длины строки.
- **Vue Flow custom nodes** должны иметь `defineOptions({ inheritAttrs: false })`, иначе slot-пропсы протекут в DOM.
- В миграциях **не использовать `enum` column type** — `string(N)` + PHP backed enum.

## Структура схемы (упрощённо)

```
Bot
├── config (JSON)                  настройки бота, validation_messages
├── messenger_config (JSON)        { telegram: { token, username, ... } }
├── BotRoute[]                     entry points (commands/phrases/fallback/...)
│   ├── status (EntityStatus)
│   ├── handler_type (controller | flow)
│   ├── handler_schema (JSON)      blocks: [{ type: 'reply' | 'save_state' | ... , params }]
│   └── children (BotRoute[])      для phrase-родителей
├── BotFlow[]                      графы диалогов
│   ├── status (EntityStatus)
│   └── graph (JSON)               { nodes: [{ id, type, position, data }], edges: [...] }
├── BotMedia[]                     загруженные файлы (photo/video/audio/document/animation)
└── BotConnection[]                переиспользуемые HTTP-подключения (auth_config encrypted)
```

## Лицензия

Internal.