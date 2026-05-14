<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Model\EventoModel;

class CarritoController extends Controller
{
    private function isAjaxRequest(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private function todayDate(): string
    {
        return (new \DateTimeImmutable('today'))->format('Y-m-d');
    }

    private function validateAndEnrollUserInEvent(EventoModel $eventoModel, int $userId, int $eventId): array
    {
        $event = $eventoModel->findEventWithRegistrationsById($eventId);
        if ($event === null) {
            return ['success' => false, 'message' => 'El evento no existe.'];
        }

        if ($eventoModel->isUserEnrolledInEvent($userId, $eventId)) {
            return ['success' => true, 'already_enrolled' => true, 'message' => 'Ya estabas inscrito en este evento.'];
        }

        $today = $this->todayDate();
        $startDate = trim((string) ($event['fecha_inicio'] ?? ''));
        $registrationDate = trim((string) ($event['fecha_inscripcion'] ?? ''));
        $slots = $event['plazas'] !== null ? (int) $event['plazas'] : null;
        $totalEnrolled = (int) ($event['total_inscritos'] ?? 0);

        if ($startDate !== '' && $today > $startDate) {
            return ['success' => false, 'message' => 'La inscripcion de este evento ya ha finalizado porque el evento ya ha comenzado.'];
        }

        if ($registrationDate !== '' && $today < $registrationDate) {
            return ['success' => false, 'message' => 'La inscripcion de este evento aun no esta abierta.'];
        }

        if ($slots !== null && $slots > 0 && $totalEnrolled >= $slots) {
            return ['success' => false, 'message' => 'No quedan plazas disponibles para este evento.'];
        }

        $enrolled = $eventoModel->enrollUserInEvent($userId, $eventId);
        if (!$enrolled) {
            return ['success' => false, 'message' => 'No se pudo completar la inscripcion.'];
        }

        return ['success' => true, 'already_enrolled' => false, 'message' => 'Inscripcion completada correctamente.'];
    }

    private function validateAndUnenrollUserFromEvent(EventoModel $eventoModel, int $userId, int $eventId): array
    {
        $event = $eventoModel->findEventWithRegistrationsById($eventId);
        if ($event === null) {
            return ['success' => false, 'message' => 'El evento no existe.'];
        }

        if (!$eventoModel->isUserEnrolledInEvent($userId, $eventId)) {
            return ['success' => false, 'message' => 'No estabas inscrito en este evento.'];
        }

        $today = $this->todayDate();
        $startDate = trim((string) ($event['fecha_inicio'] ?? ''));

        if ($startDate !== '' && $today > $startDate) {
            return ['success' => false, 'message' => 'No puedes desinscribirte de un evento que ya ha comenzado.'];
        }

        $removed = $eventoModel->unenrollUserFromEvent($userId, $eventId);
        if (!$removed) {
            return ['success' => false, 'message' => 'No se pudo completar la desinscripcion.'];
        }

        return ['success' => true, 'message' => 'Te has desinscrito correctamente del evento.'];
    }

    private function getCarrito(): array
    {
        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        return $_SESSION['carrito'];
    }

    private function setCarrito(array $carrito): void
    {
        $_SESSION['carrito'] = $carrito;
    }

    public function addToCart(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
            return;
        }

        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Debes iniciar sesion']);
            return;
        }

        $eventId = (int) ($_POST['evento_id'] ?? 0);
        if ($eventId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de evento invalido']);
            return;
        }

        $carrito = $this->getCarrito();

        if (!isset($carrito[$eventId])) {
            $carrito[$eventId] = [
                'evento_id' => $eventId,
                'titulo' => trim((string) ($_POST['titulo'] ?? '')),
                'precio' => (float) ($_POST['precio'] ?? 0),
                'fecha_inicio' => trim((string) ($_POST['fecha_inicio'] ?? '')),
            ];
        }

