<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\ModeratorModel;

class ModeratorController extends Controller
{
    private function uploadImage(string $fieldName, string $targetDirectory): ?string
    {
        // No requiere archivo subido
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        // Error en subida
        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];

        // Valida el tipo MIME real leyendo el contenido del archivo temporal,
        // no solo la extension del nombre subido.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        // Si finfo se inicializa bien, detecta un MIME como image/png.
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            // Libera el recurso abierto por finfo_open.
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($mimeType === null || !in_array($mimeType, $allowedMimes, true)) {
            return null;
        }

        // Crear directorio si no existe
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        // Generar nombre de archivo único
        $ext = match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
        };

        $filename = uniqid('img_', true) . $ext;
        $filepath = $targetDirectory . '/' . $filename;

        // Guardar archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return null;
        }

        // Retornar ruta relativa para guardar en DB (asegurar que comienza con /)
        $relativePath = str_replace('\\', '/', substr($filepath, strlen($_SERVER['DOCUMENT_ROOT'])));
        if ($relativePath !== '' && $relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $relativePath;
    }

    private function parseDateOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$parsed || $parsed->format('Y-m-d') !== $value) {
            return null;
        }

        return $value;
    }

    private function parseDateTimeLocalOrNull(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $value);
        if (!$parsed) {
            return null;
        }

        return $parsed->format('Y-m-d H:i:s');
    }

    private function handleModeratorAction(ModeratorModel $model): array
    {
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $errors;
        }

        if (!$this->isModeratorOrAdmin()) {
            $errors[] = 'No tienes permisos para realizar acciones de moderación.';
            return $errors;
        }

        $action = (string) ($_POST['moderator_action'] ?? '');

        try {
            if ($action === 'delete_post') {
                $postId = (int) ($_POST['post_id'] ?? 0);
                if ($postId <= 0) {
                    $errors[] = 'Post no válido.';
                } else {
                    $model->deletePost($postId);
                    $_SESSION['flash_success'] = 'Post eliminado correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'delete_comment') {
                $commentId = (int) ($_POST['comment_id'] ?? 0);
                if ($commentId <= 0) {
                    $errors[] = 'Comentario no válido.';
                } else {
                    $model->deleteComment($commentId);
                    $_SESSION['flash_success'] = 'Comentario eliminado correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'block_user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                $reason = trim((string) ($_POST['reason'] ?? ''));
                $blockedUntil = $this->parseDateTimeLocalOrNull((string) ($_POST['blocked_until'] ?? ''));

                if ($userId <= 0) {
                    $errors[] = 'Usuario no válido.';
                } elseif ($userId === $this->currentSessionUserId()) {
                    $errors[] = 'No puedes bloquear tu propio usuario.';
                } else {
                    $model->blockUser($userId, $reason !== '' ? $reason : null, $blockedUntil);
                    $_SESSION['flash_success'] = 'Usuario bloqueado correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'unblock_user') {
                $userId = (int) ($_POST['user_id'] ?? 0);
                if ($userId <= 0) {
                    $errors[] = 'Usuario no válido.';
                } else {
                    $model->unblockUser($userId);
                    $_SESSION['flash_success'] = 'Usuario desbloqueado correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'change_user_role') {
                if (!$this->isAdmin()) {
                    $errors[] = 'Solo un administrador puede cambiar roles de usuario.';
                } else {
                    $userId = (int) ($_POST['user_id'] ?? 0);
                    $newRole = trim((string) ($_POST['new_role'] ?? ''));
                    $validRoles = ['usuario', 'moderador', 'admin'];

                    if ($userId <= 0) {
                        $errors[] = 'Usuario no válido.';
                    } elseif (!in_array($newRole, $validRoles, true)) {
                        $errors[] = 'Rol no válido.';
                    } elseif ($userId === $this->currentSessionUserId()) {
                        $errors[] = 'No puedes cambiar tu propio rol desde este panel.';
                    } else {
                        $model->updateUserRole($userId, $newRole);
                        $_SESSION['flash_success'] = 'Rol de usuario actualizado correctamente.';
                        header('Location: ' . route('moderacion'));
                        exit;
                    }
                }
            }

            if ($action === 'block_ip') {
                $ip = trim((string) ($_POST['ip'] ?? ''));
                $reason = trim((string) ($_POST['reason'] ?? ''));
                $blockedUntil = $this->parseDateTimeLocalOrNull((string) ($_POST['blocked_until'] ?? ''));

                if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    $errors[] = 'IP no válida.';
                } else {
                    $model->blockIp($ip, $reason !== '' ? $reason : null, $blockedUntil);
                    $_SESSION['flash_success'] = 'IP bloqueada correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'unblock_ip') {
                $ip = trim((string) ($_POST['ip'] ?? ''));
                if ($ip === '') {
                    $errors[] = 'IP no válida.';
                } else {
                    $model->unblockIp($ip);
                    $_SESSION['flash_success'] = 'IP desbloqueada correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'create_event' || $action === 'update_event') {
                $eventId = (int) ($_POST['event_id'] ?? 0);
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $startDate = $this->parseDateOrNull((string) ($_POST['start_date'] ?? ''));
                $endDate = $this->parseDateOrNull((string) ($_POST['end_date'] ?? ''));
                $registrationDate = $this->parseDateOrNull((string) ($_POST['registration_date'] ?? ''));
                $location = trim((string) ($_POST['location'] ?? ''));
                $slotsRaw = trim((string) ($_POST['slots'] ?? ''));
                $priceRaw = trim((string) ($_POST['price'] ?? '0'));

                if ($title === '') {
                    $errors[] = 'El título del evento es obligatorio.';
                }
                if ($startDate === null) {
                    $errors[] = 'La fecha de inicio es obligatoria y válida.';
                }
                if ($endDate !== null && $startDate !== null && $endDate < $startDate) {
                    $errors[] = 'La fecha fin no puede ser anterior a la fecha inicio.';
                }
                if (!is_numeric($priceRaw) || (float) $priceRaw < 0) {
                    $errors[] = 'El precio debe ser un número válido >= 0.';
                }

                $slots = null;
                if ($slotsRaw !== '') {
                    if (!ctype_digit($slotsRaw) || (int) $slotsRaw <= 0) {
                        $errors[] = 'Las plazas deben ser un entero mayor que 0.';
                    } else {
                        $slots = (int) $slotsRaw;
                    }
                }

                if ($errors === []) {
                    $payload = [
                        'titulo' => $title,
                        'descripcion' => $description !== '' ? $description : null,
                        'fecha_inscripcion' => $registrationDate,
                        'fecha_inicio' => $startDate,
                        'fecha_fin' => $endDate ?? $startDate,
                        'lugar' => $location !== '' ? $location : null,
                        'plazas' => $slots,
                        'precio' => round((float) $priceRaw, 2),
                    ];

                    if ($action === 'create_event') {
                        $model->createEvent($payload);
                        $_SESSION['flash_success'] = 'Evento creado correctamente.';
                    } else {
                        if ($eventId <= 0) {
                            $errors[] = 'Evento no válido para editar.';
                        } else {
                            $model->updateEvent($eventId, $payload);
                            $_SESSION['flash_success'] = 'Evento actualizado correctamente.';
                        }
                    }

                    if ($errors === []) {
                        header('Location: ' . route('moderacion'));
                        exit;
                    }
                }
            }

            if ($action === 'create_carousel' || $action === 'update_carousel') {
                $carouselId = (int) ($_POST['carousel_id'] ?? 0);
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $order = (int) ($_POST['order'] ?? 1);
                $active = isset($_POST['active']) ? 1 : 0;

                $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/carrousel';
                $image = null;

                if ($action === 'create_carousel') {
                    // Para crear: imagen obligatoria
                    $image = $this->uploadImage('image', $targetDir);
                    if ($image === null) {
                        $errors[] = 'Carrusel: falta imagen o tipo no válido (JPG, PNG, GIF, WebP).';
                    }
                } else {
                    // Para actualizar: imagen opcional
                    $uploadedImage = $this->uploadImage('image', $targetDir);
                    if ($uploadedImage !== null) {
                        $image = $uploadedImage;
                    } else {
                        // Si no se subió imagen, mantener la actual
                        $currentItem = $model->getCarouselItemById($carouselId);
                        if ($currentItem !== null) {
                            $image = $currentItem['imagen'] ?? null;
                        }
                    }
                }

                if ($title === '' || $image === '') {
                    $errors[] = 'Carrusel: título e imagen son obligatorios.';
                } else {
                    $payload = [
                        'titulo' => $title,
                        'descripcion' => $description !== '' ? $description : null,
                        'imagen' => $image,
                        'orden' => max(1, $order),
                        'activo' => $active,
                    ];

                    if ($action === 'create_carousel') {
                        $model->createCarouselItem($payload);
                        $_SESSION['flash_success'] = 'Slide de carrusel creado.';
                    } else {
                        if ($carouselId <= 0) {
                            $errors[] = 'Slide no válido para editar.';
                        } else {
                            $model->updateCarouselItem($carouselId, $payload);
                            $_SESSION['flash_success'] = 'Slide de carrusel actualizado.';
                        }
                    }

                    if ($errors === []) {
                        header('Location: ' . route('moderacion'));
                        exit;
                    }
                }
            }

            if ($action === 'create_news' || $action === 'update_news') {
                $newsId = (int) ($_POST['news_id'] ?? 0);
                $title = trim((string) ($_POST['title'] ?? ''));
                $text = trim((string) ($_POST['text'] ?? ''));
                $order = (int) ($_POST['order'] ?? 1);
                $active = isset($_POST['active']) ? 1 : 0;

                $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/noticias';
                $image = null;

                if ($action === 'create_news') {
                    // Para crear: imagen obligatoria
                    $image = $this->uploadImage('image', $targetDir);
                    if ($image === null) {
                        $errors[] = 'Noticias: falta imagen o tipo no válido (JPG, PNG, GIF, WebP).';
                    }
                } else {
                    // Para actualizar: imagen opcional
                    $uploadedImage = $this->uploadImage('image', $targetDir);
                    if ($uploadedImage !== null) {
                        $image = $uploadedImage;
                    } else {
                        // Si no se subió imagen, mantener la actual
                        $currentItem = $model->getNewsItemById($newsId);
                        if ($currentItem !== null) {
                            $image = $currentItem['imagen'] ?? null;
                        }
                    }
                }

                if ($title === '' || $text === '' || $image === '') {
                    $errors[] = 'Noticias: título, texto e imagen son obligatorios.';
                } else {
                    $payload = [
                        'titulo' => $title,
                        'texto' => $text,
                        'imagen' => $image,
                        'orden' => max(1, $order),
                        'activo' => $active,
                    ];

                    if ($action === 'create_news') {
                        $model->createNewsItem($payload);
                        $_SESSION['flash_success'] = 'Noticia creada correctamente.';
                    } else {
                        if ($newsId <= 0) {
                            $errors[] = 'Noticia no válida para editar.';
                        } else {
                            $model->updateNewsItem($newsId, $payload);
                            $_SESSION['flash_success'] = 'Noticia actualizada correctamente.';
                        }
                    }

                    if ($errors === []) {
                        header('Location: ' . route('moderacion'));
                        exit;
                    }
                }
            }

            if ($action === 'delete_event') {
                $eventId = (int) ($_POST['event_id'] ?? 0);
                if ($eventId <= 0) {
                    $errors[] = 'Evento no válido para eliminar.';
                } else {
                    $model->deleteEvent($eventId);
                    $_SESSION['flash_success'] = 'Evento eliminado correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'delete_carousel') {
                $carouselId = (int) ($_POST['carousel_id'] ?? 0);
                if ($carouselId <= 0) {
                    $errors[] = 'Slide no válido para eliminar.';
                } else {
                    $model->deleteCarouselItem($carouselId);
                    $_SESSION['flash_success'] = 'Slide de carrusel eliminado.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }

            if ($action === 'delete_news') {
                $newsId = (int) ($_POST['news_id'] ?? 0);
                if ($newsId <= 0) {
                    $errors[] = 'Noticia no válida para eliminar.';
                } else {
                    $model->deleteNewsItem($newsId);
                    $_SESSION['flash_success'] = 'Noticia eliminada correctamente.';
                    header('Location: ' . route('moderacion'));
                    exit;
                }
            }
        } catch (\Throwable $exception) {
            $errors[] = 'Se produjo un error al ejecutar la acción de moderación.';
        }

        return $errors;
    }

    public function moderacion(): void
    {
        if ($this->currentSessionUserId() === null || !$this->isModeratorOrAdmin()) {
            $this->render('pages/info', [
                'pageTitle' => 'Inamania - Moderación',
                'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/foro.css', 'estilos/css/css_pag/responsive.css'],
                'currentPage' => 'home',
                'headline' => 'Acceso restringido',
                'message' => 'Solo moderadores y administradores pueden acceder al panel de moderación.',
            ]);
            return;
        }

        $model = new ModeratorModel();
        $actionErrors = $this->handleModeratorAction($model);

        $this->render('pages/moderacion', [
            'pageTitle' => 'Inamania - Panel de Moderación',
            'styles' => [
                'estilos/css/css_pag/style.css',
                'estilos/css/css_pag/foro.css',
                'estilos/css/css_pag/eventos.css',
                'estilos/css/css_pag/moderacion.css',
                'estilos/css/css_pag/responsive.css',
            ],
            'currentPage' => 'moderacion',
            'actionErrors' => $actionErrors,
            'canManageRoles' => $this->isAdmin(),
            'users' => $model->listUsersWithStatus(),
            'ipBlocks' => $model->listIpBlocks(),
            'posts' => $model->listPostsForModeration(),
            'comments' => $model->listCommentsForModeration(),
            'events' => $model->listEventsForModeration(),
            'carouselItems' => $model->listCarouselItems(),
            'newsItems' => $model->listNewsItems(),
        ]);
    }
}
