<?php
declare(strict_types=1);

namespace App\Model;

use PDO;

require_once __DIR__ . '/conexion.php';

class JuegoModel
{
    private PDO $connection;
    private array $objectDocumentsByGame = [];
    private ?array $routeToTitleMap = null;
    private ?array $titleToRouteMap = null;

    public function __construct(?PDO $connection = null)
    {
        $this->connection = $connection ?? \ConectarDB::conexion();
    }

    public function listGamesGroupedBySaga(): array
    {
        $stmt = $this->connection->query(
            'SELECT j.juego_id, j.titulo, j.descripcion, j.imagen, j.ano_lanzamiento,
                    COALESCE(s.nombre, "Otros") AS saga_nombre
             FROM juego j
             LEFT JOIN saga s ON s.saga_id = j.saga_id
             ORDER BY saga_nombre ASC, j.ano_lanzamiento ASC, j.titulo ASC'
        );
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $grouped = [];

        foreach ($rows as $row) {
            $title = (string) ($row['titulo'] ?? 'Juego');
            $routeKey = $this->resolveRouteKey($title);
            if ($routeKey === null) {
                continue;
            }

            $sagaName = (string) ($row['saga_nombre'] ?? 'Otros');
            if (!isset($grouped[$sagaName])) {
                $grouped[$sagaName] = [];
            }

            $image = (string) ($row['imagen'] ?? 'uploads/imagenes/juegos/Inazuma_eleven_caratula.webp');
            $subtitle = (string) ($row['descripcion'] ?? '');

            $grouped[$sagaName][] = [
                'route' => $routeKey,
                'title' => $title,
                'subtitle' => $subtitle,
                'image' => $image,
            ];
        }

        return $grouped;
    }

    public function listMenuGames(): array
    {
        $stmt = $this->connection->query(
            'SELECT titulo, ano_lanzamiento
             FROM juego
             ORDER BY ano_lanzamiento ASC, titulo ASC'
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $menuItems = [];
        $usedRoutes = [];

        foreach ($rows as $row) {
            $title = (string) ($row['titulo'] ?? '');
            if ($title === '') {
                continue;
            }

            $routeKey = $this->resolveRouteKey($title);
            if ($routeKey === null || isset($usedRoutes[$routeKey])) {
                continue;
            }

            $menuItems[] = [
                'route' => $routeKey,
                'title' => $title,
            ];
            $usedRoutes[$routeKey] = true;
        }

        return $menuItems;
    }

    public function getGameWikiByRoute(string $routeKey): ?array
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return null;
        }

        $stmt = $this->connection->prepare(
            'SELECT j.juego_id, j.titulo, j.descripcion, j.imagen, j.ano_lanzamiento,
                    COALESCE(s.nombre, "") AS saga_nombre,
                    p.nombre AS plataforma_nombre
             FROM juego j
             LEFT JOIN saga s ON s.saga_id = j.saga_id
             LEFT JOIN plataformas p ON p.plataforma_id = j.plataforma_id
             WHERE j.titulo = :titulo'
        );
           $stmt->execute([':titulo' => $title]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $release = (string) ($row['ano_lanzamiento'] ?? '');
        $platforms = (string) ($row['plataforma_nombre'] ?? '');

        return [
            'game_id'      => (int) ($row['juego_id'] ?? 0),
            'route'        => $routeKey,
            'title'        => (string) ($row['titulo'] ?? ''),
            'subtitle'     => (string) ($row['descripcion'] ?? ''),
            'image'        => (string) ($row['imagen'] ?? ''),
            'saga'         => (string) ($row['saga_nombre'] ?? ''),
            'release'      => $release,
                'platforms'    => $platforms,
            'genre'        => 'RPG deportivo',
            'summary'      => (string) ($row['descripcion'] ?? ''),
            'chapters'     => $this->getChaptersByRouteKey($routeKey),
                'teams'        => $this->getTeamsByRouteKey($routeKey),
                'characters'   => $this->getCharactersByRouteKey($routeKey),
                'supertecnicas' => $this->getSupertecnicasByRouteKey($routeKey),
                'objects'      => $this->getObjectsByRouteKey($routeKey),
            'secrets'      => [],
        ];
    }

