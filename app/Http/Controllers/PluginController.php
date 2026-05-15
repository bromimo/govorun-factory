<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Plugin;
use App\Http\Requests\StorePluginRequest;

/** Контроллер управления плагинами (только для администраторов). */
class PluginController extends Controller
{
    /** Список плагинов. */
    public function index()
    {
        return Inertia::render('Plugins/Index', [
            'plugins' => Plugin::orderBy('name')->get(),
        ]);
    }

    /** Создать новый плагин.
     */
    public function store(StorePluginRequest $request)
    {
        Plugin::create($request->validated());

        return redirect()->route('plugins.index');
    }

    /** Обновить плагин.
     */
    public function update(Plugin $plugin)
    {
        $plugin->update(request()->only('active', 'description', 'block_schema', 'vue_component', 'php_stub'));

        return redirect()->route('plugins.index');
    }

    /** Удалить плагин.
     */
    public function destroy(Plugin $plugin)
    {
        $plugin->delete();

        return redirect()->route('plugins.index');
    }
}
