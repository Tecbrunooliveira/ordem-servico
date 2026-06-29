<?php

/**
 * Ajusta URLs absolutas geradas pelo Vite para deploy em subpasta (ex.: /osv2).
 * Rode após npm run build, com APP_URL apontando para a URL pública.
 */

$root = dirname(__DIR__);

foreach (["{$root}/.env.production", "{$root}/.env"] as $envFile) {
    if (! is_file($envFile)) {
        continue;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), 'APP_URL=')) {
            $appUrl = trim(substr(trim($line), strlen('APP_URL=')), " \t\"'");
            break 2;
        }
    }
}

$appUrl ??= getenv('APP_URL') ?: '';

$subdirectory = rtrim((string) parse_url($appUrl, PHP_URL_PATH), '/');

if ($subdirectory === '') {
    echo "APP_URL sem subpasta — nada a ajustar.\n";
    exit(0);
}

$prefix = $subdirectory.'/build/';
$patterns = [
    'url("/build/' => 'url("'.$prefix,
    "url('/build/" => "url('".$prefix,
    '"/build/assets/' => '"'.$prefix.'assets/',
    "'/build/assets/" => "'".$prefix."assets/",
];

$buildDir = "{$root}/public/build";

if (! is_dir($buildDir)) {
    fwrite(STDERR, "Pasta public/build não encontrada. Rode npm run build primeiro.\n");
    exit(1);
}

$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($buildDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $fileInfo) {
    if (! $fileInfo->isFile()) {
        continue;
    }

    $extension = strtolower($fileInfo->getExtension());

    if (in_array($extension, ['css', 'json'], true)) {
        $files[] = $fileInfo->getPathname();
    }
}

$updated = 0;

foreach (array_unique($files) as $file) {
    $contents = file_get_contents($file);
    $fixed = str_replace(array_keys($patterns), array_values($patterns), $contents);

    if ($fixed !== $contents) {
        file_put_contents($file, $fixed);
        $updated++;
    }
}

echo "Caminhos de build ajustados para {$subdirectory}/build/ ({$updated} arquivo(s)).\n";
