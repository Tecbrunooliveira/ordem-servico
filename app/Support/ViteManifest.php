<?php

namespace App\Support;

use Illuminate\Support\Facades\Vite;

class ViteManifest
{
    /** @var array<string, mixed>|null */
    private static ?array $manifest = null;

    /** @var array<string, mixed>|null */
    private static ?array $fontsManifest = null;

    public static function tags(array $entries): string
    {
        if (Vite::isRunningHot()) {
            return Vite::withEntryPoints($entries)->toHtml();
        }

        $html = self::fontStylesheet();
        $manifest = self::manifest();
        $loadedStyles = [];

        foreach ($entries as $entry) {
            if (! isset($manifest[$entry]['file'])) {
                continue;
            }

            $file = $manifest[$entry]['file'];

            if (str_ends_with($file, '.css') && ! in_array($file, $loadedStyles, true)) {
                $html .= self::stylesheetTag($file);
                $loadedStyles[] = $file;
            }

            if (str_ends_with($file, '.js')) {
                $html .= self::scriptTag($file);
            }
        }

        return $html;
    }

    public static function fontStylesheet(): string
    {
        if (Vite::isRunningHot()) {
            return '';
        }

        $fontsManifest = self::fontsManifest();
        $file = $fontsManifest['style']['file'] ?? null;

        if (! is_string($file) || $file === '') {
            return '';
        }

        return self::stylesheetTag($file);
    }

    private static function stylesheetTag(string $file): string
    {
        return '<link rel="stylesheet" href="'.e(asset('build/'.$file)).'" />'."\n";
    }

    private static function scriptTag(string $file): string
    {
        return '<script type="module" src="'.e(asset('build/'.$file)).'"></script>'."\n";
    }

    /** @return array<string, mixed> */
    private static function manifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = public_path('build/manifest.json');

        self::$manifest = is_file($path)
            ? (json_decode((string) file_get_contents($path), true) ?: [])
            : [];

        return self::$manifest;
    }

    /** @return array<string, mixed> */
    private static function fontsManifest(): array
    {
        if (self::$fontsManifest !== null) {
            return self::$fontsManifest;
        }

        $path = public_path('build/fonts-manifest.json');

        self::$fontsManifest = is_file($path)
            ? (json_decode((string) file_get_contents($path), true) ?: [])
            : [];

        return self::$fontsManifest;
    }
}
