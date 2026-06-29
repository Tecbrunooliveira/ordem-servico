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

        if (file_exists(public_path('vendor/livewire/manifest.json'))) {
            $livewireFile = config('app.debug') ? 'livewire.js' : 'livewire.min.js';

            config([
                'livewire.asset_url' => asset('vendor/livewire/'.$livewireFile),
            ]);
        }

        \Illuminate\Support\Facades\Vite::createAssetPathsUsing(
            fn (string $path, ?bool $secure) => asset($path)
        );
    }
}
