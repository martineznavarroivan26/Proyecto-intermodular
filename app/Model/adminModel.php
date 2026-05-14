<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class AdminModel
{
    private PDO $connection;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? \ConectarDB::conexion();
    }

    // SAGAS
    public function listSagas(): array
    {
        $statement = $this->connection->query(
            'SELECT saga_id, nombre, descripcion FROM saga ORDER BY nombre ASC'
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createSaga(string $nombre, ?string $descripcion): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO saga (nombre, descripcion) VALUES (:nombre, :descripcion)'
        );
        $statement->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function updateSaga(int $sagaId, string $nombre, ?string $descripcion): void
    {
        $statement = $this->connection->prepare(
            'UPDATE saga SET nombre = :nombre, descripcion = :descripcion WHERE saga_id = :saga_id'
        );
        $statement->execute([
            'saga_id' => $sagaId,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
        ]);
    }

    public function deleteSaga(int $sagaId): void
    {
        $statement = $this->connection->prepare('DELETE FROM saga WHERE saga_id = :saga_id');
        $statement->execute(['saga_id' => $sagaId]);
    }

    // PLATAFORMAS
    public function listPlataformas(): array
    {
        $statement = $this->connection->query(
            'SELECT plataforma_id, nombre FROM plataformas ORDER BY nombre ASC'
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createPlataforma(string $nombre): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO plataformas (nombre) VALUES (:nombre)'
        );
        $statement->execute(['nombre' => $nombre]);
        return (int) $this->connection->lastInsertId();
    }

    public function updatePlataforma(int $plataformaId, string $nombre): void
    {
        $statement = $this->connection->prepare(
            'UPDATE plataformas SET nombre = :nombre WHERE plataforma_id = :plataforma_id'
        );
        $statement->execute([
            'plataforma_id' => $plataformaId,
            'nombre' => $nombre,
        ]);
    }

    public function deletePlataforma(int $plataformaId): void
    {
        $statement = $this->connection->prepare('DELETE FROM plataformas WHERE plataforma_id = :plataforma_id');
        $statement->execute(['plataforma_id' => $plataformaId]);
    }

    // JUEGOS
    public function listJuegos(): array
    {
        $statement = $this->connection->query(
            'SELECT j.juego_id, j.titulo, j.saga_id, j.plataforma_id, j.ano_lanzamiento, 
                    j.descripcion, j.imagen, s.nombre as saga_nombre, p.nombre as plataforma_nombre
             FROM juego j
             LEFT JOIN saga s ON j.saga_id = s.saga_id
             LEFT JOIN plataformas p ON j.plataforma_id = p.plataforma_id
             ORDER BY j.titulo ASC'
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createJuego(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO juego (titulo, saga_id, plataforma_id, ano_lanzamiento, descripcion, imagen)
             VALUES (:titulo, :saga_id, :plataforma_id, :ano_lanzamiento, :descripcion, :imagen)'
        );
        $statement->execute([
            'titulo' => $data['titulo'],
            'saga_id' => $data['saga_id'] ?? null,
            'plataforma_id' => $data['plataforma_id'] ?? null,
            'ano_lanzamiento' => $data['ano_lanzamiento'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'] ?? null,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function updateJuego(int $juegoId, array $data): void
    {
        $statement = $this->connection->prepare(
            'UPDATE juego SET titulo = :titulo, saga_id = :saga_id, plataforma_id = :plataforma_id,
                            ano_lanzamiento = :ano_lanzamiento, descripcion = :descripcion, imagen = :imagen
             WHERE juego_id = :juego_id'
        );
        $statement->execute([
            'juego_id' => $juegoId,
            'titulo' => $data['titulo'],
            'saga_id' => $data['saga_id'] ?? null,
            'plataforma_id' => $data['plataforma_id'] ?? null,
            'ano_lanzamiento' => $data['ano_lanzamiento'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'imagen' => $data['imagen'] ?? null,
        ]);
    }

    public function deleteJuego(int $juegoId): void
    {
        $statement = $this->connection->prepare('DELETE FROM juego WHERE juego_id = :juego_id');
        $statement->execute(['juego_id' => $juegoId]);
    }

    public function getJuegoById(int $juegoId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT juego_id, titulo, saga_id, plataforma_id, ano_lanzamiento, descripcion, imagen
             FROM juego WHERE juego_id = :juego_id'
        );
        $statement->execute(['juego_id' => $juegoId]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    // PERSONAJES
    public function listPersonajes(): array
    {
        $statement = $this->connection->query(
            'SELECT p.personaje_id, p.juego_id, p.nombre, p.posicion, p.elemento, p.descripcion,
                    p.estilo_juego, p.imagen, p.pe, p.pt, p.tiro, p.control, p.defensa, 
                    p.rapidez, p.fisico, p.aguante, j.titulo as juego_nombre
             FROM personaje p
             LEFT JOIN juego j ON p.juego_id = j.juego_id
             ORDER BY j.titulo ASC, p.nombre ASC'
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createPersonaje(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO personaje (juego_id, nombre, posicion, elemento, descripcion, estilo_juego, 
                                    imagen, pe, pt, tiro, control, defensa, rapidez, fisico, aguante)
             VALUES (:juego_id, :nombre, :posicion, :elemento, :descripcion, :estilo_juego,
                     :imagen, :pe, :pt, :tiro, :control, :defensa, :rapidez, :fisico, :aguante)'
        );
        $statement->execute([
            'juego_id' => $data['juego_id'],
            'nombre' => $data['nombre'],
            'posicion' => $data['posicion'],
            'elemento' => $data['elemento'],
            'descripcion' => $data['descripcion'] ?? null,
            'estilo_juego' => $data['estilo_juego'] ?? null,
            'imagen' => $data['imagen'] ?? null,
            'pe' => $data['pe'] ?? null,
            'pt' => $data['pt'] ?? null,
            'tiro' => $data['tiro'] ?? null,
            'control' => $data['control'] ?? null,
            'defensa' => $data['defensa'] ?? null,
            'rapidez' => $data['rapidez'] ?? null,
            'fisico' => $data['fisico'] ?? null,
            'aguante' => $data['aguante'] ?? null,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function updatePersonaje(int $personajeId, array $data): void
    {
        $statement = $this->connection->prepare(
            'UPDATE personaje SET juego_id = :juego_id, nombre = :nombre, posicion = :posicion,
                                elemento = :elemento, descripcion = :descripcion, estilo_juego = :estilo_juego,
                                imagen = :imagen, pe = :pe, pt = :pt, tiro = :tiro, control = :control,
                                defensa = :defensa, rapidez = :rapidez, fisico = :fisico, aguante = :aguante
             WHERE personaje_id = :personaje_id'
        );
        $statement->execute([
            'personaje_id' => $personajeId,
            'juego_id' => $data['juego_id'],
            'nombre' => $data['nombre'],
            'posicion' => $data['posicion'],
            'elemento' => $data['elemento'],
            'descripcion' => $data['descripcion'] ?? null,
            'estilo_juego' => $data['estilo_juego'] ?? null,
            'imagen' => $data['imagen'] ?? null,
            'pe' => $data['pe'] ?? null,
            'pt' => $data['pt'] ?? null,
            'tiro' => $data['tiro'] ?? null,
            'control' => $data['control'] ?? null,
            'defensa' => $data['defensa'] ?? null,
            'rapidez' => $data['rapidez'] ?? null,
            'fisico' => $data['fisico'] ?? null,
            'aguante' => $data['aguante'] ?? null,
        ]);
    }

    public function deletePersonaje(int $personajeId): void
    {
        $statement = $this->connection->prepare('DELETE FROM personaje WHERE personaje_id = :personaje_id');
        $statement->execute(['personaje_id' => $personajeId]);
    }

    public function getPersonajeById(int $personajeId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT personaje_id, juego_id, nombre, posicion, elemento, descripcion, estilo_juego, 
                    imagen, pe, pt, tiro, control, defensa, rapidez, fisico, aguante
             FROM personaje WHERE personaje_id = :personaje_id'
        );
        $statement->execute(['personaje_id' => $personajeId]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    // EQUIPOS
    public function listEquipos(): array
    {
        $statement = $this->connection->query(
            'SELECT e.equipo_id, e.juego_id, e.nombre, e.descripcion, e.escudo, e.entrenador, 
                    e.uniforme, e.estilo_juego, j.titulo as juego_nombre
             FROM equipo e
             LEFT JOIN juego j ON e.juego_id = j.juego_id
             ORDER BY j.titulo ASC, e.nombre ASC'
        );
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createEquipo(array $data): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO equipo (juego_id, nombre, descripcion, escudo, entrenador, uniforme, estilo_juego)
             VALUES (:juego_id, :nombre, :descripcion, :escudo, :entrenador, :uniforme, :estilo_juego)'
        );
        $statement->execute([
            'juego_id' => $data['juego_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'escudo' => $data['escudo'] ?? null,
            'entrenador' => $data['entrenador'] ?? null,
            'uniforme' => $data['uniforme'] ?? null,
            'estilo_juego' => $data['estilo_juego'] ?? null,
        ]);
        return (int) $this->connection->lastInsertId();
    }

    public function updateEquipo(int $equipoId, array $data): void
    {
        $statement = $this->connection->prepare(
            'UPDATE equipo SET juego_id = :juego_id, nombre = :nombre, descripcion = :descripcion,
                            escudo = :escudo, entrenador = :entrenador, uniforme = :uniforme,
                            estilo_juego = :estilo_juego
             WHERE equipo_id = :equipo_id'
        );
        $statement->execute([
            'equipo_id' => $equipoId,
            'juego_id' => $data['juego_id'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'escudo' => $data['escudo'] ?? null,
            'entrenador' => $data['entrenador'] ?? null,
            'uniforme' => $data['uniforme'] ?? null,
            'estilo_juego' => $data['estilo_juego'] ?? null,
        ]);
    }

    public function deleteEquipo(int $equipoId): void
    {
        $statement = $this->connection->prepare('DELETE FROM equipo WHERE equipo_id = :equipo_id');
        $statement->execute(['equipo_id' => $equipoId]);
    }

    public function getEquipoById(int $equipoId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT equipo_id, juego_id, nombre, descripcion, escudo, entrenador, uniforme, estilo_juego
             FROM equipo WHERE equipo_id = :equipo_id'
        );
        $statement->execute(['equipo_id' => $equipoId]);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }
}
