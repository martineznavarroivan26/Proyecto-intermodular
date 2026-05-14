<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\AdminModel;

class AdminController extends Controller
{
    private function uploadImage(string $fieldName, string $targetDirectory): ?string
    {
        // Flujo unico de subida para mantener validacion MIME y rutas uniformes en todos los modulos.
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$fieldName];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($mimeType === null || !in_array($mimeType, $allowedMimes, true)) {
            return null;
        }

        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $ext = match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
        };

        $filename = uniqid('img_', true) . $ext;
        $filepath = $targetDirectory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return null;
        }

        $relativePath = str_replace('\\', '/', substr($filepath, strlen($_SERVER['DOCUMENT_ROOT'])));
        if ($relativePath !== '' && $relativePath[0] !== '/') {
            $relativePath = '/' . $relativePath;
        }

        return $relativePath;
    }

    public function admin(): void
    {
        if (!$this->isModeratorOrAdmin()) {
            $this->render('pages/info', [
                'pageTitle' => 'Inamania - Control de Contenido',
                'styles' => [
                    'estilos/css/css_pag/style.css',
                    'estilos/css/css_pag/foro.css',
                    'estilos/css/css_pag/responsive.css',
                ],
                'currentPage' => 'home',
                'headline' => 'Acceso restringido',
                'message' => 'Solo admins y moderadores pueden acceder al control de contenido.',
            ]);
            return;
        }

        // Modo local: se desactiva la capa de base de datos para este panel.
        // El contenido se renderiza con colecciones vacias y sin ejecutar acciones CRUD.
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors[] = 'Panel en modo local: la conexion con base de datos esta desactivada.';
        }

        $this->render('pages/admin', [
            'errors' => $errors,
            'sagas' => [],
            'plataformas' => [],
            'juegos' => [],
            'personajes' => [],
            'equipos' => [],
            'pageTitle' => 'Control de Contenido',
            'styles' => [
                'estilos/css/css_pag/style.css',
                'estilos/css/css_pag/foro.css',
                'estilos/css/css_pag/eventos.css',
                'estilos/css/css_pag/moderacion.css',
                'estilos/css/css_pag/responsive.css',
            ],
            'currentPage' => 'moderacion',
        ]);
        return;

        $model = new AdminModel();
        $errors = [];
        $action = (string) ($_POST['admin_action'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // El panel usa acciones por nombre (admin_action); cada bloque valida y redirige por separado.
                // SAGAS
                if ($action === 'create_saga') {
                    $nombre = trim((string) ($_POST['saga_nombre'] ?? ''));
                    $descripcion = trim((string) ($_POST['saga_descripcion'] ?? ''));

                    if ($nombre === '') {
                        $errors[] = 'El nombre de la saga es obligatorio.';
                    } else {
                        $model->createSaga($nombre, $descripcion !== '' ? $descripcion : null);
                        $_SESSION['flash_success'] = 'Saga creada correctamente.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'update_saga') {
                    $sagaId = (int) ($_POST['saga_id'] ?? 0);
                    $nombre = trim((string) ($_POST['saga_nombre'] ?? ''));
                    $descripcion = trim((string) ($_POST['saga_descripcion'] ?? ''));

                    if ($sagaId <= 0 || $nombre === '') {
                        $errors[] = 'Datos inválidos para actualizar saga.';
                    } else {
                        $model->updateSaga($sagaId, $nombre, $descripcion !== '' ? $descripcion : null);
                        $_SESSION['flash_success'] = 'Saga actualizada.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'delete_saga') {
                    $sagaId = (int) ($_POST['saga_id'] ?? 0);
                    if ($sagaId <= 0) {
                        $errors[] = 'Saga no válida.';
                    } else {
                        $model->deleteSaga($sagaId);
                        $_SESSION['flash_success'] = 'Saga eliminada.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                // PLATAFORMAS
                if ($action === 'create_plataforma') {
                    $nombre = trim((string) ($_POST['plataforma_nombre'] ?? ''));

                    if ($nombre === '') {
                        $errors[] = 'El nombre de la plataforma es obligatorio.';
                    } else {
                        $model->createPlataforma($nombre);
                        $_SESSION['flash_success'] = 'Plataforma creada correctamente.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'update_plataforma') {
                    $plataformaId = (int) ($_POST['plataforma_id'] ?? 0);
                    $nombre = trim((string) ($_POST['plataforma_nombre'] ?? ''));

                    if ($plataformaId <= 0 || $nombre === '') {
                        $errors[] = 'Datos inválidos para actualizar plataforma.';
                    } else {
                        $model->updatePlataforma($plataformaId, $nombre);
                        $_SESSION['flash_success'] = 'Plataforma actualizada.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'delete_plataforma') {
                    $plataformaId = (int) ($_POST['plataforma_id'] ?? 0);
                    if ($plataformaId <= 0) {
                        $errors[] = 'Plataforma no válida.';
                    } else {
                        $model->deletePlataforma($plataformaId);
                        $_SESSION['flash_success'] = 'Plataforma eliminada.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                // JUEGOS
                if ($action === 'create_juego') {
                    $titulo = trim((string) ($_POST['juego_titulo'] ?? ''));
                    $sagaId = (int) ($_POST['juego_saga_id'] ?? 0);
                    $plataformaId = (int) ($_POST['juego_plataforma_id'] ?? 0);
                    $anoLanzamiento = (int) ($_POST['juego_ano'] ?? 0);
                    $descripcion = trim((string) ($_POST['juego_descripcion'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/juegos';
                    $imagen = $this->uploadImage('juego_imagen', $targetDir);

                    if ($titulo === '') {
                        $errors[] = 'El título del juego es obligatorio.';
                    } else {
                        // Se guarda null en campos opcionales para evitar strings vacios en DB.
                        $model->createJuego([
                            'titulo' => $titulo,
                            'saga_id' => $sagaId > 0 ? $sagaId : null,
                            'plataforma_id' => $plataformaId > 0 ? $plataformaId : null,
                            'ano_lanzamiento' => $anoLanzamiento > 0 ? $anoLanzamiento : null,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'imagen' => $imagen,
                        ]);
                        $_SESSION['flash_success'] = 'Juego creado correctamente.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'update_juego') {
                    $juegoId = (int) ($_POST['juego_id'] ?? 0);
                    $titulo = trim((string) ($_POST['juego_titulo'] ?? ''));
                    $sagaId = (int) ($_POST['juego_saga_id'] ?? 0);
                    $plataformaId = (int) ($_POST['juego_plataforma_id'] ?? 0);
                    $anoLanzamiento = (int) ($_POST['juego_ano'] ?? 0);
                    $descripcion = trim((string) ($_POST['juego_descripcion'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/juegos';
                    $imagen = $this->uploadImage('juego_imagen', $targetDir);

                    if ($juegoId <= 0 || $titulo === '') {
                        $errors[] = 'Datos inválidos para actualizar juego.';
                    } else {
                        $currentJuego = $model->getJuegoById($juegoId);
                        if ($imagen === null && $currentJuego !== null) {
                            $imagen = $currentJuego['imagen'];
                        }

                        $model->updateJuego($juegoId, [
                            'titulo' => $titulo,
                            'saga_id' => $sagaId > 0 ? $sagaId : null,
                            'plataforma_id' => $plataformaId > 0 ? $plataformaId : null,
                            'ano_lanzamiento' => $anoLanzamiento > 0 ? $anoLanzamiento : null,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'imagen' => $imagen,
                        ]);
                        $_SESSION['flash_success'] = 'Juego actualizado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'delete_juego') {
                    $juegoId = (int) ($_POST['juego_id'] ?? 0);
                    if ($juegoId <= 0) {
                        $errors[] = 'Juego no válido.';
                    } else {
                        $model->deleteJuego($juegoId);
                        $_SESSION['flash_success'] = 'Juego eliminado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                // PERSONAJES
                if ($action === 'create_personaje') {
                    $juegoId = (int) ($_POST['personaje_juego_id'] ?? 0);
                    $nombre = trim((string) ($_POST['personaje_nombre'] ?? ''));
                    $posicion = trim((string) ($_POST['personaje_posicion'] ?? ''));
                    $elemento = trim((string) ($_POST['personaje_elemento'] ?? ''));
                    $descripcion = trim((string) ($_POST['personaje_descripcion'] ?? ''));
                    $estiloJuego = trim((string) ($_POST['personaje_estilo'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/personajes';
                    $imagen = $this->uploadImage('personaje_imagen', $targetDir);

                    if ($juegoId <= 0 || $nombre === '' || $posicion === '' || $elemento === '') {
                        $errors[] = 'Juego, nombre, posición y elemento son obligatorios.';
                    } else {
                        $model->createPersonaje([
                            'juego_id' => $juegoId,
                            'nombre' => $nombre,
                            'posicion' => $posicion,
                            'elemento' => $elemento,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'estilo_juego' => $estiloJuego !== '' ? $estiloJuego : null,
                            'imagen' => $imagen,
                            'pe' => (int) ($_POST['personaje_pe'] ?? 0) ?: null,
                            'pt' => (int) ($_POST['personaje_pt'] ?? 0) ?: null,
                            'tiro' => (int) ($_POST['personaje_tiro'] ?? 0) ?: null,
                            'control' => (int) ($_POST['personaje_control'] ?? 0) ?: null,
                            'defensa' => (int) ($_POST['personaje_defensa'] ?? 0) ?: null,
                            'rapidez' => (int) ($_POST['personaje_rapidez'] ?? 0) ?: null,
                            'fisico' => (int) ($_POST['personaje_fisico'] ?? 0) ?: null,
                            'aguante' => (int) ($_POST['personaje_aguante'] ?? 0) ?: null,
                        ]);
                        $_SESSION['flash_success'] = 'Personaje creado correctamente.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'update_personaje') {
                    $personajeId = (int) ($_POST['personaje_id'] ?? 0);
                    $juegoId = (int) ($_POST['personaje_juego_id'] ?? 0);
                    $nombre = trim((string) ($_POST['personaje_nombre'] ?? ''));
                    $posicion = trim((string) ($_POST['personaje_posicion'] ?? ''));
                    $elemento = trim((string) ($_POST['personaje_elemento'] ?? ''));
                    $descripcion = trim((string) ($_POST['personaje_descripcion'] ?? ''));
                    $estiloJuego = trim((string) ($_POST['personaje_estilo'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/personajes';
                    $imagen = $this->uploadImage('personaje_imagen', $targetDir);

                    if ($personajeId <= 0 || $juegoId <= 0 || $nombre === '' || $posicion === '' || $elemento === '') {
                        $errors[] = 'Datos inválidos.';
                    } else {
                        $currentPersonaje = $model->getPersonajeById($personajeId);
                        if ($imagen === null && $currentPersonaje !== null) {
                            $imagen = $currentPersonaje['imagen'];
                        }

                        $model->updatePersonaje($personajeId, [
                            'juego_id' => $juegoId,
                            'nombre' => $nombre,
                            'posicion' => $posicion,
                            'elemento' => $elemento,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'estilo_juego' => $estiloJuego !== '' ? $estiloJuego : null,
                            'imagen' => $imagen,
                            'pe' => (int) ($_POST['personaje_pe'] ?? 0) ?: null,
                            'pt' => (int) ($_POST['personaje_pt'] ?? 0) ?: null,
                            'tiro' => (int) ($_POST['personaje_tiro'] ?? 0) ?: null,
                            'control' => (int) ($_POST['personaje_control'] ?? 0) ?: null,
                            'defensa' => (int) ($_POST['personaje_defensa'] ?? 0) ?: null,
                            'rapidez' => (int) ($_POST['personaje_rapidez'] ?? 0) ?: null,
                            'fisico' => (int) ($_POST['personaje_fisico'] ?? 0) ?: null,
                            'aguante' => (int) ($_POST['personaje_aguante'] ?? 0) ?: null,
                        ]);
                        $_SESSION['flash_success'] = 'Personaje actualizado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'delete_personaje') {
                    $personajeId = (int) ($_POST['personaje_id'] ?? 0);
                    if ($personajeId <= 0) {
                        $errors[] = 'Personaje no válido.';
                    } else {
                        $model->deletePersonaje($personajeId);
                        $_SESSION['flash_success'] = 'Personaje eliminado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                // EQUIPOS
                if ($action === 'create_equipo') {
                    $juegoId = (int) ($_POST['equipo_juego_id'] ?? 0);
                    $nombre = trim((string) ($_POST['equipo_nombre'] ?? ''));
                    $descripcion = trim((string) ($_POST['equipo_descripcion'] ?? ''));
                    $entrenador = trim((string) ($_POST['equipo_entrenador'] ?? ''));
                    $uniforme = trim((string) ($_POST['equipo_uniforme'] ?? ''));
                    $estiloJuego = trim((string) ($_POST['equipo_estilo'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/equipos';
                    $escudo = $this->uploadImage('equipo_escudo', $targetDir);

                    if ($juegoId <= 0 || $nombre === '') {
                        $errors[] = 'Juego y nombre del equipo son obligatorios.';
                    } else {
                        $model->createEquipo([
                            'juego_id' => $juegoId,
                            'nombre' => $nombre,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'escudo' => $escudo,
                            'entrenador' => $entrenador !== '' ? $entrenador : null,
                            'uniforme' => $uniforme !== '' ? $uniforme : null,
                            'estilo_juego' => $estiloJuego !== '' ? $estiloJuego : null,
                        ]);
                        $_SESSION['flash_success'] = 'Equipo creado correctamente.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'update_equipo') {
                    $equipoId = (int) ($_POST['equipo_id'] ?? 0);
                    $juegoId = (int) ($_POST['equipo_juego_id'] ?? 0);
                    $nombre = trim((string) ($_POST['equipo_nombre'] ?? ''));
                    $descripcion = trim((string) ($_POST['equipo_descripcion'] ?? ''));
                    $entrenador = trim((string) ($_POST['equipo_entrenador'] ?? ''));
                    $uniforme = trim((string) ($_POST['equipo_uniforme'] ?? ''));
                    $estiloJuego = trim((string) ($_POST['equipo_estilo'] ?? ''));

                    $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/imagenes/equipos';
                    $escudo = $this->uploadImage('equipo_escudo', $targetDir);

                    if ($equipoId <= 0 || $juegoId <= 0 || $nombre === '') {
                        $errors[] = 'Datos inválidos.';
                    } else {
                        $currentEquipo = $model->getEquipoById($equipoId);
                        if ($escudo === null && $currentEquipo !== null) {
                            $escudo = $currentEquipo['escudo'];
                        }

                        $model->updateEquipo($equipoId, [
                            'juego_id' => $juegoId,
                            'nombre' => $nombre,
                            'descripcion' => $descripcion !== '' ? $descripcion : null,
                            'escudo' => $escudo,
                            'entrenador' => $entrenador !== '' ? $entrenador : null,
                            'uniforme' => $uniforme !== '' ? $uniforme : null,
                            'estilo_juego' => $estiloJuego !== '' ? $estiloJuego : null,
                        ]);
                        $_SESSION['flash_success'] = 'Equipo actualizado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }

                if ($action === 'delete_equipo') {
                    $equipoId = (int) ($_POST['equipo_id'] ?? 0);
                    if ($equipoId <= 0) {
                        $errors[] = 'Equipo no válido.';
                    } else {
                        $model->deleteEquipo($equipoId);
                        $_SESSION['flash_success'] = 'Equipo eliminado.';
                        header('Location: ' . route('admin'));
                        exit;
                    }
                }
            } catch (\Throwable $exception) {
                $errors[] = 'Error: ' . $exception->getMessage();
            }
        }

        $sagas = $model->listSagas();
        $plataformas = $model->listPlataformas();
        $juegos = $model->listJuegos();
        $personajes = $model->listPersonajes();
        $equipos = $model->listEquipos();

        $this->render('pages/admin', [
            'errors' => $errors,
            'sagas' => $sagas,
            'plataformas' => $plataformas,
            'juegos' => $juegos,
            'personajes' => $personajes,
            'equipos' => $equipos,
            'pageTitle' => 'Control de Contenido',
            'styles' => [
                'estilos/css/css_pag/style.css',
                'estilos/css/css_pag/foro.css',
                'estilos/css/css_pag/eventos.css',
                'estilos/css/css_pag/moderacion.css',
                'estilos/css/css_pag/responsive.css',
            ],
            'currentPage' => 'moderacion',
        ]);
    }
}
