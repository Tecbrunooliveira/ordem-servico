<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Subpasta (ex.: /osv2) — ajusta REQUEST_URI antes do Laravel ler a rota
if (is_file($envFile = __DIR__.'/../.env')) {
    Dotenv\Dotenv::createImmutable(dirname($envFile))->safeLoad();
}

$subdirectory = rtrim((string) parse_url($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '', PHP_URL_PATH), '/');

if ($subdirectory !== '' && isset($_SERVER['REQUEST_URI'])) {
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

    // Evita que o Laravel duplique /osv2 nas URLs geradas (login, logout, Livewire).
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
