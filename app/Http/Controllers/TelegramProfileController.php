<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;

/** Контроллер страницы настроек профиля Telegram-бота. */
class TelegramProfileController extends Controller
{
    /** Страница настроек профиля Telegram-бота.
     * @return Response
     */
    public function edit(Bot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load([
            'routes' => fn ($q) => $q->whereNull('parent_id')
                ->where('type', 'command')
                ->orderBy('sort_order'),
        ]);

        return Inertia::render('Bots/TelegramProfile', [
            'bot' => $bot,
            'can' => [
                'update' => request()->user()->can('update', $bot),
            ],
        ]);
    }
}