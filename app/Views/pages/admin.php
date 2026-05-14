<?php
declare(strict_types=1);

$errors = is_array($errors ?? null) ? $errors : [];
$sagas = is_array($sagas ?? null) ? $sagas : [];
$plataformas = is_array($plataformas ?? null) ? $plataformas : [];
$juegos = is_array($juegos ?? null) ? $juegos : [];
$personajes = is_array($personajes ?? null) ? $personajes : [];
$equipos = is_array($equipos ?? null) ? $equipos : [];
?>
<div class="caja moderation-page">
    <h1 class="titulo">Panel de Administración</h1>
    <div class="linea"></div>

    <?php if ($errors !== []) : ?>
        <div class="alert alert-danger" role="alert" style="margin: 1rem 5%;">
            <?= implode('<br>', array_map(static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'), $errors)) ?>
        </div>
    <?php endif; ?>

    <!-- SAGAS -->
    <section class="moderation-block">
        <h2 class="titulo">Sagas</h2>
        <form method="post" action="<?= route('admin') ?>" class="moderation-card-form moderation-grid-form">
            <input type="hidden" name="admin_action" value="create_saga">
            <div>
                <label for="createSagaNombre">Nombre *</label>
                <input type="text" id="createSagaNombre" name="saga_nombre" placeholder="Ej: Inazuma Eleven" required>
            </div>
            <div>
                <label for="createSagaDescripcion">Descripción</label>
                <textarea id="createSagaDescripcion" name="saga_descripcion" placeholder="Describe la saga..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Crear Saga</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($sagas as $saga) : ?>
                        <tr>
                            <td><?= (int) ($saga['saga_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($saga['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($saga['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= route('admin') ?>" class="moderation-inline-form">
                                    <input type="hidden" name="admin_action" value="update_saga">
                                    <input type="hidden" name="saga_id" value="<?= (int) ($saga['saga_id'] ?? 0) ?>">
                                    <input type="text" name="saga_nombre" value="<?= htmlspecialchars((string) ($saga['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    <button type="submit" class="btn btn-sm btn-secondary">Editar</button>
                                </form>
                                <form method="post" action="<?= route('admin') ?>" style="margin-top: 0.25rem;" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="admin_action" value="delete_saga">
                                    <input type="hidden" name="saga_id" value="<?= (int) ($saga['saga_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- PLATAFORMAS -->
    <section class="moderation-block">
        <h2 class="titulo">Plataformas</h2>
        <form method="post" action="<?= route('admin') ?>" class="moderation-card-form moderation-grid-form">
            <input type="hidden" name="admin_action" value="create_plataforma">
            <div>
                <label for="createPlataformaNombre">Nombre *</label>
                <input type="text" id="createPlataformaNombre" name="plataforma_nombre" placeholder="Ej: Nintendo 3DS" required>
            </div>
            <button type="submit" class="btn btn-primary">Crear Plataforma</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($plataformas as $plataforma) : ?>
                        <tr>
                            <td><?= (int) ($plataforma['plataforma_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($plataforma['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= route('admin') ?>" class="moderation-inline-form">
                                    <input type="hidden" name="admin_action" value="update_plataforma">
                                    <input type="hidden" name="plataforma_id" value="<?= (int) ($plataforma['plataforma_id'] ?? 0) ?>">
                                    <input type="text" name="plataforma_nombre" value="<?= htmlspecialchars((string) ($plataforma['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                    <button type="submit" class="btn btn-sm btn-secondary">Editar</button>
                                </form>
                                <form method="post" action="<?= route('admin') ?>" style="margin-top: 0.25rem;" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="admin_action" value="delete_plataforma">
                                    <input type="hidden" name="plataforma_id" value="<?= (int) ($plataforma['plataforma_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- JUEGOS -->
    <section class="moderation-block">
        <h2 class="titulo">Juegos</h2>
        <form method="post" action="<?= route('admin') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
            <input type="hidden" name="admin_action" value="create_juego">
            <div>
                <label for="createJuegoTitulo">Título *</label>
                <input type="text" id="createJuegoTitulo" name="juego_titulo" placeholder="Ej: Inazuma Eleven" required>
            </div>
            <div>
                <label for="createJuegoSaga">Saga</label>
                <select id="createJuegoSaga" name="juego_saga_id">
                    <option value="0">-- Seleccionar --</option>
                    <?php foreach ($sagas as $saga) : ?>
                        <option value="<?= (int) ($saga['saga_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($saga['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="createJuegoPlataforma">Plataforma</label>
                <select id="createJuegoPlataforma" name="juego_plataforma_id">
                    <option value="0">-- Seleccionar --</option>
                    <?php foreach ($plataformas as $plat) : ?>
                        <option value="<?= (int) ($plat['plataforma_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($plat['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="createJuegoAno">Año</label>
                <input type="number" id="createJuegoAno" name="juego_ano" min="1900" max="<?= date('Y') + 5 ?>" placeholder="Ej: 2008">
            </div>
            <div>
                <label for="createJuegoDescripcion">Descripción</label>
                <textarea id="createJuegoDescripcion" name="juego_descripcion" placeholder="Descripción del juego..."></textarea>
            </div>
            <div>
                <label for="createJuegoImagen">Imagen</label>
                <input type="file" id="createJuegoImagen" name="juego_imagen" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Crear Juego</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr><th>ID</th><th>Título</th><th>Saga</th><th>Plataforma</th><th>Año</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($juegos as $juego) : ?>
                        <tr>
                            <td><?= (int) ($juego['juego_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($juego['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($juego['saga_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($juego['plataforma_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) ($juego['ano_lanzamiento'] ?? 0) ?: '—' ?></td>
                            <td>
                                <form method="post" action="<?= route('admin') ?>" style="margin-bottom: 0.25rem;" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="admin_action" value="delete_juego">
                                    <input type="hidden" name="juego_id" value="<?= (int) ($juego['juego_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- PERSONAJES -->
    <section class="moderation-block">
        <h2 class="titulo">Personajes</h2>
        <form method="post" action="<?= route('admin') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
            <input type="hidden" name="admin_action" value="create_personaje">
            <div>
                <label for="createPersonajeJuego">Juego *</label>
                <select id="createPersonajeJuego" name="personaje_juego_id" required>
                    <option value="0">-- Seleccionar --</option>
                    <?php foreach ($juegos as $juego) : ?>
                        <option value="<?= (int) ($juego['juego_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($juego['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="createPersonajeNombre">Nombre *</label>
                <input type="text" id="createPersonajeNombre" name="personaje_nombre" placeholder="Ej: Mark Evans" required>
            </div>
            <div>
                <label for="createPersonajePosicion">Posición *</label>
                <select id="createPersonajePosicion" name="personaje_posicion" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="POR">Portero (POR)</option>
                    <option value="DF">Defensa (DF)</option>
                    <option value="MD">Medio (MD)</option>
                    <option value="DL">Delantero (DL)</option>
                </select>
            </div>
            <div>
                <label for="createPersonajeElemento">Elemento *</label>
                <select id="createPersonajeElemento" name="personaje_elemento" required>
                    <option value="">-- Seleccionar --</option>
                    <option value="Fuego">Fuego</option>
                    <option value="Aire">Aire</option>
                    <option value="Bosque">Bosque</option>
                    <option value="Montania">Montaña</option>
                    <option value="Neutral">Neutral</option>
                </select>
            </div>
            <div>
                <label for="createPersonajeDescripcion">Descripción</label>
                <textarea id="createPersonajeDescripcion" name="personaje_descripcion" placeholder="Descripción..."></textarea>
            </div>
            <div>
                <label for="createPersonajeEstilo">Estilo de Juego</label>
                <input type="text" id="createPersonajeEstilo" name="personaje_estilo" placeholder="Ej: Ofensivo">
            </div>
            <div>
                <label for="createPersonajeImagen">Imagen</label>
                <input type="file" id="createPersonajeImagen" name="personaje_imagen" accept="image/*">
            </div>
            <fieldset style="grid-column: 1 / -1;">
                <legend>Estadísticas (opcional)</legend>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div>
                        <label for="createPersonajePE">PE</label>
                        <input type="number" id="createPersonajePE" name="personaje_pe" min="0">
                    </div>
                    <div>
                        <label for="createPersonajePT">PT</label>
                        <input type="number" id="createPersonajePT" name="personaje_pt" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeTiro">Tiro</label>
                        <input type="number" id="createPersonajeTiro" name="personaje_tiro" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeControl">Control</label>
                        <input type="number" id="createPersonajeControl" name="personaje_control" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeDefensa">Defensa</label>
                        <input type="number" id="createPersonajeDefensa" name="personaje_defensa" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeRapidez">Rapidez</label>
                        <input type="number" id="createPersonajeRapidez" name="personaje_rapidez" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeFisico">Físico</label>
                        <input type="number" id="createPersonajeFisico" name="personaje_fisico" min="0">
                    </div>
                    <div>
                        <label for="createPersonajeAguante">Aguante</label>
                        <input type="number" id="createPersonajeAguante" name="personaje_aguante" min="0">
                    </div>
                </div>
            </fieldset>
            <button type="submit" class="btn btn-primary">Crear Personaje</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table" style="font-size: 0.85rem;">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Juego</th><th>Pos</th><th>Elem</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($personajes as $personaje) : ?>
                        <tr>
                            <td><?= (int) ($personaje['personaje_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($personaje['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($personaje['juego_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($personaje['posicion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($personaje['elemento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= route('admin') ?>" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="admin_action" value="delete_personaje">
                                    <input type="hidden" name="personaje_id" value="<?= (int) ($personaje['personaje_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- EQUIPOS -->
    <section class="moderation-block">
        <h2 class="titulo">Equipos</h2>
        <form method="post" action="<?= route('admin') ?>" class="moderation-card-form moderation-grid-form" enctype="multipart/form-data">
            <input type="hidden" name="admin_action" value="create_equipo">
            <div>
                <label for="createEquipoJuego">Juego *</label>
                <select id="createEquipoJuego" name="equipo_juego_id" required>
                    <option value="0">-- Seleccionar --</option>
                    <?php foreach ($juegos as $juego) : ?>
                        <option value="<?= (int) ($juego['juego_id'] ?? 0) ?>"><?= htmlspecialchars((string) ($juego['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="createEquipoNombre">Nombre *</label>
                <input type="text" id="createEquipoNombre" name="equipo_nombre" placeholder="Ej: Instituto Raimon" required>
            </div>
            <div>
                <label for="createEquipoDescripcion">Descripción</label>
                <textarea id="createEquipoDescripcion" name="equipo_descripcion" placeholder="Descripción..."></textarea>
            </div>
            <div>
                <label for="createEquipoEntrenador">Entrenador</label>
                <input type="text" id="createEquipoEntrenador" name="equipo_entrenador" placeholder="Ej: Seymour Hillman">
            </div>
            <div>
                <label for="createEquipoUniforme">Uniforme</label>
                <input type="text" id="createEquipoUniforme" name="equipo_uniforme" placeholder="Ej: Amarillo y azul">
            </div>
            <div>
                <label for="createEquipoEstilo">Estilo de Juego</label>
                <input type="text" id="createEquipoEstilo" name="equipo_estilo" placeholder="Ej: Ofensivo">
            </div>
            <div>
                <label for="createEquipoEscudo">Escudo (imagen)</label>
                <input type="file" id="createEquipoEscudo" name="equipo_escudo" accept="image/*">
            </div>
            <button type="submit" class="btn btn-primary">Crear Equipo</button>
        </form>

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr><th>ID</th><th>Nombre</th><th>Juego</th><th>Entrenador</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $equipo) : ?>
                        <tr>
                            <td><?= (int) ($equipo['equipo_id'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string) ($equipo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($equipo['juego_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($equipo['entrenador'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= route('admin') ?>" onsubmit="return confirm('¿Eliminar?');">
                                    <input type="hidden" name="admin_action" value="delete_equipo">
                                    <input type="hidden" name="equipo_id" value="<?= (int) ($equipo['equipo_id'] ?? 0) ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
