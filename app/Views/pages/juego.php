<?php
declare(strict_types=1);

$gameWiki    = is_array($gameWiki ?? null) ? $gameWiki : [];
$title       = (string) ($gameWiki['title']    ?? 'Juego');
$subtitle    = (string) ($gameWiki['subtitle'] ?? '');
$image       = (string) ($gameWiki['image']    ?? '');
$image       = !empty($image) ? $image : 'uploads/imagenes/juegos/Inazuma_eleven_caratula.webp';
$saga        = (string) ($gameWiki['saga']      ?? '');
$release     = (string) ($gameWiki['release']   ?? '');
$platforms   = (string) ($gameWiki['platforms'] ?? '');
$genre       = (string) ($gameWiki['genre']     ?? '');
$summary     = (string) ($gameWiki['summary']   ?? '');
$wikiUrl     = (string) ($gameWiki['wikiUrl']   ?? '');
$chapters    = is_array($gameWiki['chapters']    ?? null) ? $gameWiki['chapters']    : [];
$teams       = is_array($gameWiki['teams']       ?? null) ? $gameWiki['teams']       : [];
$characters  = is_array($gameWiki['characters']  ?? null) ? $gameWiki['characters']  : [];
$supertecnicas = is_array($gameWiki['supertecnicas'] ?? null) ? $gameWiki['supertecnicas'] : [];
$objects     = is_array($gameWiki['objects']     ?? null) ? $gameWiki['objects']     : [];
$secrets     = is_array($gameWiki['secrets']     ?? null) ? $gameWiki['secrets']     : [];

