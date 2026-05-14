<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\JuegoModel;

class JuegoController extends Controller
{
    private JuegoModel $juegoModel;

    public function __construct()
    {
        $this->juegoModel = new JuegoModel();
    }

    public function juegos(): void
    {
        $this->render('pages/juegos', [
            'pageTitle' => 'Inamania - Juegos',
            'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/juego.css', 'estilos/css/css_pag/responsive.css'],
            'currentPage' => 'juegos',
            'gamesBySaga' => $this->juegoModel->listGamesGroupedBySaga(),
        ]);
    }

    public function game(): void
    {
        $this->renderCurrentWiki();
    }

    private function renderCurrentWiki(): void
    {
        $routeKey = trim((string) ($_GET['page'] ?? ''));

        if ($routeKey === '') {
            http_response_code(404);
            $this->render('pages/not-found', [
                'pageTitle' => 'Inamania - 404',
                'styles' => ['estilos/css/css_pag/style.css'],
                'currentPage' => 'juegos',
            ]);
            return;
        }

        $this->renderWiki($routeKey);
    }

    private function renderWiki(string $routeKey): void
    {
        try {
            $gameWiki = $this->juegoModel->getGameWikiByRoute($routeKey);
        } catch (\Throwable $exception) {
            $gameWiki = null;
        }

        if ($gameWiki === null) {
            http_response_code(404);
            $this->render('pages/not-found', [
                'pageTitle' => 'Inamania - 404',
                'styles' => ['estilos/css/css_pag/style.css'],
                'currentPage' => 'juegos',
            ]);
            return;
        }

        $this->render('pages/juego', [
            'pageTitle' => 'Inamania - ' . (string) ($gameWiki['title'] ?? 'Juego'),
            'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/juego.css', 'estilos/css/css_pag/foro.css', 'estilos/css/css_pag/responsive.css'],
            'scripts' => ['estilos/js/wiki-personajes.js'],
            'currentPage' => 'juegos',
            'gameWiki' => $gameWiki,
        ]);
    }
}
