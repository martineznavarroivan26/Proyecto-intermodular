<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class ModeratorModel
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? \ConectarDB::conexion();
        $this->ensureModeratorTables();
    }

    private function ensureModeratorTables(): void
    {
        // Auto-provisiona tablas auxiliares de moderacion para despliegues donde aun no existan.
        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS bloqueo_usuario (
                usuario_id INT UNSIGNED NOT NULL PRIMARY KEY,
                motivo VARCHAR(255) DEFAULT NULL,
                bloqueado_hasta DATETIME DEFAULT NULL,
                creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_bloqueo_usuario_usuario
                    FOREIGN KEY (usuario_id)
                    REFERENCES usuario(usuario_id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            ) ENGINE=InnoDB"
        );

        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS bloqueo_ip (
                ip VARCHAR(45) NOT NULL PRIMARY KEY,
                motivo VARCHAR(255) DEFAULT NULL,
                bloqueado_hasta DATETIME DEFAULT NULL,
                creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );

        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS home_carousel (
                carousel_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(150) NOT NULL,
                descripcion TEXT,
                imagen VARCHAR(255) NOT NULL,
                orden SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );

        $this->connection->exec(
            "CREATE TABLE IF NOT EXISTS home_noticia (
                noticia_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                titulo VARCHAR(150) NOT NULL,
                texto TEXT NOT NULL,
                imagen VARCHAR(255) NOT NULL,
                orden SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB"
        );
    }

    public function listUsersWithStatus(): array
    {
        $sql = "
            SELECT
                u.usuario_id,
                u.nombre_usuario,
                u.email,
                u.ip_registro,
                u.rol,
                b.motivo AS bloqueo_motivo,
                b.bloqueado_hasta,
                CASE
                    WHEN b.usuario_id IS NOT NULL
                         AND (b.bloqueado_hasta IS NULL OR b.bloqueado_hasta >= NOW())
                    THEN 1 ELSE 0
                END AS bloqueado
            FROM usuario u
            LEFT JOIN bloqueo_usuario b ON b.usuario_id = u.usuario_id
            ORDER BY u.creado_en DESC
        ";

        $statement = $this->connection->query($sql);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function blockUser(int $userId, ?string $reason, ?string $blockedUntil): void
    {
        // UPSERT para bloquear y actualizar motivo/fecha con una sola sentencia.
        $statement = $this->connection->prepare(
            "INSERT INTO bloqueo_usuario (usuario_id, motivo, bloqueado_hasta)
             VALUES (:usuario_id, :motivo, :bloqueado_hasta)
             ON DUPLICATE KEY UPDATE
                motivo = VALUES(motivo),
                bloqueado_hasta = VALUES(bloqueado_hasta),
                creado_en = CURRENT_TIMESTAMP"
        );

        $statement->execute([
            'usuario_id' => $userId,
            'motivo' => $reason,
            'bloqueado_hasta' => $blockedUntil,
        ]);
    }

    public function unblockUser(int $userId): void
    {
        $statement = $this->connection->prepare('DELETE FROM bloqueo_usuario WHERE usuario_id = :usuario_id');
        $statement->execute(['usuario_id' => $userId]);
    }

    public function updateUserRole(int $userId, string $newRole): void
    {
        $statement = $this->connection->prepare(
            'UPDATE usuario
             SET rol = :rol
             WHERE usuario_id = :usuario_id'
        );

        $statement->execute([
            'rol' => $newRole,
            'usuario_id' => $userId,
        ]);
    }

    public function listIpBlocks(): array
    {
        $statement = $this->connection->query(
            'SELECT ip, motivo, bloqueado_hasta, creado_en FROM bloqueo_ip ORDER BY creado_en DESC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function blockIp(string $ip, ?string $reason, ?string $blockedUntil): void
    {
        $statement = $this->connection->prepare(
            "INSERT INTO bloqueo_ip (ip, motivo, bloqueado_hasta)
             VALUES (:ip, :motivo, :bloqueado_hasta)
             ON DUPLICATE KEY UPDATE
                motivo = VALUES(motivo),
                bloqueado_hasta = VALUES(bloqueado_hasta),
                creado_en = CURRENT_TIMESTAMP"
        );

        $statement->execute([
            'ip' => $ip,
            'motivo' => $reason,
            'bloqueado_hasta' => $blockedUntil,
        ]);
    }

    public function unblockIp(string $ip): void
    {
        $statement = $this->connection->prepare('DELETE FROM bloqueo_ip WHERE ip = :ip');
        $statement->execute(['ip' => $ip]);
    }

    public function listPostsForModeration(int $limit = 120): array
    {
        $limit = max(1, min(300, $limit));

        $statement = $this->connection->query(
            "SELECT p.post_id, p.titulo, p.categoria, p.creado_en, u.nombre_usuario
             FROM post p
             INNER JOIN usuario u ON u.usuario_id = p.usuario_id
             ORDER BY p.creado_en DESC
             LIMIT {$limit}"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deletePost(int $postId): void
    {
        $statement = $this->connection->prepare('DELETE FROM post WHERE post_id = :post_id');
        $statement->execute(['post_id' => $postId]);
    }

    public function listCommentsForModeration(int $limit = 200): array
    {
        $limit = max(1, min(400, $limit));

        $statement = $this->connection->query(
            "SELECT c.comentario_id, c.post_id, c.contenido, c.creado_en, u.nombre_usuario
             FROM comentario c
             INNER JOIN usuario u ON u.usuario_id = c.usuario_id
             ORDER BY c.creado_en DESC
             LIMIT {$limit}"
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteComment(int $commentId): void
    {
        $statement = $this->connection->prepare('DELETE FROM comentario WHERE comentario_id = :comentario_id');
        $statement->execute(['comentario_id' => $commentId]);
    }

    public function listEventsForModeration(): array
    {
        $statement = $this->connection->query(
            'SELECT evento_id, titulo, descripcion, fecha_inscripcion, fecha_inicio, fecha_fin, lugar, plazas, precio
             FROM evento
             ORDER BY fecha_inicio DESC, creado_en DESC'
        );

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

    public function updateEvent(int $eventId, array $eventData): void
    {
        $statement = $this->connection->prepare(
            'UPDATE evento SET
                titulo = :titulo,
                descripcion = :descripcion,
                fecha_inscripcion = :fecha_inscripcion,
                fecha_inicio = :fecha_inicio,
                fecha_fin = :fecha_fin,
                lugar = :lugar,
                plazas = :plazas,
                precio = :precio
             WHERE evento_id = :evento_id'
        );

        $statement->execute([
            'evento_id' => $eventId,
            'titulo' => $eventData['titulo'],
            'descripcion' => $eventData['descripcion'],
            'fecha_inscripcion' => $eventData['fecha_inscripcion'],
            'fecha_inicio' => $eventData['fecha_inicio'],
            'fecha_fin' => $eventData['fecha_fin'],
            'lugar' => $eventData['lugar'],
            'plazas' => $eventData['plazas'],
            'precio' => $eventData['precio'],
        ]);
    }

    public function listCarouselItems(): array
    {
        $statement = $this->connection->query(
            'SELECT carousel_id, titulo, descripcion, imagen, orden, activo
             FROM home_carousel
             ORDER BY orden ASC, carousel_id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listActiveCarouselItems(): array
    {
        $statement = $this->connection->query(
            'SELECT carousel_id, titulo, descripcion, imagen
             FROM home_carousel
             WHERE activo = 1
             ORDER BY orden ASC, carousel_id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createCarouselItem(array $item): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO home_carousel (titulo, descripcion, imagen, orden, activo)
             VALUES (:titulo, :descripcion, :imagen, :orden, :activo)'
        );

        $statement->execute([
            'titulo' => $item['titulo'],
            'descripcion' => $item['descripcion'],
            'imagen' => $item['imagen'],
            'orden' => $item['orden'],
            'activo' => $item['activo'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateCarouselItem(int $carouselId, array $item): void
    {
        $statement = $this->connection->prepare(
            'UPDATE home_carousel
             SET titulo = :titulo,
                 descripcion = :descripcion,
                 imagen = :imagen,
                 orden = :orden,
                 activo = :activo
             WHERE carousel_id = :carousel_id'
        );

        $statement->execute([
            'carousel_id' => $carouselId,
            'titulo' => $item['titulo'],
            'descripcion' => $item['descripcion'],
            'imagen' => $item['imagen'],
            'orden' => $item['orden'],
            'activo' => $item['activo'],
        ]);
    }

    public function listNewsItems(): array
    {
        $statement = $this->connection->query(
            'SELECT noticia_id, titulo, texto, imagen, orden, activo
             FROM home_noticia
             ORDER BY orden ASC, noticia_id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listActiveNewsItems(): array
    {
        $statement = $this->connection->query(
            'SELECT noticia_id, titulo, texto, imagen
             FROM home_noticia
             WHERE activo = 1
             ORDER BY orden ASC, noticia_id ASC'
        );

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createNewsItem(array $item): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO home_noticia (titulo, texto, imagen, orden, activo)
             VALUES (:titulo, :texto, :imagen, :orden, :activo)'
        );

        $statement->execute([
            'titulo' => $item['titulo'],
            'texto' => $item['texto'],
            'imagen' => $item['imagen'],
            'orden' => $item['orden'],
            'activo' => $item['activo'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateNewsItem(int $newsId, array $item): void
    {
        $statement = $this->connection->prepare(
            'UPDATE home_noticia
             SET titulo = :titulo,
                 texto = :texto,
                 imagen = :imagen,
                 orden = :orden,
                 activo = :activo
             WHERE noticia_id = :noticia_id'
        );

        $statement->execute([
            'noticia_id' => $newsId,
            'titulo' => $item['titulo'],
            'texto' => $item['texto'],
            'imagen' => $item['imagen'],
            'orden' => $item['orden'],
            'activo' => $item['activo'],
        ]);
    }

    public function getCarouselItemById(int $carouselId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT carousel_id, titulo, descripcion, imagen, orden, activo
             FROM home_carousel
             WHERE carousel_id = :carousel_id'
        );

        $statement->execute(['carousel_id' => $carouselId]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function getNewsItemById(int $newsId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT noticia_id, titulo, texto, imagen, orden, activo
             FROM home_noticia
             WHERE noticia_id = :noticia_id'
        );

        $statement->execute(['noticia_id' => $newsId]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    public function deleteEvent(int $eventId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM evento WHERE evento_id = :evento_id'
        );

        $statement->execute(['evento_id' => $eventId]);
    }

    public function deleteCarouselItem(int $carouselId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM home_carousel WHERE carousel_id = :carousel_id'
        );

        $statement->execute(['carousel_id' => $carouselId]);
    }

    public function deleteNewsItem(int $newsId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM home_noticia WHERE noticia_id = :noticia_id'
        );

        $statement->execute(['noticia_id' => $newsId]);
    }
}
