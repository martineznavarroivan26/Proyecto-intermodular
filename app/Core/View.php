<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function make(string $view, array $data = []): string
    {
        $viewFile = __DIR__ . '/../Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('La vista no existe: ' . $view);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;

        return (string) ob_get_clean();
    }
}