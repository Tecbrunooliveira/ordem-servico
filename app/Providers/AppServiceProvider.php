<?php

namespace App\Providers;

use App\Support\Subdirectory;
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

        config(['session.path' => Subdirectory::sessionPath()]);

        if ($this->app->runningInConsole()) {
            return;
        }

        Subdirectory::configureUrls();

        \Illuminate\Support\Facades\Vite::createAssetPathsUsing(
            fn (string $path, ?bool $secure) => asset($path)
        );
    }
}
