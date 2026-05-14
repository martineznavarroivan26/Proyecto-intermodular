<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\PerfilModel;

class PerfilController extends Controller
{
    private const MAX_AVATAR_SIZE = 2097152;
    private const ALLOWED_ROLES = ['usuario', 'moderador', 'admin'];

    private function validateAndStoreAvatar(array $avatarFile): array
    {
        if (!isset($avatarFile['error']) || (int) $avatarFile['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, []];
        }

        if ((int) $avatarFile['error'] !== UPLOAD_ERR_OK) {
            return [null, ['No se pudo subir el avatar.']];
        }

        if (!isset($avatarFile['tmp_name']) || !is_uploaded_file((string) $avatarFile['tmp_name'])) {
            return [null, ['El archivo del avatar no es valido.']];
        }

        if ((int) ($avatarFile['size'] ?? 0) > self::MAX_AVATAR_SIZE) {
            return [null, ['El avatar no puede superar los 2 MB.']];
        }

        $tmpName = (string) $avatarFile['tmp_name'];
        $mimeType = '';

        if (class_exists('finfo')) {
            $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = (string) $fileInfo->file($tmpName);
        }

        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!array_key_exists($mimeType, $allowedTypes)) {
            return [null, ['El avatar debe ser una imagen JPG, PNG, WEBP o GIF.']];
        }

        $relativeDirectory = 'uploads/avatars';
        $projectRoot = dirname(__DIR__, 2);
        $targetDirectory = $projectRoot . DIRECTORY_SEPARATOR . $relativeDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            return [null, ['No se pudo preparar la carpeta de avatares.']];
        }

        $fileName = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
        $relativePath = $relativeDirectory . '/' . $fileName;
        $targetFile = $projectRoot . DIRECTORY_SEPARATOR . $relativePath;

        if (!move_uploaded_file($tmpName, $targetFile)) {
            return [null, ['No se pudo guardar el avatar en el servidor.']];
        }

        return [$relativePath, []];
    }

    private function deleteLocalAvatarIfNeeded(?string $avatarPath): void
    {
        if ($avatarPath === null || $avatarPath === '' || !str_starts_with($avatarPath, 'uploads/avatars/')) {
            return;
        }

        $absolutePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $avatarPath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function renderProfile(array $profileUser, array $overrides = []): void
    {
        $defaultData = [
            'pageTitle' => 'Inamania',
            'styles' => [
                'estilos/css/css_pag/style.css',
                'estilos/css/css_pag/responsive.css',
            ],
            'currentPage' => 'home',
            'profileUser' => $profileUser,
            'profileErrors' => [],
            'profileFormData' => [
                'username' => (string) ($profileUser['nombre_usuario'] ?? ''),
                'rol' => (string) ($profileUser['rol'] ?? 'usuario'),
            ],
            'flashSuccess' => $this->consumeFlashSuccess(),
            'openProfileModal' => true,
        ];

        $this->render('pages/home', array_merge($defaultData, $overrides));
    }

    public function perfil(): void
    {
        $userId = $this->currentSessionUserId();

        if ($userId === null) {
            header('Location: ' . route('home'));
            return;
        }

        $perfilModel = new PerfilModel();
        $profileUser = $perfilModel->findById($userId);

        if ($profileUser === null) {
            unset($_SESSION['auth_user']);
            $_SESSION['flash_success'] = 'Debes iniciar sesion para acceder al perfil.';
            header('Location: ' . route('home'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderProfile($profileUser);
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $submittedRole = trim((string) ($_POST['rol'] ?? 'usuario'));
        $avatarFile = $_FILES['avatar'] ?? null;
        $errors = [];
        $isAdminUser = (string) ($profileUser['rol'] ?? ($_SESSION['auth_user']['rol'] ?? 'usuario')) === 'admin';
        $finalRole = (string) ($profileUser['rol'] ?? 'usuario');

        if ($username === '') {
            $errors[] = 'El nombre de usuario es obligatorio.';
        } elseif (mb_strlen($username) > 16) {
            $errors[] = 'El nombre de usuario no puede tener mas de 16 caracteres.';
        }

        if ($perfilModel->usernameExistsForOther($username, $userId)) {
            $errors[] = 'Ese nombre de usuario ya esta en uso.';
        }

        if ($isAdminUser) {
            if (!in_array($submittedRole, self::ALLOWED_ROLES, true)) {
                $errors[] = 'El rol seleccionado no es valido.';
            } else {
                $finalRole = $submittedRole;
            }
        }

        $newAvatarPath = null;
        if (is_array($avatarFile)) {
            [$newAvatarPath, $avatarErrors] = $this->validateAndStoreAvatar($avatarFile);
            $errors = array_merge($errors, $avatarErrors);
        }

        if ($errors !== []) {
            if ($newAvatarPath !== null) {
                $this->deleteLocalAvatarIfNeeded($newAvatarPath);
            }

            $this->renderProfile($profileUser, [
                'profileErrors' => $errors,
                'profileFormData' => [
                    'username' => $username,
                    'rol' => $submittedRole,
                ],
                'flashSuccess' => null,
                'openProfileModal' => true,
            ]);
            return;
        }

        $finalAvatarPath = $newAvatarPath !== null
            ? $newAvatarPath
            : ($profileUser['avatar'] !== null ? (string) $profileUser['avatar'] : null);

        $perfilModel->updateProfile($userId, $username, $finalAvatarPath, $isAdminUser ? $finalRole : null);

        if ($newAvatarPath !== null && isset($profileUser['avatar'])) {
            $this->deleteLocalAvatarIfNeeded((string) $profileUser['avatar']);
        }

        $_SESSION['auth_user'] = [
            'usuario_id' => $userId,
            'nombre_usuario' => $username,
            'email' => (string) $profileUser['email'],
            'avatar' => $finalAvatarPath,
            'rol' => $isAdminUser ? $finalRole : (string) ($profileUser['rol'] ?? ($_SESSION['auth_user']['rol'] ?? 'usuario')),
        ];

        $_SESSION['flash_success'] = 'Perfil actualizado correctamente.';
        header('Location: ' . route('perfil'));
    }
}
