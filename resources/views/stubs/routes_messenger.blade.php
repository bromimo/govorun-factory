@foreach($imports as $import)
use {{ $import }};
@endforeach

/**
    |--------------------------------------------------------------------------
    | Messenger Routes
    |--------------------------------------------------------------------------
    |
    | Доступные методы маршрутизации:
    |
    | Route::command('name', Action)      — команда /name
    | Route::phrase('text', Action)       — текстовая фраза (поиск вхождения)
    | Route::pattern('/regex/', Action)   — регулярное выражение
    | Route::action('name', Action)       — callback-действие (inline-кнопки)
    | Route::event('name', Action)        — событие (member_joined и т.д.)
    | Route::media('type', Action)        — медиафайл (photo, video, document)
    | Route::location(Action)             — геолокация
    | Route::contact(Action)              — контакт
    | Route::referral('code', Action)     — реферальный код
    | Route::fallback(Action)             — всё, что не совпало с другими
    |
    | Route::middleware(Class, fn)        — обернуть группу маршрутов в middleware
    | Route::phrase('text', fn)           — группа вложенных маршрутов
    |
    | Action — класс контроллера (метод handle() или __invoke())
    | RouteEntry->alias(['синоним', ...]) — алиасы для phrase-маршрутов
    |
    */

@foreach($routes as $route)
@if(!empty($route['children']))
Route::phrase('{{ $route['match'] }}', function () {
@foreach($route['children'] as $child)
@if(!empty($child['aliases']))
    Route::phrase('{{ $child['match'] }}', {{ $child['controller_class'] }}::class)->alias([{!! collect($child['aliases'])->map(fn ($a) => "'" . $a . "'")->implode(', ') !!}]);
@else
    Route::phrase('{{ $child['match'] }}', {{ $child['controller_class'] }}::class);
@endif
@endforeach
});
@elseif($route['type'] === 'fallback')
Route::fallback({{ $route['controller_class'] }}::class);
@elseif(!empty($route['aliases']))
Route::{{ $route['type'] }}({!! $route['match'] !== null ? "'" . $route['match'] . "', " : '' !!}{{ $route['controller_class'] }}::class)->alias([{!! collect($route['aliases'])->map(fn ($a) => "'" . $a . "'")->implode(', ') !!}]);
@else
Route::{{ $route['type'] }}({!! $route['match'] !== null ? "'" . $route['match'] . "', " : '' !!}{{ $route['controller_class'] }}::class);
@endif
@endforeach