    public function getGameIdByRouteKey(string $routeKey): ?int
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return null;
        }

        $stmt = $this->connection->prepare('SELECT juego_id FROM juego WHERE titulo = :titulo LIMIT 1');
        $stmt->execute([':titulo' => $title]);
        $gameId = $stmt->fetchColumn();

        if ($gameId === false) {
            return null;
        }

        return (int) $gameId;
    }

    private function getChaptersByRouteKey(string $routeKey): array
    {
            $title = $this->getTitleByRouteKey($routeKey);
            if ($title === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT c.capitulo_id, c.numero, c.titulo, c.descripcion
             FROM capitulo c
             INNER JOIN juego j ON j.juego_id = c.juego_id
             WHERE j.titulo = :titulo
             ORDER BY c.numero ASC'
        );
           $stmt->execute([':titulo' => $title]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row): array => [
            'id' => (int) ($row['capitulo_id'] ?? 0),
            'number' => (int) ($row['numero'] ?? 0),
            'titleRaw' => (string) ($row['titulo'] ?? ''),
            'descriptionRaw' => (string) ($row['descripcion'] ?? ''),
            'title' => 'Capitulo ' . (int) $row['numero'] . ': ' . (string) $row['titulo'],
            'desc'  => (string) ($row['descripcion'] ?? ''),
        ], $rows);
    }

    private function getTeamsByRouteKey(string $routeKey): array
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return [];
        }

        $gameId = $this->getGameIdByRouteKey($routeKey);
        if ($gameId === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT e.equipo_id, e.nombre, e.descripcion, e.escudo, e.entrenador, e.uniforme, e.estilo_juego
             FROM equipo e
             INNER JOIN juego j ON j.juego_id = e.juego_id
             WHERE j.titulo = :titulo
             ORDER BY e.nombre ASC'
        );
        $stmt->execute([':titulo' => $title]);
        $teams = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $membersStmt = $this->connection->prepare(
            'SELECT COALESCE(GROUP_CONCAT(p.personaje_id ORDER BY p.personaje_id SEPARATOR ","), "") AS ids,
                    COALESCE(GROUP_CONCAT(p.nombre ORDER BY p.personaje_id SEPARATOR "||"), "") AS names
             FROM personaje_equipo pe
             INNER JOIN personaje p ON p.personaje_id = pe.personaje_id
             WHERE pe.equipo_id = :equipo_id'
        );

        return array_map(function (array $team) use ($membersStmt, $gameId): array {
            // Recupera miembros del equipo en una sola consulta agregada para no multiplicar llamadas.
            $teamId = (int) ($team['equipo_id'] ?? 0);
            $membersStmt->execute([':equipo_id' => $teamId]);
            $members = $membersStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $characterIds = $this->parseCsvInts((string) ($members['ids'] ?? ''));
            $characterNames = $this->parseCsvStrings((string) ($members['names'] ?? ''), '||');
            $captain = $characterNames[0] ?? '';

            $relatedObjectIds = $this->findRelatedObjectIdsByGameIdAndKeywords(
                $gameId,
                array_merge([(string) ($team['nombre'] ?? '')], $characterNames)
            );

            $stats = [];
            if ($captain !== '') {
                $stats[] = ['label' => 'Capitan', 'value' => $captain];
            }
            if (!empty($team['entrenador'])) {
                $stats[] = ['label' => 'Entrenador', 'value' => (string) $team['entrenador']];
            }
            if (!empty($team['uniforme'])) {
                $stats[] = ['label' => 'Uniforme', 'value' => (string) $team['uniforme']];
            }
            if (!empty($team['estilo_juego'])) {
                $stats[] = ['label' => 'Estilo de Juego', 'value' => (string) $team['estilo_juego']];
            }

            return [
                'id'          => $teamId,
                'name'        => (string) ($team['nombre'] ?? ''),
                'description' => (string) ($team['descripcion'] ?? ''),
                'image'       => (string) ($team['escudo'] ?? ''),
                'coach'       => (string) ($team['entrenador'] ?? ''),
                'uniform'     => (string) ($team['uniforme'] ?? ''),
                'style'       => (string) ($team['estilo_juego'] ?? ''),
                'stats'       => $stats,
                'history'     => (string) ($team['descripcion'] ?? ''),
                'character_ids' => $characterIds,
                'character_names' => $characterNames,
                'object_ids' => $relatedObjectIds,
            ];
        }, $teams);
    }

    private function getCharactersByRouteKey(string $routeKey): array
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return [];
        }

        $gameId = $this->getGameIdByRouteKey($routeKey);
        if ($gameId === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT p.personaje_id, p.nombre, p.posicion, p.elemento, p.descripcion, p.estilo_juego, p.imagen,
                p.pe, p.pt, p.tiro, p.control, p.defensa, p.rapidez, p.fisico, p.aguante,
                    COALESCE(GROUP_CONCAT(DISTINCT CONCAT(e.equipo_id, "::", e.nombre) ORDER BY e.nombre SEPARATOR "||"), "") AS team_pairs,
                    COALESCE(GROUP_CONCAT(DISTINCT CONCAT(st.supertecnica_id, "::", st.nombre) ORDER BY st.nombre SEPARATOR "||"), "") AS technique_pairs
             FROM personaje p
             INNER JOIN juego j ON j.juego_id = p.juego_id
             LEFT JOIN personaje_equipo pe ON pe.personaje_id = p.personaje_id
             LEFT JOIN equipo e ON e.equipo_id = pe.equipo_id
             LEFT JOIN tecnicas_personaje tp ON tp.personaje_id = p.personaje_id
             LEFT JOIN supertecnica st ON st.supertecnica_id = tp.supertecnica_id
             WHERE j.titulo = :titulo
             GROUP BY p.personaje_id, p.nombre, p.posicion, p.elemento, p.descripcion, p.estilo_juego, p.imagen,
                      p.pe, p.pt, p.tiro, p.control, p.defensa, p.rapidez, p.fisico, p.aguante
             ORDER BY p.nombre ASC'
        );
        $stmt->execute([':titulo' => $title]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $positionMap = [
            'POR' => 'Portero',
            'DF' => 'Defensa',
            'MD' => 'Mediocampista',
            'DL' => 'Delantero',
        ];

        $elementMap = [
            'Montania' => 'Montaña',
        ];

        return array_map(function (array $row) use ($positionMap, $elementMap, $gameId): array {
            $positionCode = (string) ($row['posicion'] ?? '');
            $position = $positionMap[$positionCode] ?? $positionCode;
            $elementRaw = (string) ($row['elemento'] ?? '');
            $element = $elementMap[$elementRaw] ?? $elementRaw;

            $teamPairs = $this->parseIdNamePairs((string) ($row['team_pairs'] ?? ''));
            $techniquePairs = $this->parseIdNamePairs((string) ($row['technique_pairs'] ?? ''));

            $teamIds = array_map(static fn(array $pair): int => $pair['id'], $teamPairs);
            $teamNames = array_map(static fn(array $pair): string => $pair['name'], $teamPairs);
            $techniqueIds = array_map(static fn(array $pair): int => $pair['id'], $techniquePairs);
            $techniqueNames = array_map(static fn(array $pair): string => $pair['name'], $techniquePairs);

            $primaryTeamName = $teamNames[0] ?? 'Sin equipo';
            $primaryTeamId = $teamIds[0] ?? 0;

            $relatedObjectIds = $this->findRelatedObjectIdsByGameIdAndKeywords(
                $gameId,
                array_merge([(string) ($row['nombre'] ?? '')], $teamNames, $techniqueNames)
            );

            return [
                'id'    => (int) ($row['personaje_id'] ?? 0),
                'name'  => (string) ($row['nombre'] ?? ''),
                'role'  => trim($position . ' de ' . $primaryTeamName),
                'image' => (string) ($row['imagen'] ?? ''),
                'team_id' => $primaryTeamId,
                'position_code' => $positionCode,
                'element_code' => $elementRaw,
                'description' => (string) ($row['descripcion'] ?? ''),
                'style' => (string) ($row['estilo_juego'] ?? ''),
                'team_name' => $primaryTeamName,
                'team_ids' => $teamIds,
                'team_names' => $teamNames,
                'technique_ids' => $techniqueIds,
                'technique_names' => $techniqueNames,
                'object_ids' => $relatedObjectIds,
                'pe' => (string) ($row['pe'] ?? ''),
                'pt' => (string) ($row['pt'] ?? ''),
                'tiro' => (string) ($row['tiro'] ?? ''),
                'control' => (string) ($row['control'] ?? ''),
                'defensa' => (string) ($row['defensa'] ?? ''),
                'rapidez' => (string) ($row['rapidez'] ?? ''),
                'fisico' => (string) ($row['fisico'] ?? ''),
                'aguante' => (string) ($row['aguante'] ?? ''),
                'stats' => [
                    ['label' => 'Equipo', 'value' => $teamNames === [] ? 'Sin equipo' : implode(', ', $teamNames)],
                    ['label' => 'Posicion', 'value' => $position],
                    ['label' => 'Elemento', 'value' => $element],
                    ['label' => 'Estilo de juego', 'value' => (string) ($row['estilo_juego'] ?? '')],
                    ['label' => 'PE', 'value' => (string) ($row['pe'] ?? '')],
                    ['label' => 'PT', 'value' => (string) ($row['pt'] ?? '')],
                    ['label' => 'Tiro', 'value' => (string) ($row['tiro'] ?? '')],
                    ['label' => 'Control', 'value' => (string) ($row['control'] ?? '')],
                    ['label' => 'Defensa', 'value' => (string) ($row['defensa'] ?? '')],
                    ['label' => 'Rapidez', 'value' => (string) ($row['rapidez'] ?? '')],
                    ['label' => 'Fisico', 'value' => (string) ($row['fisico'] ?? '')],
                    ['label' => 'Aguante', 'value' => (string) ($row['aguante'] ?? '')],
                ],
            ];
        }, $rows);
    }

    private function getSupertecnicasByRouteKey(string $routeKey): array
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT st.supertecnica_id, st.nombre, st.tipo, st.poder, st.coste_pe, st.nivel_desbloqueo,
                st.descripcion, st.elemento, st.video,
                    COALESCE(GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ", "), "") AS usuarios,
                    COALESCE(GROUP_CONCAT(DISTINCT CONCAT(p.personaje_id, "::", p.nombre) ORDER BY p.nombre SEPARATOR "||"), "") AS character_pairs
             FROM supertecnica st
             INNER JOIN juego j ON j.juego_id = st.juego_id
             LEFT JOIN tecnicas_personaje tp ON tp.supertecnica_id = st.supertecnica_id
             LEFT JOIN personaje p ON p.personaje_id = tp.personaje_id
             WHERE j.titulo = :titulo
             GROUP BY st.supertecnica_id, st.nombre, st.tipo, st.poder, st.coste_pe, st.nivel_desbloqueo,
                  st.descripcion, st.elemento, st.video
             ORDER BY st.nombre ASC'
        );
        $stmt->execute([':titulo' => $title]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $typeMap = [
            'POR' => 'Parada',
            'DF' => 'Defensa',
            'BLQ' => 'Bloqueo',
            'REG' => 'Regate',
            'TIR' => 'Tiro',
            'LAR' => 'Larga',
            'CAD' => 'Cadena',
        ];

        $elementMap = [
            'Montania' => 'Montaña',
        ];

        return array_map(function (array $row) use ($typeMap, $elementMap): array {
            $typeCode = (string) ($row['tipo'] ?? '');
            $type = $typeMap[$typeCode] ?? $typeCode;
            $elementRaw = (string) ($row['elemento'] ?? '');
            $element = $elementMap[$elementRaw] ?? $elementRaw;
            $characterPairs = $this->parseIdNamePairs((string) ($row['character_pairs'] ?? ''));
            $characterIds = array_map(static fn(array $pair): int => $pair['id'], $characterPairs);
            $characterNames = array_map(static fn(array $pair): string => $pair['name'], $characterPairs);

            return [
                'id'          => (int) ($row['supertecnica_id'] ?? 0),
                'name'        => (string) ($row['nombre'] ?? ''),
                'description' => (string) ($row['descripcion'] ?? ''),
                'image'       => (string) ($row['video'] ?? ''),
                'type_code'   => $typeCode,
                'element_code' => $elementRaw,
                'power'       => (string) ($row['poder'] ?? ''),
                'cost'        => (string) ($row['coste_pe'] ?? ''),
                'unlock'      => (string) ($row['nivel_desbloqueo'] ?? ''),
                'character_ids' => $characterIds,
                'character_names' => $characterNames,
                'stats'       => [
                    ['label' => 'Tipo', 'value' => $type],
                    ['label' => 'Elemento', 'value' => $element],
                    ['label' => 'Usuario Principal', 'value' => (string) ($row['usuarios'] ?? '')],
                    ['label' => 'Poder', 'value' => (string) ($row['poder'] ?? '')],
                    ['label' => 'Coste de PE', 'value' => (string) ($row['coste_pe'] ?? '')],
                    ['label' => 'Nivel de Desbloqueo', 'value' => (string) ($row['nivel_desbloqueo'] ?? '')],
                ],
                'history'     => (string) ($row['descripcion'] ?? ''),
            ];
        }, $rows);
    }

    private function getObjectsByRouteKey(string $routeKey): array
    {
        $title = $this->getTitleByRouteKey($routeKey);
        if ($title === null) {
            return [];
        }

        $stmt = $this->connection->prepare(
            'SELECT c.coleccionable_id, c.nombre, c.tipo, c.descripcion, c.rareza, c.categoria, c.localizaciones, c.curiosidades, c.tecnicas, c.imagen
             FROM coleccionable c
             INNER JOIN juego j ON j.juego_id = c.juego_id
             WHERE j.titulo = :titulo
             ORDER BY c.nombre ASC'
        );
        $stmt->execute([':titulo' => $title]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static fn(array $row): array => [
            'id'          => (int) ($row['coleccionable_id'] ?? 0),
            'name'        => (string) ($row['nombre'] ?? ''),
            'type'        => (string) ($row['tipo'] ?? ''),
            'description' => (string) ($row['descripcion'] ?? ''),
            'image'       => (string) ($row['imagen'] ?? ''),
            'rarity'      => (string) ($row['rareza'] ?? ''),
            'category'    => (string) ($row['categoria'] ?? ''),
            'locationsRaw' => (string) ($row['localizaciones'] ?? ''),
            'notesRaw'    => (string) ($row['curiosidades'] ?? ''),
            'techniquesRaw' => (string) ($row['tecnicas'] ?? ''),
            'stats'       => [
                ['label' => 'Rareza', 'value' => (string) ($row['rareza'] ?? '')],
                ['label' => 'Categoria', 'value' => (string) ($row['categoria'] ?? ($row['tipo'] ?? ''))],
            ],
            'locations'   => (string) ($row['localizaciones'] ?? ''),
            'notes'       => (string) ($row['curiosidades'] ?? ''),
            'techniques'  => (string) ($row['tecnicas'] ?? ''),
        ], $rows);
    }

    private function parseCsvInts(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        $values = array_map(
            static fn(string $value): int => (int) trim($value),
            explode(',', $csv)
        );
        $values = array_values(array_filter($values, static fn(int $value): bool => $value > 0));

        return array_values(array_unique($values));
    }

    private function parseCsvStrings(string $csv, string $separator = ','): array
    {
        if ($csv === '') {
            return [];
        }

        $values = array_map('trim', explode($separator, $csv));
        $values = array_values(array_filter($values, static fn(string $value): bool => $value !== ''));

        return array_values(array_unique($values));
    }

    private function parseIdNamePairs(string $pairs): array
    {
        if ($pairs === '') {
            return [];
        }

        // El formato viene como "id::nombre||id::nombre" y aqui se convierte a array tipado.
        $result = [];
        foreach (explode('||', $pairs) as $pair) {
            $parts = explode('::', $pair, 2);
            $id = (int) ($parts[0] ?? 0);
            $name = trim((string) ($parts[1] ?? ''));

            if ($id <= 0 || $name === '') {
                continue;
            }

            $result[] = ['id' => $id, 'name' => $name];
        }

        return $result;
    }

    private function getObjectDocumentsByGameId(int $gameId): array
    {
        if (isset($this->objectDocumentsByGame[$gameId])) {
            return $this->objectDocumentsByGame[$gameId];
        }

        $stmt = $this->connection->prepare(
            'SELECT coleccionable_id, nombre, descripcion, categoria, localizaciones, curiosidades, tecnicas
             FROM coleccionable
             WHERE juego_id = :game_id'
        );
        $stmt->execute([':game_id' => $gameId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $documents = [];
        foreach ($rows as $row) {
            // Genera un "blob" de texto por objeto para hacer matching semantico simple por palabras.
            $blob = mb_strtolower(trim(implode(' ', [
                (string) ($row['nombre'] ?? ''),
                (string) ($row['descripcion'] ?? ''),
                (string) ($row['categoria'] ?? ''),
                (string) ($row['localizaciones'] ?? ''),
                (string) ($row['curiosidades'] ?? ''),
                (string) ($row['tecnicas'] ?? ''),
            ])), 'UTF-8');

            $documents[] = [
                'id' => (int) ($row['coleccionable_id'] ?? 0),
                'blob' => $blob,
            ];
        }

        $this->objectDocumentsByGame[$gameId] = $documents;

        return $documents;
    }

    private function findRelatedObjectIdsByGameIdAndKeywords(int $gameId, array $keywords): array
    {
        $documents = $this->getObjectDocumentsByGameId($gameId);
        if ($documents === []) {
            return [];
        }

        $normalizedKeywords = [];
        foreach ($keywords as $keyword) {
            $value = mb_strtolower(trim((string) $keyword), 'UTF-8');
            if ($value !== '') {
                $normalizedKeywords[] = $value;
            }
        }
        $normalizedKeywords = array_values(array_unique($normalizedKeywords));

        $related = [];
        foreach ($documents as $document) {
            foreach ($normalizedKeywords as $keyword) {
                // Si alguna palabra clave aparece en el blob, considera el objeto relacionado.
                if (mb_stripos((string) ($document['blob'] ?? ''), $keyword, 0, 'UTF-8') !== false) {
                    $related[] = (int) ($document['id'] ?? 0);
                    break;
                }
            }
        }

        $related = array_values(array_unique(array_filter($related, static fn(int $id): bool => $id > 0)));

        if ($related === [] && count($documents) === 1) {
            // Fallback util cuando solo existe un objeto en el juego.
            return [(int) ($documents[0]['id'] ?? 0)];
        }

        return $related;
    }

    private function getTitleByRouteKey(string $routeKey): ?string
    {
        $this->ensureRouteMapsLoaded();

        return $this->routeToTitleMap[$routeKey] ?? null;
    }

    private function resolveRouteKey(string $title): ?string
    {
        $this->ensureRouteMapsLoaded();
        $normalizedTitle = mb_strtolower(trim($title), 'UTF-8');

        return $this->titleToRouteMap[$normalizedTitle] ?? null;
    }

    private function ensureRouteMapsLoaded(): void
    {
        if ($this->routeToTitleMap !== null && $this->titleToRouteMap !== null) {
            return;
        }

        $stmt = $this->connection->query(
            'SELECT j.titulo, COALESCE(s.nombre, "") AS saga_nombre, j.ano_lanzamiento
             FROM juego j
             LEFT JOIN saga s ON s.saga_id = j.saga_id
             ORDER BY saga_nombre ASC, j.ano_lanzamiento ASC, j.titulo ASC'
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $routeToTitle = [];
        $titleToRoute = [];
        $prefixCounters = [];

        foreach ($rows as $row) {
            $title = trim((string) ($row['titulo'] ?? ''));
            if ($title === '') {
                continue;
            }

            $prefix = $this->resolveSagaPrefix((string) ($row['saga_nombre'] ?? ''));
            if (!isset($prefixCounters[$prefix])) {
                $prefixCounters[$prefix] = 0;
            }

            $prefixCounters[$prefix]++;
            $routeKey = $prefix . (string) $prefixCounters[$prefix];

            if (isset($routeToTitle[$routeKey])) {
                continue;
            }

            $routeToTitle[$routeKey] = $title;
            $titleToRoute[mb_strtolower($title, 'UTF-8')] = $routeKey;
        }

        $this->routeToTitleMap = $routeToTitle;
        $this->titleToRouteMap = $titleToRoute;
    }

    private function resolveSagaPrefix(string $sagaName): string
    {
        $normalizedSaga = mb_strtolower(trim($sagaName), 'UTF-8');

        if ($normalizedSaga === '') {
            return 'j';
        }

        if (str_contains($normalizedSaga, 'go')) {
            return 'go';
        }

        if (str_contains($normalizedSaga, 'inazuma') || str_contains($normalizedSaga, 'eleven')) {
            return 'ie';
        }

        $ascii = strtr($normalizedSaga, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
        $token = preg_replace('/[^a-z0-9]+/', '', $ascii);
        $token = is_string($token) ? $token : '';

        if ($token === '') {
            return 'j';
        }

        return substr($token, 0, 2);
    }

    public function createChapter(int $gameId, int $number, string $title, ?string $description): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO capitulo (juego_id, numero, titulo, descripcion)
             VALUES (:game_id, :numero, :titulo, :descripcion)'
        );
        $stmt->execute([
            ':game_id' => $gameId,
            ':numero' => $number,
            ':titulo' => $title,
            ':descripcion' => $description,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateChapter(int $chapterId, int $gameId, int $number, string $title, ?string $description): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE capitulo
             SET numero = :numero, titulo = :titulo, descripcion = :descripcion
             WHERE capitulo_id = :chapter_id AND juego_id = :game_id'
        );
        $stmt->execute([
            ':numero' => $number,
            ':titulo' => $title,
            ':descripcion' => $description,
            ':chapter_id' => $chapterId,
            ':game_id' => $gameId,
        ]);
    }

    public function createTeam(int $gameId, array $payload): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO equipo (juego_id, nombre, descripcion, escudo, entrenador, uniforme, estilo_juego)
             VALUES (:game_id, :nombre, :descripcion, :escudo, :entrenador, :uniforme, :estilo_juego)'
        );
        $stmt->execute([
            ':game_id' => $gameId,
            ':nombre' => $payload['nombre'],
            ':descripcion' => $payload['descripcion'],
            ':escudo' => $payload['escudo'],
            ':entrenador' => $payload['entrenador'],
            ':uniforme' => $payload['uniforme'],
            ':estilo_juego' => $payload['estilo_juego'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateTeam(int $teamId, int $gameId, array $payload): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE equipo
             SET nombre = :nombre,
                 descripcion = :descripcion,
                 escudo = :escudo,
                 entrenador = :entrenador,
                 uniforme = :uniforme,
                 estilo_juego = :estilo_juego
             WHERE equipo_id = :team_id AND juego_id = :game_id'
        );
        $stmt->execute([
            ':nombre' => $payload['nombre'],
            ':descripcion' => $payload['descripcion'],
            ':escudo' => $payload['escudo'],
            ':entrenador' => $payload['entrenador'],
            ':uniforme' => $payload['uniforme'],
            ':estilo_juego' => $payload['estilo_juego'],
            ':team_id' => $teamId,
            ':game_id' => $gameId,
        ]);
    }

    private function syncCharacterTeam(int $characterId, ?int $teamId): void
    {
        $delete = $this->connection->prepare('DELETE FROM personaje_equipo WHERE personaje_id = :character_id');
        $delete->execute([':character_id' => $characterId]);

        if ($teamId !== null && $teamId > 0) {
            $insert = $this->connection->prepare(
                'INSERT INTO personaje_equipo (personaje_id, equipo_id) VALUES (:character_id, :team_id)'
            );
            $insert->execute([
                ':character_id' => $characterId,
                ':team_id' => $teamId,
            ]);
        }
    }

    public function createCharacter(int $gameId, array $payload): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO personaje (
                juego_id, nombre, posicion, elemento, descripcion, estilo_juego, imagen,
                pe, pt, tiro, control, defensa, rapidez, fisico, aguante
             ) VALUES (
                :game_id, :nombre, :posicion, :elemento, :descripcion, :estilo_juego, :imagen,
                :pe, :pt, :tiro, :control, :defensa, :rapidez, :fisico, :aguante
             )'
        );

        $stats = $payload['stats'];
        $stmt->execute([
            ':game_id' => $gameId,
            ':nombre' => $payload['nombre'],
            ':posicion' => $payload['posicion'],
            ':elemento' => $payload['elemento'],
            ':descripcion' => $payload['descripcion'],
            ':estilo_juego' => $payload['estilo_juego'],
            ':imagen' => $payload['imagen'],
            ':pe' => $stats['pe'],
            ':pt' => $stats['pt'],
            ':tiro' => $stats['tiro'],
            ':control' => $stats['control'],
            ':defensa' => $stats['defensa'],
            ':rapidez' => $stats['rapidez'],
            ':fisico' => $stats['fisico'],
            ':aguante' => $stats['aguante'],
        ]);

        $characterId = (int) $this->connection->lastInsertId();
        $this->syncCharacterTeam($characterId, $payload['team_id']);

        return $characterId;
    }

    public function updateCharacter(int $characterId, int $gameId, array $payload): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE personaje
             SET nombre = :nombre,
                 posicion = :posicion,
                 elemento = :elemento,
                 descripcion = :descripcion,
                 estilo_juego = :estilo_juego,
                 imagen = :imagen,
                 pe = :pe,
                 pt = :pt,
                 tiro = :tiro,
                 control = :control,
                 defensa = :defensa,
                 rapidez = :rapidez,
                 fisico = :fisico,
                 aguante = :aguante
             WHERE personaje_id = :character_id AND juego_id = :game_id'
        );

        $stats = $payload['stats'];
        $stmt->execute([
            ':nombre' => $payload['nombre'],
            ':posicion' => $payload['posicion'],
            ':elemento' => $payload['elemento'],
            ':descripcion' => $payload['descripcion'],
            ':estilo_juego' => $payload['estilo_juego'],
            ':imagen' => $payload['imagen'],
            ':pe' => $stats['pe'],
            ':pt' => $stats['pt'],
            ':tiro' => $stats['tiro'],
            ':control' => $stats['control'],
            ':defensa' => $stats['defensa'],
            ':rapidez' => $stats['rapidez'],
            ':fisico' => $stats['fisico'],
            ':aguante' => $stats['aguante'],
            ':character_id' => $characterId,
            ':game_id' => $gameId,
        ]);

        $this->syncCharacterTeam($characterId, $payload['team_id']);
    }

    public function createSupertecnica(int $gameId, array $payload): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO supertecnica (
                juego_id, nombre, tipo, poder, coste_pe, nivel_desbloqueo, descripcion, elemento, video
             ) VALUES (
                :game_id, :nombre, :tipo, :poder, :coste_pe, :nivel_desbloqueo, :descripcion, :elemento, :video
             )'
        );
        $stmt->execute([
            ':game_id' => $gameId,
            ':nombre' => $payload['nombre'],
            ':tipo' => $payload['tipo'],
            ':poder' => $payload['poder'],
            ':coste_pe' => $payload['coste_pe'],
            ':nivel_desbloqueo' => $payload['nivel_desbloqueo'],
            ':descripcion' => $payload['descripcion'],
            ':elemento' => $payload['elemento'],
            ':video' => $payload['video'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateSupertecnica(int $techId, int $gameId, array $payload): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE supertecnica
             SET nombre = :nombre,
                 tipo = :tipo,
                 poder = :poder,
                 coste_pe = :coste_pe,
                 nivel_desbloqueo = :nivel_desbloqueo,
                 descripcion = :descripcion,
                 elemento = :elemento,
                 video = :video
             WHERE supertecnica_id = :tech_id AND juego_id = :game_id'
        );
        $stmt->execute([
            ':nombre' => $payload['nombre'],
            ':tipo' => $payload['tipo'],
            ':poder' => $payload['poder'],
            ':coste_pe' => $payload['coste_pe'],
            ':nivel_desbloqueo' => $payload['nivel_desbloqueo'],
            ':descripcion' => $payload['descripcion'],
            ':elemento' => $payload['elemento'],
            ':video' => $payload['video'],
            ':tech_id' => $techId,
            ':game_id' => $gameId,
        ]);
    }

    public function createObject(int $gameId, array $payload): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO coleccionable (
                juego_id, nombre, tipo, descripcion, rareza, categoria, localizaciones, curiosidades, tecnicas, imagen
             ) VALUES (
                :game_id, :nombre, :tipo, :descripcion, :rareza, :categoria, :localizaciones, :curiosidades, :tecnicas, :imagen
             )'
        );
        $stmt->execute([
            ':game_id' => $gameId,
            ':nombre' => $payload['nombre'],
            ':tipo' => $payload['tipo'],
            ':descripcion' => $payload['descripcion'],
            ':rareza' => $payload['rareza'],
            ':categoria' => $payload['categoria'],
            ':localizaciones' => $payload['localizaciones'],
            ':curiosidades' => $payload['curiosidades'],
            ':tecnicas' => $payload['tecnicas'],
            ':imagen' => $payload['imagen'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateObject(int $objectId, int $gameId, array $payload): void
    {
        $stmt = $this->connection->prepare(
            'UPDATE coleccionable
             SET nombre = :nombre,
                 tipo = :tipo,
                 descripcion = :descripcion,
                 rareza = :rareza,
                 categoria = :categoria,
                 localizaciones = :localizaciones,
                 curiosidades = :curiosidades,
                 tecnicas = :tecnicas,
                 imagen = :imagen
             WHERE coleccionable_id = :object_id AND juego_id = :game_id'
        );
        $stmt->execute([
            ':nombre' => $payload['nombre'],
            ':tipo' => $payload['tipo'],
            ':descripcion' => $payload['descripcion'],
            ':rareza' => $payload['rareza'],
            ':categoria' => $payload['categoria'],
            ':localizaciones' => $payload['localizaciones'],
            ':curiosidades' => $payload['curiosidades'],
            ':tecnicas' => $payload['tecnicas'],
            ':imagen' => $payload['imagen'],
            ':object_id' => $objectId,
            ':game_id' => $gameId,
        ]);
    }
}
