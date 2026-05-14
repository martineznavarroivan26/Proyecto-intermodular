<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // Windows no distingue mayusculas/minusculas en rutas, Linux si.
    // Este fallback mantiene la estructura actual aunque no coincida el case
    // entre el nombre de archivo y el de la clase.
    if (!is_file($file)) {
        $directory = dirname($file);
        $expectedFileName = basename($file);

        if (is_dir($directory)) {
            $entries = scandir($directory);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (strcasecmp((string) $entry, $expectedFileName) === 0) {
                        $file = $directory . '/' . $entry;
                        break;
                    }
                }
            }
        }
    }

    if (is_file($file)) {
        require $file;
    }
});

function route(string $page): string
{
    return '?page=' . urlencode($page);
}

function asset(string $path): string
{
    $normalizedPath = ltrim(str_replace('\\', '/', $path), './');
    $encodedPath = str_replace('%2F', '/', rawurlencode($normalizedPath));
    $absolutePath = dirname(__DIR__) . '/' . $normalizedPath;

    if (is_file($absolutePath)) {
        return './?asset=' . $encodedPath . '&v=' . filemtime($absolutePath);
    }

    return './?asset=' . $encodedPath;
}