        $this->setCarrito($carrito);

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'cart_count' => count($carrito),
            'message' => 'Evento agregado al carrito'
        ]);
    }

    public function viewCart(): void
    {
        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            header('Location: ' . route('home'));
            return;
        }

        $carrito = $this->getCarrito();
        if ($carrito === []) {
            header('Location: ' . route('eventos'));
            return;
        }

        $this->render('pages/carrito', [
            'pageTitle' => 'Inamania - Carrito',
            'styles' => ['estilos/css/css_pag/style.css', 'estilos/css/css_pag/responsive.css'],
            'scripts' => ['estilos/js/carrito.js'],
            'currentPage' => 'eventos',
            'cartItems' => $carrito,
        ]);
    }

    public function removeFromCart(): void
    {
        $isAjaxRequest = $this->isAjaxRequest();

        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjaxRequest) {
                http_response_code(405);
                echo json_encode(['error' => 'Metodo no permitido']);
                return;
            }

            header('Location: ' . route('carrito'));
            return;
        }

        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            if ($isAjaxRequest) {
                http_response_code(401);
                echo json_encode(['error' => 'Debes iniciar sesion']);
                return;
            }

            header('Location: ' . route('home'));
            return;
        }

        $eventId = (int) ($_POST['evento_id'] ?? 0);
        if ($eventId <= 0) {
            if ($isAjaxRequest) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de evento invalido']);
                return;
            }

            header('Location: ' . route('carrito'));
            return;
        }

        $carrito = $this->getCarrito();
        unset($carrito[$eventId]);
        $this->setCarrito($carrito);

        if ($isAjaxRequest) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'cart_count' => count($carrito),
                'message' => 'Evento removido del carrito'
            ]);
            return;
        }

        if ($carrito === []) {
            header('Location: ' . route('eventos'));
            return;
        }

        header('Location: ' . route('carrito'));
    }

    public function joinFreeEvent(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
            return;
        }

        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Debes iniciar sesion']);
            return;
        }

        $eventId = (int) ($_POST['evento_id'] ?? 0);
        if ($eventId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de evento invalido']);
            return;
        }

        $eventoModel = new EventoModel();
        $result = $this->validateAndEnrollUserInEvent($eventoModel, $userId, $eventId);

        if (($result['success'] ?? false) !== true) {
            http_response_code(400);
            echo json_encode(['error' => (string) ($result['message'] ?? 'No se pudo completar la inscripcion.')]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => (string) ($result['message'] ?? 'Inscripcion completada correctamente.'),
        ]);
    }

    public function leaveEvent(): void
    {
        $isAjaxRequest = $this->isAjaxRequest();
        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjaxRequest) {
                http_response_code(405);
                echo json_encode(['error' => 'Metodo no permitido']);
                return;
            }

            header('Location: ' . route('eventos'));
            return;
        }

        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            if ($isAjaxRequest) {
                http_response_code(401);
                echo json_encode(['error' => 'Debes iniciar sesion']);
                return;
            }

            header('Location: ' . route('home'));
            return;
        }

        $eventId = (int) ($_POST['evento_id'] ?? 0);
        if ($eventId <= 0) {
            if ($isAjaxRequest) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de evento invalido']);
                return;
            }

            $_SESSION['flash_error'] = 'ID de evento invalido.';
            header('Location: ' . route('eventos') . '#eventos-inscritos');
            return;
        }

        $eventoModel = new EventoModel();
        $result = $this->validateAndUnenrollUserFromEvent($eventoModel, $userId, $eventId);

        if (($result['success'] ?? false) !== true) {
            if ($isAjaxRequest) {
                http_response_code(400);
                echo json_encode(['error' => (string) ($result['message'] ?? 'No se pudo completar la desinscripcion.')]);
                return;
            }

            $_SESSION['flash_error'] = (string) ($result['message'] ?? 'No se pudo completar la desinscripcion.');
            header('Location: ' . route('eventos') . '#eventos-inscritos');
            return;
        }

        if ($isAjaxRequest) {
            echo json_encode([
                'success' => true,
                'message' => (string) ($result['message'] ?? 'Te has desinscrito correctamente del evento.'),
            ]);
            return;
        }

        $_SESSION['flash_success'] = (string) ($result['message'] ?? 'Te has desinscrito correctamente del evento.');
        header('Location: ' . route('eventos') . '#eventos-inscritos');
    }

    public function checkout(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido']);
            return;
        }

        $userId = $this->currentSessionUserId();
        if ($userId === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Debes iniciar sesion']);
            return;
        }

        $carrito = $this->getCarrito();
        if ($carrito === []) {
            http_response_code(400);
            echo json_encode(['error' => 'Tu carrito esta vacio.']);
            return;
        }

        $eventoModel = new EventoModel();
        $successfulEventIds = [];
        $errors = [];

        foreach ($carrito as $item) {
            $eventId = (int) ($item['evento_id'] ?? 0);
            if ($eventId <= 0) {
                continue;
            }

            $result = $this->validateAndEnrollUserInEvent($eventoModel, $userId, $eventId);
            if (($result['success'] ?? false) === true) {
                $successfulEventIds[] = $eventId;
                continue;
            }

            $errors[] = (string) ($result['message'] ?? 'No se pudo procesar un evento del carrito.');
        }

        if ($successfulEventIds !== []) {
            foreach ($successfulEventIds as $eventId) {
                unset($carrito[$eventId]);
            }
            $this->setCarrito($carrito);
        }

        if ($successfulEventIds === []) {
            http_response_code(400);
            echo json_encode([
                'error' => $errors !== [] ? $errors[0] : 'No se pudo completar el pago.',
            ]);
            return;
        }

        $message = count($successfulEventIds) === 1
            ? 'Pago completado. Te has inscrito en 1 evento.'
            : 'Pago completado. Te has inscrito en ' . count($successfulEventIds) . ' eventos.';

        if ($errors !== []) {
            $message .= ' Algunos eventos no se pudieron procesar.';
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => count($carrito),
        ]);
    }
}
