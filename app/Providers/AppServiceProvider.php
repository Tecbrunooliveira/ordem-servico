<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\App::setLocale('pt_BR');

        if (! $this->app->runningInConsole() && ($url = config('app.url'))) {
            \Illuminate\Support\Facades\URL::forceRootUrl($url);
        }
    }
}
