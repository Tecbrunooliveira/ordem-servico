<?php

namespace App\Support;

use Livewire\Mechanisms\FrontendAssets\FrontendAssets;

class LivewireAssets
{
    public static function scripts(): string
    {
        $html = FrontendAssets::scripts();

        if ($html === '') {
            return '';
        }

        $html = preg_replace(
            '/data-update-uri="[^"]*"/',
            'data-update-uri="'.e(self::updateUri()).'"',
            $html,
            1,
        );

        return preg_replace(
            '/data-module-url="[^"]*"/',
            'data-module-url="'.e(self::moduleUri()).'"',
            $html,
            1,
        ) ?? $html;
    }

    public static function updateUri(): string
    {
        return Subdirectory::applicationUrl(app('livewire')->getUpdateUri());
    }

    public static function moduleUri(): string
    {
        return Subdirectory::applicationUrl(app('livewire')->getUriPrefix());
    }
}
