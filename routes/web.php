<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BotController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PluginController;
use App\Http\Controllers\BotFlowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BotMediaController;
use App\Http\Controllers\BotRouteController;
use App\Http\Controllers\BotConnectionController;
use App\Http\Controllers\BotConnectionTestController;

Route::middleware('auth')->group(function () {
    Route::get('/', [BotController::class, 'index'])->name('dashboard');

    Route::prefix('bots')->group(function () {
        Route::post('', [BotController::class, 'store'])->name('bots.store');
        Route::get('{bot}/edit', [BotController::class, 'edit'])->name('bots.edit');
        Route::put('{bot}', [BotController::class, 'update'])->name('bots.update');
        Route::delete('{bot}', [BotController::class, 'destroy'])->name('bots.destroy');
        Route::post('{bot}/profile-photo', [BotController::class, 'uploadProfilePhoto'])
            ->name('bots.profile-photo.upload');
        Route::get('{bot}/profile-photo', [BotController::class, 'showProfilePhoto'])
            ->name('bots.profile-photo.show');
        Route::delete('{bot}/profile-photo', [BotController::class, 'deleteProfilePhoto'])
            ->name('bots.profile-photo.delete');

        Route::get('{bot}/media', [BotMediaController::class, 'index'])->name('bots.media.index');
        Route::post('{bot}/media', [BotMediaController::class, 'store'])->name('bots.media.store');
        Route::get('{bot}/media/{media}/file', [BotMediaController::class, 'file'])->name('bots.media.file');
        Route::delete('{bot}/media/{media}', [BotMediaController::class, 'destroy'])->name('bots.media.destroy');

        Route::prefix('{bot}/routes')->group(function () {
            Route::post('', [BotRouteController::class, 'store'])->name('bot-routes.store');
            Route::post('reorder', [BotRouteController::class, 'reorder'])->name('bot-routes.reorder');
            Route::put('{route}', [BotRouteController::class, 'update'])->name('bot-routes.update');
            Route::delete('{route}', [BotRouteController::class, 'destroy'])->name('bot-routes.destroy');
        });

        Route::get('{bot}/export', ExportController::class)->name('bots.export');

        Route::prefix('{bot}/flows')->group(function () {
            Route::post('', [BotFlowController::class, 'store'])->name('bot-flows.store');
            Route::get('{flow}', [BotFlowController::class, 'show'])->name('bot-flows.show');
            Route::put('{flow}', [BotFlowController::class, 'update'])->name('bot-flows.update');
            Route::delete('{flow}', [BotFlowController::class, 'destroy'])->name('bot-flows.destroy');
        });

        Route::prefix('{bot}/connections')->group(function () {
            Route::get('', [BotConnectionController::class, 'index'])->name('bot-connections.index');
            Route::post('', [BotConnectionController::class, 'store'])->name('bot-connections.store');
            Route::put('{connection}', [BotConnectionController::class, 'update'])->name('bot-connections.update');
            Route::delete('{connection}', [BotConnectionController::class, 'destroy'])->name('bot-connections.destroy');
            Route::post('{connection}/test', BotConnectionTestController::class)
                ->middleware('throttle:30,1')
                ->name('bot-connections.test');
        });
    });

    Route::prefix('profile')->group(function () {
        Route::get('', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('users')->group(function () {
    Route::get('', [UserController::class, 'index'])->name('users.index');
    Route::get('create', [UserController::class, 'create'])->name('users.create');
    Route::post('', [UserController::class, 'store'])->name('users.store');
    Route::get('{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('plugins')->group(function () {
    Route::get('', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('', [PluginController::class, 'store'])->name('plugins.store');
    Route::put('{plugin}', [PluginController::class, 'update'])->name('plugins.update');
    Route::delete('{plugin}', [PluginController::class, 'destroy'])->name('plugins.destroy');
});

require __DIR__.'/auth.php';