$esc = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
// Busca una estadistica por etiqueta dentro de una lista heterogenea de stats.
$findStatValue = static function (array $stats, string $label): string {
    foreach ($stats as $stat) {
        if ((string) ($stat['label'] ?? '') === $label) {
            return (string) ($stat['value'] ?? '');
        }
    }

    return '';
};
// Normaliza bloques multilinea en arrays limpios para render con chips/listas.
$splitLines = static function (string $text): array {
    $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));

    if ($normalized === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode("\n", $normalized)), static fn(string $line): bool => $line !== ''));
};
// Traduce formatos de rareza (n/5, n/10 o entero) a estrellas visuales.
$formatRarityStars = static function (string $rawRarity): string {
    $value = trim($rawRarity);
    if ($value === '') {
        return '';
    }

    $score = null;
    if (preg_match('/^(\d+)\s*\/\s*(\d+)$/', $value, $matches) === 1) {
        $numerator = (int) $matches[1];
        $denominator = max(1, (int) $matches[2]);
        $score = (int) round(($numerator / $denominator) * 5);
    } elseif (preg_match('/^\d+$/', $value) === 1) {
        $score = (int) $value;
    }

    if ($score === null) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    $filled = max(0, min(5, $score));
    return str_repeat('&#9733;', $filled) . str_repeat('&#9734;', 5 - $filled);
};
?>
<div class="caja">

    <!-- Wiki Header / Hero Section -->
    <div class="wiki-header">
        <h1 class="wiki-title"><?= $esc($title) ?></h1>
    </div>

    <div class="linea"></div>

    <!-- Intro: caratula + resumen -->
    <div class="wiki-intro">
        <div class="wiki-cover-section">
            <img src="<?= asset($image) ?>" alt="<?= $esc($title) ?>" class="wiki-cover-image">
        </div>
        <div class="wiki-summary-section">
            <h2 class="wiki-subtitle">Descripción</h2>
            <p class="wiki-summary-text"><?= $esc($summary) ?></p>
            <div class="wiki-meta-info">
                <div class="meta-item">
                    <strong>Plataformas:</strong> <?= $esc($platforms) ?>
                </div>
                <div class="meta-item">
                    <strong>Género:</strong> <?= $esc($genre) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegacion de secciones -->
    <div class="wiki-navigation">
        <h3 class="wiki-nav-title"><i class="fa fa-search"></i> Que datos quieres buscar</h3>
        <div class="wiki-nav-buttons">
            <a href="#equipos" class="wiki-nav-btn">
                <i class="fa fa-users"></i>
                <span>Equipos</span>
            </a>
            <a href="#personajes" class="wiki-nav-btn">
                <i class="fa fa-user"></i>
                <span>Personajes</span>
            </a>
            <a href="#supertecnicas" class="wiki-nav-btn">
                <i class="fa fa-bolt"></i>
                <span>Supertecnicas</span>
            </a>
            <a href="#objetos" class="wiki-nav-btn">
                <i class="fa fa-gift"></i>
                <span>Objetos</span>
            </a>
        </div>
    </div>

    <div class="linea"></div>

    <!-- Capitulos de la historia -->
    <h1 class="titulo" id="capitulos">Capítulos de la Historia</h1>
    <?php if (!empty($chapters)) : ?>
        <?php foreach ($chapters as $cap) : ?>
            <div class="ficha-container wiki-chapter-card">
                <div class="ficha">
                    <div class="ficha-title">
                        <h3><?= $esc((string) ($cap['title'] ?? '')) ?></h3>
                        <p style="font-size:1.1em"><?= $esc((string) ($cap['desc'] ?? '')) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="wiki-empty-state">
            <p>No hay capitulos disponibles para este juego todavia.</p>
        </div>
    <?php endif; ?>

    <div class="linea" id="equipos"></div>

    <!-- Equipos -->
    <div id="equipos"></div>
    <h1 class="titulo">Equipos</h1>
    <section class="wiki-browser" id="team-browser" data-wiki-browser>
        <div class="wiki-browser-toolbar">
            <form class="wiki-browser-search-bar" data-wiki-search-form>
                <input type="search" class="wiki-browser-input" placeholder="Buscar el equipo" data-wiki-search-input>
                <button type="submit" class="wiki-browser-button"><i class="fa fa-search"></i> Buscar</button>
            </form>
        </div>

        <?php if (!empty($teams)) : ?>
            <div class="wiki-browser-grid">
                <?php foreach ($teams as $team) : ?>
                    <?php
                    $teamName = (string) ($team['name'] ?? '');
                    $teamDescription = (string) ($team['description'] ?? '');
                    $teamHistory = (string) ($team['history'] ?? '');
                    $teamCharacterIds = is_array($team['character_ids'] ?? null) ? $team['character_ids'] : [];
                    $teamObjectIds = is_array($team['object_ids'] ?? null) ? $team['object_ids'] : [];
                    $teamStats = (array) ($team['stats'] ?? []);
                    $teamCaptain = $findStatValue($teamStats, 'Capitan');
                    $teamCoach = $findStatValue($teamStats, 'Entrenador');
                    $teamUniform = $findStatValue($teamStats, 'Uniforme');
                    $teamStyle = $findStatValue($teamStats, 'Estilo de Juego');
                    $teamSearch = mb_strtolower(trim($teamName . ' ' . $teamDescription . ' ' . $teamCaptain . ' ' . $teamCoach . ' ' . $teamUniform . ' ' . $teamStyle), 'UTF-8');
                    ?>
                    <article class="wiki-character-card wiki-team-card" data-wiki-card data-search="<?= $esc($teamSearch) ?>" data-entity-id="<?= (int) ($team['id'] ?? 0) ?>" data-rel-character-ids="<?= $esc(implode(',', array_map('strval', $teamCharacterIds))) ?>" data-rel-object-ids="<?= $esc(implode(',', array_map('strval', $teamObjectIds))) ?>">
                        <div class="wiki-character-layout">
                            <?php if (!empty($team['image'])) : ?>
                                <img src="<?= asset((string) $team['image']) ?>" alt="<?= $esc($teamName) ?>" class="wiki-character-image">
                            <?php endif; ?>
                            <div class="wiki-character-content">
                                <h2><?= $esc($teamName) ?></h2>
                                <p class="wiki-character-role wiki-entity-subtitle"><?= $esc($teamDescription) ?></p>
                                <div class="wiki-character-meta">
                                    <?php if ($teamCaptain !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Capitan</h4>
                                            <p><?= $esc($teamCaptain) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($teamCoach !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Entrenador</h4>
                                            <p><?= $esc($teamCoach) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($teamUniform !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Uniforme</h4>
                                            <p><?= $esc($teamUniform) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($teamStyle !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Estilo de Juego</h4>
                                            <p><?= $esc($teamStyle) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($teamHistory !== '') : ?>
                                    <div class="wiki-character-history">
                                        <h4>Historia:</h4>
                                        <p><?= $esc($teamHistory) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="wiki-empty-state" data-wiki-empty hidden>
                <p>No hay equipos que coincidan con la busqueda.</p>
            </div>
        <?php else : ?>
            <div class="wiki-empty-state">
                <p>No hay equipos disponibles para este juego todavia.</p>
            </div>
        <?php endif; ?>
    </section>

    <div class="linea" id="personajes"></div>

    <!-- Personajes -->
    <h1 class="titulo">Personajes</h1>
    <section class="wiki-browser" id="character-browser" data-empty-label="No hay personajes que coincidan con la busqueda." data-wiki-browser>
        <div class="wiki-browser-toolbar">
            <form class="wiki-browser-search-bar" data-character-search-form data-wiki-search-form>
                <input type="search" class="wiki-browser-input" placeholder="Buscar el personaje" data-character-search data-wiki-search-input>
                <button type="submit" class="wiki-browser-button"><i class="fa fa-search"></i> Buscar</button>
            </form>
        </div>

        <?php if (!empty($characters)) : ?>
            <div class="wiki-browser-grid" data-character-results>
                <?php foreach ($characters as $index => $char) : ?>
                    <?php
                    $charName = (string) ($char['name'] ?? '');
                    $charRole = (string) ($char['role'] ?? '');
                    $charTeamIds = is_array($char['team_ids'] ?? null) ? $char['team_ids'] : [];
                    $charTeamNames = is_array($char['team_names'] ?? null) ? $char['team_names'] : [];
                    $charTechniqueIds = is_array($char['technique_ids'] ?? null) ? $char['technique_ids'] : [];
                    $charTechniqueNames = is_array($char['technique_names'] ?? null) ? $char['technique_names'] : [];
                    $charObjectIds = is_array($char['object_ids'] ?? null) ? $char['object_ids'] : [];
                    $charStats = (array) ($char['stats'] ?? []);
                    $charTeam = $findStatValue($charStats, 'Equipo');
                    $charPosition = $findStatValue($charStats, 'Posicion');
                    $charElement = $findStatValue($charStats, 'Elemento');
                    $charStyle = $findStatValue($charStats, 'Estilo de juego');
                    $searchBlob = mb_strtolower(trim($charName . ' ' . $charRole . ' ' . $charTeam . ' ' . $charPosition . ' ' . $charElement . ' ' . $charStyle . ' ' . implode(' ', $charTeamNames) . ' ' . implode(' ', $charTechniqueNames)), 'UTF-8');
                    $tableStats = array_values(array_filter($charStats, static function (array $stat): bool {
                        $label = (string) ($stat['label'] ?? '');

                        return in_array($label, ['PE', 'PT', 'Tiro', 'Control', 'Defensa', 'Rapidez', 'Fisico', 'Aguante'], true);
                    }));

                    if ($tableStats === []) {
                        $tableStats = $charStats;
                    }
                    ?>
                    <article
                        class="wiki-character-card wiki-character-card--person"
                        data-character-card
                        data-wiki-card
                        data-search="<?= $esc($searchBlob) ?>"
                        data-entity-id="<?= (int) ($char['id'] ?? 0) ?>"
                        data-rel-team-ids="<?= $esc(implode(',', array_map('strval', $charTeamIds))) ?>"
                        data-rel-technique-ids="<?= $esc(implode(',', array_map('strval', $charTechniqueIds))) ?>"
                        data-rel-object-ids="<?= $esc(implode(',', array_map('strval', $charObjectIds))) ?>"
                    >
                        <div class="wiki-character-layout">
                            <?php if (!empty($char['image'])) : ?>
                                <img src="<?= asset((string) $char['image']) ?>" alt="<?= $esc($charName) ?>" class="wiki-character-image wiki-character-image--person">
                            <?php endif; ?>
                            <div class="wiki-character-content">
                                <h2><?= $esc($charName) ?></h2>
                                <p class="wiki-character-role"><?= $esc($charRole) ?></p>
                                <div class="wiki-character-meta">
                                    <?php if ($charTeam !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Equipo</h4>
                                            <p><?= $esc($charTeam) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($charPosition !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Posicion</h4>
                                            <p><?= $esc($charPosition) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($charElement !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Elemento</h4>
                                            <p><?= $esc($charElement) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($charStyle !== '') : ?>
                                        <div class="wiki-character-meta-item">
                                            <h4>Estilo de juego</h4>
                                            <p><?= $esc($charStyle) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($charTechniqueNames)) : ?>
                                    <div class="wiki-character-techniques-block">
                                        <h4>Supertecnicas</h4>
                                        <div class="wiki-character-techniques-list">
                                            <?php foreach ($charTechniqueNames as $techniqueName) : ?>
                                                <span class="wiki-character-technique-pill"><?= $esc((string) $techniqueName) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($tableStats)) : ?>
                                    <div class="wiki-character-table-wrap" id="character-detail-<?= $index + 1 ?>">
                                        <table class="wiki-character-table">
                                            <tbody>
                                            <tr class="wiki-character-table-title-row">
                                                <th colspan="4"><?= $esc($charName . ' - Nvl 99') ?></th>
                                            </tr>
                                            <?php foreach (array_chunk($tableStats, 2) as $statsRow) : ?>
                                                <tr>
                                                    <th><?= $esc((string) ($statsRow[0]['label'] ?? '')) ?></th>
                                                    <td><?= $esc((string) ($statsRow[0]['value'] ?? '')) ?></td>
                                                    <?php if (isset($statsRow[1])) : ?>
                                                        <th><?= $esc((string) ($statsRow[1]['label'] ?? '')) ?></th>
                                                        <td><?= $esc((string) ($statsRow[1]['value'] ?? '')) ?></td>
                                                    <?php else : ?>
                                                        <th class="is-empty"></th>
                                                        <td class="is-empty"></td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="wiki-empty-state" data-character-empty data-wiki-empty hidden>
                <p>No hay personajes que coincidan con la busqueda.</p>
            </div>
        <?php else : ?>
            <div class="wiki-empty-state">
                <p>No hay personajes disponibles para este juego todavia.</p>
            </div>
        <?php endif; ?>
    </section>

    <div class="linea" id="supertecnicas"></div>

    <!-- Supertecnicas -->
    <h1 class="titulo">Supertecnicas</h1>
    <section class="wiki-browser" id="technique-browser" data-wiki-browser>
        <div class="wiki-browser-toolbar">
            <form class="wiki-browser-search-bar" data-wiki-search-form>
                <input type="search" class="wiki-browser-input" placeholder="Buscar la supertecnica" data-wiki-search-input>
                <button type="submit" class="wiki-browser-button"><i class="fa fa-search"></i> Buscar</button>
            </form>
        </div>

        <?php if (!empty($supertecnicas)) : ?>
            <div class="wiki-browser-grid">
                <?php foreach ($supertecnicas as $st) : ?>
                    <?php
                    $stName = (string) ($st['name'] ?? '');
                    $stDescription = (string) ($st['description'] ?? '');
                    $stHistory = (string) ($st['history'] ?? '');
                    $stCharacterIds = is_array($st['character_ids'] ?? null) ? $st['character_ids'] : [];
                    $stStats = (array) ($st['stats'] ?? []);
                    $stSearch = mb_strtolower(trim($stName . ' ' . $stDescription . ' ' . $stHistory), 'UTF-8');
                    foreach ($stStats as $statItem) {
                        $stSearch .= ' ' . (string) ($statItem['label'] ?? '') . ' ' . (string) ($statItem['value'] ?? '');
                    }
                    ?>
                    <article class="wiki-character-card wiki-technique-card" data-wiki-card data-search="<?= $esc($stSearch) ?>" data-entity-id="<?= (int) ($st['id'] ?? 0) ?>" data-rel-character-ids="<?= $esc(implode(',', array_map('strval', $stCharacterIds))) ?>">
                        <div class="wiki-character-layout">
                            <?php if (!empty($st['image'])) : ?>
                                <img src="<?= asset((string) $st['image']) ?>" alt="<?= $esc($stName) ?>" class="wiki-character-image wiki-technique-image">
                            <?php endif; ?>
                            <div class="wiki-character-content">
                                <h2><?= $esc($stName) ?></h2>
                                <p class="wiki-character-role wiki-entity-subtitle"><?= $esc($stDescription) ?></p>
                                <?php if (!empty($stStats)) : ?>
                                    <div class="wiki-character-meta">
                                        <?php foreach ($stStats as $stat) : ?>
                                            <div class="wiki-character-meta-item">
                                                <h4><?= $esc((string) ($stat['label'] ?? '')) ?></h4>
                                                <p><?= $esc((string) ($stat['value'] ?? '')) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($stHistory !== '') : ?>
                                    <div class="wiki-character-history">
                                        <h4>Historia de la Tecnica:</h4>
                                        <p><?= $esc($stHistory) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="wiki-empty-state" data-wiki-empty hidden>
                <p>No hay supertecnicas que coincidan con la busqueda.</p>
            </div>
        <?php else : ?>
            <div class="wiki-empty-state">
                <p>No hay supertecnicas disponibles para este juego todavia.</p>
            </div>
        <?php endif; ?>
    </section>

    <div class="linea" id="objetos"></div>

    <!-- Objetos coleccionables -->
    <h1 class="titulo">Objetos Coleccionables</h1>
    <section class="wiki-browser" id="object-browser" data-wiki-browser>
        <div class="wiki-browser-toolbar">
            <form class="wiki-browser-search-bar" data-wiki-search-form>
                <input type="search" class="wiki-browser-input" placeholder="Buscar el objeto" data-wiki-search-input>
                <button type="submit" class="wiki-browser-button"><i class="fa fa-search"></i> Buscar</button>
            </form>
        </div>

        <?php if (!empty($objects)) : ?>
            <div class="wiki-browser-grid">
                <?php foreach ($objects as $obj) : ?>
                    <?php
                    $objectName = (string) ($obj['name'] ?? '');
                    $objectDescription = (string) ($obj['description'] ?? '');
                    $objectStats = (array) ($obj['stats'] ?? []);
                    $objectRarity = $findStatValue($objectStats, 'Rareza');
                    $objectRarityStars = $formatRarityStars($objectRarity);
                    $objectCategory = $findStatValue($objectStats, 'Categoria');
                    $objectLocations = $splitLines((string) ($obj['locations'] ?? ''));
                    $objectNotes = $splitLines((string) ($obj['notes'] ?? ''));
                    $objectTechniques = $splitLines((string) ($obj['techniques'] ?? ''));
                    $objectSearch = mb_strtolower(trim($objectName . ' ' . $objectDescription . ' ' . $objectCategory . ' ' . implode(' ', $objectLocations) . ' ' . implode(' ', $objectNotes) . ' ' . implode(' ', $objectTechniques)), 'UTF-8');
                    ?>
                    <article class="wiki-object-card" data-wiki-card data-search="<?= $esc($objectSearch) ?>" data-entity-id="<?= (int) ($obj['id'] ?? 0) ?>">
                        <?php if (!empty($obj['image'])) : ?>
                            <div class="wiki-object-image-wrap">
                                <img src="<?= asset((string) $obj['image']) ?>" alt="<?= $esc($objectName) ?>" class="wiki-object-image">
                            </div>
                        <?php endif; ?>
                        <h2 class="wiki-object-title"><?= $esc($objectName) ?></h2>
                        <p class="wiki-object-subtitle"><?= $esc($objectDescription) ?></p>

                        <div class="wiki-object-meta-grid">
                            <?php if ($objectRarity !== '') : ?>
                                <div class="wiki-object-meta-item">
                                    <h4>Rareza</h4>
                                    <p><?= $objectRarityStars ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($objectCategory !== '') : ?>
                                <div class="wiki-object-meta-item">
                                    <h4>Categoría</h4>
                                    <p><?= $esc($objectCategory) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($objectLocations)) : ?>
                            <div class="wiki-object-section">
                                <h4 class="wiki-object-section-title">Localizaciones:</h4>
                                <ul class="wiki-object-list">
                                    <?php foreach ($objectLocations as $location) : ?>
                                        <li><?= $esc($location) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($objectNotes)) : ?>
                            <div class="wiki-object-section">
                                <h4 class="wiki-object-section-title">Curiosidades:</h4>
                                <ul class="wiki-object-list">
                                    <?php foreach ($objectNotes as $note) : ?>
                                        <li><?= $esc($note) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($objectTechniques)) : ?>
                            <div class="wiki-object-section">
                                <h4 class="wiki-object-section-title">Técnicas:</h4>
                                <ul class="wiki-object-list wiki-object-techniques-list">
                                    <?php foreach ($objectTechniques as $technique) : ?>
                                        <li><strong><?= $esc(strtok($technique, ':') ?: $technique) ?>:</strong><?= str_contains($technique, ':') ? ' ' . $esc(trim(substr($technique, (int) strpos($technique, ':') + 1))) : '' ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="wiki-empty-state" data-wiki-empty hidden>
                <p>No hay objetos que coincidan con la busqueda.</p>
            </div>
            <div style="margin-bottom: 0.5rem;"></div>
        <?php else : ?>
            <div class="wiki-empty-state">
                <p>No hay objetos disponibles para este juego todavia.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- Secretos y trucos -->
    <?php if (!empty($secrets)) : ?>
        <h1 class="titulo">Secretos y Trucos</h1>
        <div class="flex">
            <?php foreach ($secrets as $secret) : ?>
                <div class="noticia">
                    <div class="secreto-icon"><i class="fa fa-<?= $esc((string) ($secret['icon'] ?? 'star')) ?>"></i></div>
                    <h4><?= $esc((string) ($secret['title'] ?? '')) ?></h4>
                    <p><?= $esc((string) ($secret['text'] ?? '')) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="linea"></div>
    <?php endif; ?>

    
</div>
