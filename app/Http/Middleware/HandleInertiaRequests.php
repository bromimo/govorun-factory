<?php

namespace App\Http\Middleware;

use App\Models\Plugin;
use Inertia\Middleware;
use Illuminate\Http\Request;

/** Middleware для Inertia.js — шаблон, версия и общие пропсы. */
class HandleInertiaRequests extends Middleware
{
    /** @var string Корневой шаблон Blade. */
    protected $rootView = 'app';

    /** Текущая версия ассетов.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /** Пропсы, доступные на всех страницах.
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
            'plugins' => fn () => Plugin::where('active', true)
                ->select('id', 'name', 'description', 'block_schema', 'vue_component')
                ->get(),
            'auto_drafted_reasons' => fn () => $request->session()->get('auto_drafted_reasons'),
        ];
    }
}
