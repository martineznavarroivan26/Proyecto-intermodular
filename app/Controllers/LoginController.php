<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\UserModel;

class LoginController extends Controller
{
    private function homeStyles(): array
    {
        return ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/responsive.css'];
    }

    private function authViewData(string $authMode = 'login', array $overrides = []): array
    {
        $currentUser = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;

        return array_merge([
            'pageTitle' => 'Inamania - Log In',
            'styles' => $this->homeStyles(),
            'currentPage' => 'home',
            'openAuthModal' => true,
            'authMode' => $authMode,
            'authErrors' => [],
            'authSuccess' => null,
            'authFormData' => [],
            'currentUser' => $currentUser,
            'isAuthenticated' => $currentUser !== null,
        ], $overrides);
    }

    private function handleLoginRequest(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $clientIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $errors = [];

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Introduce un correo electronico valido.';
        }

        if ($password === '') {
            $errors[] = 'La contrasena es obligatoria.';
        }

        $formData = ['email' => $email];

        if ($errors !== []) {
            return $this->authViewData('login', [
                'authErrors' => $errors,
                'authFormData' => $formData,
            ]);
        }

        try {
            $userModel = new UserModel();

            if ($clientIp !== '') {
                $ipBlock = $userModel->getActiveIpBlock($clientIp);
                if ($ipBlock !== null) {
                    $until = isset($ipBlock['bloqueado_hasta']) && $ipBlock['bloqueado_hasta'] !== null
                        ? ' hasta ' . (string) $ipBlock['bloqueado_hasta']
                        : ' de forma indefinida';
                    $reason = trim((string) ($ipBlock['motivo'] ?? ''));
                    $message = 'Tu IP está bloqueada' . $until . '.';
                    if ($reason !== '') {
                        $message .= ' Motivo: ' . $reason;
                    }

                    return $this->authViewData('login', [
                        'authErrors' => [$message],
                        'authFormData' => $formData,
                    ]);
                }
            }

            $user = $userModel->authenticateByEmail($email, $password);

            if ($user === null) {
                return $this->authViewData('login', [
                    'authErrors' => ['Correo o contrasena incorrectos.'],
                    'authFormData' => $formData,
                ]);
            }

            $userBlock = $userModel->getActiveUserBlock((int) ($user['usuario_id'] ?? 0));
            if ($userBlock !== null) {
                $until = isset($userBlock['bloqueado_hasta']) && $userBlock['bloqueado_hasta'] !== null
                    ? ' hasta ' . (string) $userBlock['bloqueado_hasta']
                    : ' de forma indefinida';
                $reason = trim((string) ($userBlock['motivo'] ?? ''));
                $message = 'Tu usuario está bloqueado' . $until . '.';
                if ($reason !== '') {
                    $message .= ' Motivo: ' . $reason;
                }

                return $this->authViewData('login', [
                    'authErrors' => [$message],
                    'authFormData' => $formData,
                ]);
            }

            session_regenerate_id(true);
            $_SESSION['auth_user'] = $user;

            return $this->authViewData('login', [
                'openAuthModal' => false,
                'flashSuccess' => 'Sesion iniciada correctamente.',
                'authFormData' => [],
            ]);
        } catch (\Throwable $exception) {
            return $this->authViewData('login', [
                'authErrors' => ['No se pudo validar el acceso en la base de datos.'],
                'authFormData' => $formData,
            ]);
        }
    }

    private function handleLogoutRequest(): array
    {
        unset($_SESSION['auth_user']);
        session_regenerate_id(true);

        return $this->authViewData('login', [
            'flashSuccess' => 'Sesion cerrada correctamente.',
            'openAuthModal' => false,
            'authFormData' => [],
        ]);
    }

    private function handleRegisterRequest(): array
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');
        $registrationIp = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $errors = [];

        if ($username === '') {
            $errors[] = 'El nombre de usuario es obligatorio.';
        } elseif (mb_strlen($username) > 16) {
            $errors[] = 'El nombre de usuario no puede tener mas de 16 caracteres.';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Introduce un correo electronico valido.';
        }

        if (mb_strlen($password) < 6) {
            $errors[] = 'La contrasena debe tener al menos 6 caracteres.';
        }

        if ($password !== $passwordConfirmation) {
            $errors[] = 'Las contrasenas no coinciden.';
        }

        $formData = [
            'username' => $username,
            'email' => $email,
        ];

        if ($errors !== []) {
            return $this->authViewData('register', [
                'authErrors' => $errors,
                'authFormData' => $formData,
            ]);
        }

        try {
            $userModel = new UserModel();

            if ($userModel->usernameExists($username)) {
                $errors[] = 'Ese nombre de usuario ya esta en uso.';
            }

            if ($userModel->emailExists($email)) {
                $errors[] = 'Ese correo electronico ya esta registrado.';
            }

            if ($errors !== []) {
                return $this->authViewData('register', [
                    'authErrors' => $errors,
                    'authFormData' => $formData,
                ]);
            }

            $userId = $userModel->createUser($username, $email, $password, $registrationIp !== '' ? $registrationIp : null);

            session_regenerate_id(true);
            $_SESSION['auth_user'] = [
                'usuario_id' => $userId,
                'nombre_usuario' => $username,
                'email' => $email,
                'avatar' => null,
                'rol' => 'usuario',
            ];

            return $this->authViewData('login', [
                'openAuthModal' => false,
                'flashSuccess' => 'Cuenta creada correctamente.',
                'authFormData' => [],
            ]);
        } catch (\Throwable $exception) {
            return $this->authViewData('register', [
                'authErrors' => ['No se pudo guardar el registro en la base de datos.'],
                'authFormData' => $formData,
            ]);
        }
    }

    public function login(): void
    {
        $authMode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
        $authAction = (string) ($_POST['auth_action'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authAction === 'register') {
            $viewData = $this->handleRegisterRequest();

            if (isset($viewData['flashSuccess']) && $viewData['flashSuccess'] !== null) {
                $_SESSION['flash_success'] = (string) $viewData['flashSuccess'];
                header('Location: ' . route('home'));

                return;
            }

            $this->render('pages/home', $viewData);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authAction === 'login') {
            $viewData = $this->handleLoginRequest();

            if (isset($viewData['flashSuccess']) && $viewData['flashSuccess'] !== null) {
                $_SESSION['flash_success'] = (string) $viewData['flashSuccess'];
                header('Location: ' . route('home'));

                return;
            }

            $this->render('pages/home', $viewData);

            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authAction === 'logout') {
            $viewData = $this->handleLogoutRequest();

            if (isset($viewData['flashSuccess']) && $viewData['flashSuccess'] !== null) {
                $_SESSION['flash_success'] = (string) $viewData['flashSuccess'];
                header('Location: ' . route('home'));

                return;
            }

            $this->render('pages/home', $viewData);

            return;
        }

        $this->render('pages/home', $this->authViewData($authMode));
    }
}
