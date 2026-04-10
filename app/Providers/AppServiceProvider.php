<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

/** Основной сервис-провайдер приложения. */
class AppServiceProvider extends ServiceProvider
{
    /** Регистрация сервисов приложения.
     *
     */
    public function register(): void
    {
        //
    }

    /** Начальная загрузка сервисов приложения.
     *
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
