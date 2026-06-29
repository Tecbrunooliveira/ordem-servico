<?php

namespace App\Support;

class Subdirectory
{
    public static function path(): string
    {
        $fromEnv = rtrim((string) parse_url((string) env('APP_URL', ''), PHP_URL_PATH), '/');

        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        foreach (['/osv2'] as $candidate) {
            if ($requestPath === $candidate || str_starts_with($requestPath, $candidate.'/')) {
                return $candidate;
            }
        }

        return '';
    }

    public static function prepareRequest(): void
    {
        $subdirectory = self::path();

        if ($subdirectory === '' || ! isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        $parts = parse_url($_SERVER['REQUEST_URI']);
        $path = $parts['path'] ?? '/';

        if (str_starts_with($path, $subdirectory)) {
            $path = substr($path, strlen($subdirectory)) ?: '/';
        }

        if (str_starts_with($path, '/public')) {
            $path = substr($path, 7) ?: '/';
        }

        if ($path !== '/' && ! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        $_SERVER['REQUEST_URI'] = $path.$query;
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['PHP_SELF'] = '/index.php';
    }

    public static function configureUrls(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '') {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
        }
    }

    public static function sessionPath(): string
    {
        $configured = env('SESSION_PATH');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $path = self::path();

        return $path !== '' ? $path : '/';
    }

    public static function applicationUrl(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = self::normalizeAppPath($path);

        if ($path === '/') {
            return $base;
        }

        return $base.$path;
    }

    public static function normalizeAppPath(string $path): string
    {
        $path = '/'.ltrim($path, '/');
        $basePath = self::path();

        if ($basePath !== '' && (str_starts_with($path, $basePath.'/') || $path === $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        return $path;
    }

    public static function normalizeRedirectUrl(?string $url, ?string $fallback = null): string
    {
        $fallback ??= self::applicationUrl('/dashboard');

        if ($url === null || trim($url) === '') {
            return $fallback;
        }

        $url = trim($url);
        $basePath = self::path();
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($basePath === '') {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            if (str_starts_with($url, $basePath.'/') || $url === $basePath) {
                return $appUrl.substr($url, strlen($basePath)) ?: $appUrl;
            }

            return $appUrl.$url;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if ($path !== '' && ! str_starts_with($path, $basePath)) {
            $query = parse_url($url, PHP_URL_QUERY);
            $fixed = $appUrl.$path;

            return $query ? $fixed.'?'.$query : $fixed;
        }

        return $url;
    }

}
