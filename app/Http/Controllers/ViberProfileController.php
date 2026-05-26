<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\UpdateViberProfileRequest;

/** Контроллер страницы настроек профиля Viber-бота. */
class ViberProfileController extends Controller
{
    /** Страница настроек профиля Viber-бота. */
    public function edit(Bot $bot): Response
    {
        $this->authorize('view', $bot);

        return Inertia::render('Bots/ViberProfile', [
            'bot' => $bot,
            'can' => [
                'update' => request()->user()->can('update', $bot),
            ],
        ]);
    }

    /** Обновить секцию messenger_config.viber.profile. */
    public function update(UpdateViberProfileRequest $request, Bot $bot): RedirectResponse
    {
        $config = $bot->messenger_config ?? [];
        $config['viber'] = $config['viber'] ?? ['enabled' => true];
        $config['viber']['profile'] = $request->validated()['profile'];

        $bot->messenger_config = $config;
        $bot->save();

        return back();
    }
}