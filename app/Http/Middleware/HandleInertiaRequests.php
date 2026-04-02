<?php

namespace App\Http\Middleware;

use Inertia\Middleware;
use Illuminate\Http\Request;

/** Middleware для Inertia.js — шаблон, версия и общие пропсы. */
class HandleInertiaRequests extends Middleware
{
    /** @var string Корневой шаблон Blade. */
    protected $rootView = 'app';

    /** Текущая версия ассетов.
     * @param Request $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** Пропсы, доступные на всех страницах.
     * @param Request $request
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role->value,
                ] : null,
            ],
        ];
    }
}
