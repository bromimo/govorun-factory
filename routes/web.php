<?php

use App\Http\Controllers\BotController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', [BotController::class, 'index'])->name('dashboard');
    Route::post('/bots', [BotController::class, 'store'])->name('bots.store');
    Route::get('/bots/{bot}/edit', [BotController::class, 'edit'])->name('bots.edit');
    Route::put('/bots/{bot}', [BotController::class, 'update'])->name('bots.update');
    Route::delete('/bots/{bot}', [BotController::class, 'destroy'])->name('bots.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

require __DIR__.'/auth.php';
