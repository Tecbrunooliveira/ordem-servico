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

        if ($this->app->runningInConsole()) {
            return;
        }

        $appUrl = config('app.url');

        if ($appUrl) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);

            if (str_starts_with($appUrl, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        $assetUrl = env('ASSET_URL') ?: $appUrl;

        if ($assetUrl) {
            \Illuminate\Support\Facades\Vite::createAssetPathsUsing(
                fn (string $path, ?bool $secure) => rtrim($assetUrl, '/').'/'.ltrim($path, '/')
            );
        }
    }
}
