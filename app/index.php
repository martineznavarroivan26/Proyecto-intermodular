<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Controllers\JuegoController;

$requestedAsset = isset($_GET['asset']) ? (string) $_GET['asset'] : '';

if ($requestedAsset !== '') {
    $assetPath = ltrim(str_replace('\\', '/', rawurldecode($requestedAsset)), '/');

    // Bloquea traversal y limita el acceso a directorios publicos controlados.
    if ($assetPath === '' || str_contains($assetPath, '..')) {
        http_response_code(404);
        exit;
    }

    $isAllowedAsset = str_starts_with($assetPath, 'estilos/') || str_starts_with($assetPath, 'uploads/');
    if (!$isAllowedAsset) {
        http_response_code(404);
        exit;
    }

    $absoluteAssetPath = dirname(__DIR__) . '/' . $assetPath;

    if (!is_file($absoluteAssetPath)) {
        http_response_code(404);
        exit;
    }

    $extension = strtolower((string) pathinfo($absoluteAssetPath, PATHINFO_EXTENSION));
    $mimeByExtension = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    $mimeType = $mimeByExtension[$extension] ?? null;

    if ($mimeType === null) {
        $detectedMimeType = function_exists('mime_content_type') ? mime_content_type($absoluteAssetPath) : false;
        $mimeType = is_string($detectedMimeType) && $detectedMimeType !== ''
            ? $detectedMimeType
            : 'application/octet-stream';
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . (string) filesize($absoluteAssetPath));
    header('Cache-Control: public, max-age=604800');

    readfile($absoluteAssetPath);
    exit;
}

$routes = require dirname(__DIR__) . '/config/routes.php';
$rawPage = $_GET['page'] ?? 'home';
$page = is_string($rawPage) ? trim($rawPage) : 'home';
if ($page === '') {
    $page = 'home';
}

$route = $routes[$page] ?? [JuegoController::class, 'game'];
[$controllerClass, $method] = $route;

try {
    $controller = new $controllerClass();

    if (!is_callable([$controller, $method])) {
        throw new RuntimeException('Ruta invalida');
    }

    $controller->$method();
} catch (\Throwable $exception) {
    http_response_code(404);
    $fallbackControllerClass = $routes['404'][0] ?? null;
    $fallbackMethod = $routes['404'][1] ?? null;

    if (is_string($fallbackControllerClass) && is_string($fallbackMethod) && class_exists($fallbackControllerClass)) {
        $fallbackController = new $fallbackControllerClass();
        if (is_callable([$fallbackController, $fallbackMethod])) {
            $fallbackController->$fallbackMethod();
            exit;
        }
    }

    echo '404';
}
