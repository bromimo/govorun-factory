<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

/** Основной сервис-провайдер приложения. */
class AppServiceProvider extends ServiceProvider
{
    /** Регистрация сервисов приложения.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /** Начальная загрузка сервисов приложения.
     *
     * @return void
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
