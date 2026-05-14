<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class EventoModel
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? \ConectarDB::conexion();
    }

    public function listEventsBetween(string $startDate, string $endDate): array
    {
        // Regla de solape: un evento entra si empieza antes del fin de ventana
        // y termina (o empieza) despues del inicio de ventana.
        $statement = $this->connection->prepare(
            'SELECT
                evento_id,
                titulo,
                descripcion,
                fecha_inscripcion,
                fecha_inicio,
                fecha_fin,
                lugar,
                plazas,
                precio,
                creado_en
             FROM evento
             WHERE fecha_inicio <= :end_date
               AND COALESCE(fecha_fin, fecha_inicio) >= :start_date
             ORDER BY fecha_inicio ASC, titulo ASC'
        );

        $statement->execute([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listRecommendedEventsByRegistrations(int $limit = 3, ?string $fromDate = null): array
    {
        $limit = max(1, min(10, $limit));

        if ($fromDate === null || trim($fromDate) === '') {
            $fromDate = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }

        // Recomienda por volumen de inscripciones y desempata por fecha de inicio.
        $statement = $this->connection->prepare(
            "SELECT
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                COUNT(i.usuario_id) AS total_inscritos
             FROM evento e
             LEFT JOIN inscripcion_evento i ON i.evento_id = e.evento_id
                 WHERE COALESCE(e.fecha_fin, e.fecha_inicio) >= :from_date
             GROUP BY
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                e.creado_en
             ORDER BY total_inscritos DESC, e.fecha_inicio ASC, e.creado_en DESC
             LIMIT {$limit}"
        );

        $statement->execute([
            'from_date' => $fromDate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listUpcomingEvents(string $fromDate, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));

        $statement = $this->connection->prepare(
            "SELECT
                evento_id,
                titulo,
                descripcion,
                fecha_inscripcion,
                fecha_inicio,
                fecha_fin,
                lugar,
                plazas,
                precio
             FROM evento
             WHERE COALESCE(fecha_fin, fecha_inicio) >= :from_date
             ORDER BY fecha_inicio ASC, titulo ASC
             LIMIT {$limit}"
        );

        $statement->execute([
            'from_date' => $fromDate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listUpcomingOpenRegistrationEvents(string $fromDate, int $limit = 4): array
    {
        $limit = max(1, min(10, $limit));

        // Incluye solo eventos futuros con inscripcion abierta y sin superar plazas.
        $statement = $this->connection->prepare(
            "SELECT
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                e.creado_en,
                COUNT(i.usuario_id) AS total_inscritos
             FROM evento e
             LEFT JOIN inscripcion_evento i ON i.evento_id = e.evento_id
             WHERE e.fecha_inicio >= :from_date
                             AND (e.fecha_inscripcion IS NULL OR e.fecha_inscripcion <= :from_date)
             GROUP BY
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                e.creado_en
             HAVING e.plazas IS NULL OR COUNT(i.usuario_id) < e.plazas
             ORDER BY e.fecha_inicio ASC, e.creado_en ASC
             LIMIT {$limit}"
        );

        $statement->execute([
            'from_date' => $fromDate,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findEventWithRegistrationsById(int $eventId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                e.creado_en,
                COUNT(i.usuario_id) AS total_inscritos
             FROM evento e
             LEFT JOIN inscripcion_evento i ON i.evento_id = e.evento_id
             WHERE e.evento_id = :evento_id
             GROUP BY
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inscripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.plazas,
                e.precio,
                e.creado_en'
        );

        $statement->execute([
            'evento_id' => $eventId,
        ]);

        $event = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($event) ? $event : null;
    }

    public function isUserEnrolledInEvent(int $userId, int $eventId): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1
             FROM inscripcion_evento
             WHERE usuario_id = :usuario_id
               AND evento_id = :evento_id
             LIMIT 1'
        );

        $statement->execute([
            'usuario_id' => $userId,
            'evento_id' => $eventId,
        ]);

        return (bool) $statement->fetchColumn();
    }

    public function enrollUserInEvent(int $userId, int $eventId): bool
    {
        $statement = $this->connection->prepare(
            'INSERT IGNORE INTO inscripcion_evento (usuario_id, evento_id)
             VALUES (:usuario_id, :evento_id)'
        );

        $statement->execute([
            'usuario_id' => $userId,
            'evento_id' => $eventId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function unenrollUserFromEvent(int $userId, int $eventId): bool
    {
        $statement = $this->connection->prepare(
            'DELETE FROM inscripcion_evento
             WHERE usuario_id = :usuario_id
               AND evento_id = :evento_id
             LIMIT 1'
        );

        $statement->execute([
            'usuario_id' => $userId,
            'evento_id' => $eventId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function listEnrolledEventsByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));

        $statement = $this->connection->prepare(
            "SELECT
                e.evento_id,
                e.titulo,
                e.descripcion,
                e.fecha_inicio,
                e.fecha_fin,
                e.lugar,
                e.precio,
                ie.fecha_registro
             FROM inscripcion_evento ie
             INNER JOIN evento e ON e.evento_id = ie.evento_id
             WHERE ie.usuario_id = :usuario_id
             ORDER BY e.fecha_inicio ASC, ie.fecha_registro DESC
             LIMIT {$limit}"
        );

        $statement->execute([
            'usuario_id' => $userId,
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createEvent(array $eventData): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO evento (
                titulo,
                descripcion,
                fecha_inscripcion,
                fecha_inicio,
                fecha_fin,
                lugar,
                plazas,
                precio
            ) VALUES (
                :titulo,
                :descripcion,
                :fecha_inscripcion,
                :fecha_inicio,
                :fecha_fin,
                :lugar,
                :plazas,
                :precio
            )'
        );

        $statement->execute([
            'titulo' => $eventData['titulo'],
            'descripcion' => $eventData['descripcion'],
            'fecha_inscripcion' => $eventData['fecha_inscripcion'],
            'fecha_inicio' => $eventData['fecha_inicio'],
            'fecha_fin' => $eventData['fecha_fin'],
            'lugar' => $eventData['lugar'],
            'plazas' => $eventData['plazas'],
            'precio' => $eventData['precio'],
        ]);

        return (int) $this->connection->lastInsertId();
    }
}
