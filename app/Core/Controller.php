<?php
declare(strict_types=1);

namespace App\Core;

use App\Model\JuegoModel;

class Controller
{
    protected function sessionUser(): ?array
    {
        if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
            return null;
        }

        return $_SESSION['auth_user'];
    }

    protected function currentSessionUserId(): ?int
    {
        $sessionUser = $this->sessionUser();
        if ($sessionUser === null) {
            return null;
        }

        $userId = $sessionUser['usuario_id'] ?? null;

        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return null;
        }

        return (int) $userId;
    }

    protected function currentSessionUserRole(): ?string
    {
        $sessionUser = $this->sessionUser();
        if ($sessionUser === null) {
            return null;
        }

        $role = isset($sessionUser['rol']) ? (string) $sessionUser['rol'] : '';

        return $role !== '' ? $role : null;
    }

    protected function isModeratorOrAdmin(): bool
    {
        return in_array($this->currentSessionUserRole(), ['moderador', 'admin'], true);
    }

    protected function isAdmin(): bool
    {
        return $this->currentSessionUserRole() === 'admin';
    }

    protected function consumeFlashSuccess(): ?string
    {
        $flashMessage = isset($_SESSION['flash_success']) ? (string) $_SESSION['flash_success'] : null;
        unset($_SESSION['flash_success']);

        return $flashMessage;
    }

    protected function consumeFlashError(): ?string
    {
        $flashMessage = isset($_SESSION['flash_error']) ? (string) $_SESSION['flash_error'] : null;
        unset($_SESSION['flash_error']);

        return $flashMessage;
    }

    protected function setFlashSuccess(string $message): void
    {
        $_SESSION['flash_success'] = $message;
    }

    protected function setFlashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    protected function render(string $view, array $data = []): void
    {
        $pageTitle = $data['pageTitle'] ?? 'Inamania';
        $styles = array_values(array_unique(array_merge(
            $data['styles'] ?? [],
            ['estilos/css/css_pag/log_in.css']
        )));
        $currentPage = $data['currentPage'] ?? 'home';
        $openAuthModal = $data['openAuthModal'] ?? false;
        $authMode = $data['authMode'] ?? 'login';
        $authErrors = $data['authErrors'] ?? [];
        $authSuccess = $data['authSuccess'] ?? null;
        $flashSuccess = isset($data['flashSuccess']) ? (string) $data['flashSuccess'] : $this->consumeFlashSuccess();
        $authFormData = $data['authFormData'] ?? [];
        $openProfileModal = $data['openProfileModal'] ?? false;
        $scripts = $data['scripts'] ?? [];

        if (!isset($data['navGames']) || !is_array($data['navGames'])) {
            try {
                $data['navGames'] = (new JuegoModel())->listMenuGames();
            } catch (\Throwable $exception) {
                $data['navGames'] = [];
            }
        }

        $navGames = $data['navGames'];

        $content = View::make($view, $data);

        require __DIR__ . '/../Views/layouts/main.php';
    }
}