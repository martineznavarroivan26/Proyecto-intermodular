SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `11367764_inamania`
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE `11367764_inamania`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS tecnicas_personaje;
DROP TABLE IF EXISTS personaje_equipo;
DROP TABLE IF EXISTS coleccionable;
DROP TABLE IF EXISTS supertecnica;
DROP TABLE IF EXISTS personaje;
DROP TABLE IF EXISTS equipo;
DROP TABLE IF EXISTS capitulo;
DROP TABLE IF EXISTS favorito;
DROP TABLE IF EXISTS inscripcion_evento;
DROP TABLE IF EXISTS bloqueo_ip;
DROP TABLE IF EXISTS bloqueo_usuario;
DROP TABLE IF EXISTS home_noticia;
DROP TABLE IF EXISTS home_carousel;
DROP TABLE IF EXISTS comentario;
DROP TABLE IF EXISTS post;
DROP TABLE IF EXISTS evento;
DROP TABLE IF EXISTS juego;
DROP TABLE IF EXISTS saga;
DROP TABLE IF EXISTS plataformas;
DROP TABLE IF EXISTS usuario;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuario (
	usuario_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
	email VARCHAR(100) NOT NULL UNIQUE,
	contrasena VARCHAR(255) NOT NULL,
	avatar VARCHAR(255) DEFAULT NULL,
	ip_registro VARCHAR(45) DEFAULT NULL,
	rol ENUM('usuario', 'moderador', 'admin') NOT NULL DEFAULT 'usuario',
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bloqueo_usuario (
	usuario_id INT UNSIGNED PRIMARY KEY,
	motivo VARCHAR(255) DEFAULT NULL,
	bloqueado_hasta DATETIME DEFAULT NULL,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_bloqueo_usuario_usuario
		FOREIGN KEY (usuario_id)
		REFERENCES usuario(usuario_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bloqueo_ip (
	ip VARCHAR(45) PRIMARY KEY,
	motivo VARCHAR(255) DEFAULT NULL,
	bloqueado_hasta DATETIME DEFAULT NULL,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE home_carousel (
	carousel_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	titulo VARCHAR(150) NOT NULL,
	descripcion TEXT,
	imagen VARCHAR(255) NOT NULL,
	orden SMALLINT UNSIGNED NOT NULL DEFAULT 1,
	activo TINYINT(1) NOT NULL DEFAULT 1,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE home_noticia (
	noticia_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	titulo VARCHAR(150) NOT NULL,
	texto TEXT NOT NULL,
	imagen VARCHAR(255) NOT NULL,
	orden SMALLINT UNSIGNED NOT NULL DEFAULT 1,
	activo TINYINT(1) NOT NULL DEFAULT 1,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE plataformas (
	plataforma_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	nombre VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE saga (
	saga_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	nombre VARCHAR(100) NOT NULL UNIQUE,
	descripcion TEXT
) ENGINE=InnoDB;

CREATE TABLE juego (
	juego_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	titulo VARCHAR(100) NOT NULL UNIQUE,
	saga_id INT UNSIGNED DEFAULT NULL,
	plataforma_id INT UNSIGNED DEFAULT NULL,
	ano_lanzamiento YEAR DEFAULT NULL,
	descripcion TEXT,
	imagen VARCHAR(255),
	CONSTRAINT fk_juego_saga
		FOREIGN KEY (saga_id)
		REFERENCES saga(saga_id)
		ON DELETE SET NULL
		ON UPDATE CASCADE,
	CONSTRAINT fk_juego_plataforma
		FOREIGN KEY (plataforma_id)
		REFERENCES plataformas(plataforma_id)
		ON DELETE SET NULL
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post (
	post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	usuario_id INT UNSIGNED NOT NULL,
	titulo VARCHAR(100) NOT NULL,
	contenido TEXT NOT NULL,
	categoria VARCHAR(50),
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_post_usuario
		FOREIGN KEY (usuario_id)
		REFERENCES usuario(usuario_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE comentario (
	comentario_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	post_id INT UNSIGNED NOT NULL,
	usuario_id INT UNSIGNED NOT NULL,
	contenido TEXT NOT NULL,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	CONSTRAINT fk_comentario_post
		FOREIGN KEY (post_id)
		REFERENCES post(post_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT fk_comentario_usuario
		FOREIGN KEY (usuario_id)
		REFERENCES usuario(usuario_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE evento (
	evento_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	titulo VARCHAR(100) NOT NULL,
	descripcion TEXT,
	fecha_inscripcion DATE,
	fecha_inicio DATE,
	fecha_fin DATE,
	lugar VARCHAR(150),
	plazas SMALLINT UNSIGNED,
	precio DECIMAL(8,2) NOT NULL DEFAULT 0.00,
	creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE inscripcion_evento (
	usuario_id INT UNSIGNED NOT NULL,
	evento_id INT UNSIGNED NOT NULL,
	fecha_registro DATE NOT NULL DEFAULT (CURRENT_DATE),
	PRIMARY KEY (usuario_id, evento_id),
	CONSTRAINT fk_inscripcion_usuario
		FOREIGN KEY (usuario_id)
		REFERENCES usuario(usuario_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT fk_inscripcion_evento
		FOREIGN KEY (evento_id)
		REFERENCES evento(evento_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE favorito (
	usuario_id INT UNSIGNED NOT NULL,
	post_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (usuario_id, post_id),
	CONSTRAINT fk_favorito_usuario
		FOREIGN KEY (usuario_id)
		REFERENCES usuario(usuario_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT fk_favorito_post
		FOREIGN KEY (post_id)
		REFERENCES post(post_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE equipo (
	equipo_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	juego_id INT UNSIGNED NOT NULL,
	nombre VARCHAR(50) NOT NULL,
	descripcion TEXT,
	escudo VARCHAR(255),
	entrenador VARCHAR(100),
	uniforme VARCHAR(120),
	estilo_juego VARCHAR(120),
	CONSTRAINT fk_equipo_juego
		FOREIGN KEY (juego_id)
		REFERENCES juego(juego_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE personaje (
	personaje_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	juego_id INT UNSIGNED NOT NULL,
	nombre VARCHAR(100) NOT NULL,
	posicion ENUM('POR', 'DF', 'MD', 'DL') NOT NULL,
	elemento ENUM('Fuego', 'Aire', 'Bosque', 'Montania', 'Neutral') NOT NULL,
	descripcion TEXT,
	estilo_juego VARCHAR(120),
	imagen VARCHAR(255),
	pe SMALLINT UNSIGNED DEFAULT NULL,
	pt SMALLINT UNSIGNED DEFAULT NULL,
	tiro SMALLINT UNSIGNED DEFAULT NULL,
	control SMALLINT UNSIGNED DEFAULT NULL,
	defensa SMALLINT UNSIGNED DEFAULT NULL,
	rapidez SMALLINT UNSIGNED DEFAULT NULL,
	fisico SMALLINT UNSIGNED DEFAULT NULL,
	aguante SMALLINT UNSIGNED DEFAULT NULL,
	CONSTRAINT fk_personaje_juego
		FOREIGN KEY (juego_id)
		REFERENCES juego(juego_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE supertecnica (
	supertecnica_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	juego_id INT UNSIGNED NOT NULL,
	nombre VARCHAR(100) NOT NULL,
	tipo ENUM('POR', 'DF', 'BLQ', 'REG', 'TIR', 'LAR', 'CAD') NOT NULL,
	poder SMALLINT UNSIGNED,
	coste_pe SMALLINT UNSIGNED,
	nivel_desbloqueo SMALLINT UNSIGNED,
	descripcion TEXT,
	elemento ENUM('Fuego', 'Aire', 'Bosque', 'Montania', 'Neutral') NOT NULL,
	video VARCHAR(255),
	CONSTRAINT fk_supertecnica_juego
		FOREIGN KEY (juego_id)
		REFERENCES juego(juego_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE coleccionable (
	coleccionable_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	juego_id INT UNSIGNED NOT NULL,
	nombre VARCHAR(100) NOT NULL,
	tipo ENUM('Foto', 'Escudo', 'Objeto', 'Equipacion') NOT NULL,
	descripcion TEXT,
	rareza VARCHAR(50) DEFAULT NULL,
	categoria VARCHAR(100) DEFAULT NULL,
	localizaciones TEXT,
	curiosidades TEXT,
	tecnicas TEXT,
	imagen VARCHAR(255),
	CONSTRAINT fk_coleccionable_juego
		FOREIGN KEY (juego_id)
		REFERENCES juego(juego_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE capitulo (
	capitulo_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	juego_id INT UNSIGNED NOT NULL,
	numero SMALLINT UNSIGNED NOT NULL,
	titulo VARCHAR(150) NOT NULL,
	descripcion TEXT,
	CONSTRAINT fk_capitulo_juego
		FOREIGN KEY (juego_id)
		REFERENCES juego(juego_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	UNIQUE KEY uq_capitulo_juego_numero (juego_id, numero)
) ENGINE=InnoDB;

CREATE TABLE personaje_equipo (
	personaje_id INT UNSIGNED NOT NULL,
	equipo_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (personaje_id, equipo_id),
	CONSTRAINT fk_personaje_equipo_personaje
		FOREIGN KEY (personaje_id)
		REFERENCES personaje(personaje_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT fk_personaje_equipo_equipo
		FOREIGN KEY (equipo_id)
		REFERENCES equipo(equipo_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tecnicas_personaje (
	personaje_id INT UNSIGNED NOT NULL,
	supertecnica_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (personaje_id, supertecnica_id),
	CONSTRAINT fk_tecnicas_personaje_personaje
		FOREIGN KEY (personaje_id)
		REFERENCES personaje(personaje_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE,
	CONSTRAINT fk_tecnicas_personaje_supertecnica
		FOREIGN KEY (supertecnica_id)
		REFERENCES supertecnica(supertecnica_id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ==============================================
-- INSERT PARA PROBAR
-- ==============================================

INSERT INTO usuario (usuario_id, nombre_usuario, email, contrasena, avatar, ip_registro, rol) VALUES
	(1, 'admin_ivan', 'admin@inamania.test', '$2y$10$cNYTZ.gvlflwipvwryt6Cu7ntre5Ul7ql4hd/Oi6md0DVq0l6Cjsi', NULL, '127.0.0.1', 'admin'),
	(2, 'mod_lucia', 'mod@inamania.test', '$2y$10$cNYTZ.gvlflwipvwryt6Cu7ntre5Ul7ql4hd/Oi6md0DVq0l6Cjsi', NULL, '10.10.0.12', 'moderador'),
	(3, 'player_raul', 'raul@inamania.test', '$2y$10$cNYTZ.gvlflwipvwryt6Cu7ntre5Ul7ql4hd/Oi6md0DVq0l6Cjsi', NULL, '10.10.0.21', 'usuario'),
	(4, 'player_sara', 'sara@inamania.test', '$2y$10$cNYTZ.gvlflwipvwryt6Cu7ntre5Ul7ql4hd/Oi6md0DVq0l6Cjsi', NULL, '10.10.0.22', 'usuario'),
	(5, 'player_mario', 'mario@inamania.test', '$2y$10$cNYTZ.gvlflwipvwryt6Cu7ntre5Ul7ql4hd/Oi6md0DVq0l6Cjsi', NULL, '10.10.0.23', 'usuario');

-- Un bloqueo de usuario activo y otro vencido para probar ambos estados.
INSERT INTO bloqueo_usuario (usuario_id, motivo, bloqueado_hasta) VALUES
	(5, 'Spam recurrente en foro', DATE_ADD(NOW(), INTERVAL 7 DAY)),
	(4, 'Bloqueo antiguo de prueba', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Un bloqueo IP activo y otro vencido.
INSERT INTO bloqueo_ip (ip, motivo, bloqueado_hasta) VALUES
	('203.0.113.50', 'Intentos de acceso fallidos', DATE_ADD(NOW(), INTERVAL 3 DAY)),
	('203.0.113.60', 'Bloqueo de prueba expirado', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO home_carousel (carousel_id, titulo, descripcion, imagen, orden, activo) VALUES
	(1, 'Torneo Regional de Primavera', 'Inscripciones abiertas para equipos escolares.', 'uploads/imagenes/carrousel/Inazuma eleven mundial.jpg', 1, 1),
	(2, 'Nueva Wiki GO', 'Seccion GO actualizada con tecnicas y objetos.', 'uploads/imagenes/carrousel/inazuma eleven portrait.jpg', 2, 1),
	(3, 'Slide oculto de prueba', 'Este item sirve para validar el filtro de activos.', 'uploads/imagenes/carrousel/inazuma-eleven-heroes-victory-road-pc-steam-cover.jpg', 3, 0),
	(4, 'Victoria Road', 'Nuevas capturas y novedades del proximo juego.', 'uploads/imagenes/carrousel/inazuma-eleven-heroes-victory-road-pc-steam-cover.jpg', 4, 1);

INSERT INTO home_noticia (noticia_id, titulo, texto, imagen, orden, activo) VALUES
	(1, 'Actualizacion del foro', 'Ya puedes marcar publicaciones favoritas y filtrarlas en tu panel.', 'uploads/imagenes/noticias/Noticia.jpg', 1, 1),
	(2, 'Calendario de eventos', 'El calendario mensual muestra eventos abiertos y plazas restantes.', 'uploads/imagenes/noticias/Noticia2.jpg', 2, 1),
	(3, 'Noticia inactiva', 'Registro para probar contenido desactivado.', 'uploads/imagenes/noticias/Noticia.jpg', 3, 0);

INSERT INTO plataformas (plataforma_id, nombre) VALUES
	(1, 'Nintendo DS'),
	(2, 'Nintendo 3DS');

INSERT INTO saga (saga_id, nombre, descripcion) VALUES
	(1, 'Inazuma Eleven', 'Saga original de Inazuma Eleven.'),
	(2, 'Inazuma Eleven GO', 'Arco GO con nueva generacion de jugadores.');

INSERT INTO juego (juego_id, titulo, saga_id, plataforma_id, ano_lanzamiento, descripcion, imagen) VALUES
	(1, 'Inazuma Eleven', 1, 1, 2008, 'La aventura del Raimon para llegar al Football Frontier.', 'uploads/imagenes/juegos/Inazuma_eleven_caratula.webp'),
	(2, 'Inazuma Eleven GO Luz', 2, 2, 2011, 'Tenma y sus companeros luchan por recuperar el futbol.', 'uploads/imagenes/juegos/SN_BB_Logo_HD.png'),
	(3, 'Inazuma Eleven 2 Tormenta de Fuego', 1, 1, 2009, 'Segunda entrega de la saga original con nuevos rivales y tecnicas.', 'uploads/imagenes/juegos/Tormenta2.webp');

INSERT INTO capitulo (capitulo_id, juego_id, numero, titulo, descripcion) VALUES
	(1, 1, 1, 'Comienza la leyenda', 'Mark Evans funda el nuevo Raimon y busca jugadores.'),
	(2, 1, 2, 'Partido contra Royal Academy', 'Primer gran reto competitivo del equipo.'),
	(3, 2, 1, 'El futbol controlado', 'Tenma descubre las reglas de Fifth Sector.'),
	(4, 2, 2, 'Resistencia de Raimon GO', 'El equipo empieza a rebelarse contra el sistema.');

INSERT INTO equipo (equipo_id, juego_id, nombre, descripcion, escudo, entrenador, uniforme, estilo_juego) VALUES
	(1, 1, 'Raimon', 'Equipo protagonista de la saga original.', 'uploads/imagenes/equipos/Raimon_FF_29.jpg', 'Nelly Raimon', 'Azul y amarillo', 'Ofensivo y creativo'),
	(2, 1, 'Royal Academy', 'Rival clasico con gran disciplina tactica.', 'uploads/imagenes/equipos/Royal_Academy.png', 'Jude Sharp', 'Negro y dorado', 'Defensa organizada'),
	(3, 2, 'Raimon GO', 'Nueva generacion del Raimon liderada por Tenma.', 'uploads/imagenes/equipos/IEGO_Raimon.webp', 'Riccardo Di Rigo', 'Blanco y azul', 'Posesion y presion alta');

INSERT INTO personaje (personaje_id, juego_id, nombre, posicion, elemento, descripcion, estilo_juego, imagen, pe, pt, tiro, control, defensa, rapidez, fisico, aguante) VALUES
	(1, 1, 'Mark Evans', 'POR', 'Montania', 'Capitan y portero del Raimon.', 'Liderazgo y reflejos', 'uploads/imagenes/personajes/markevans_01.png', 180, 120, 55, 70, 88, 62, 74, 81),
	(2, 1, 'Axel Blaze', 'DL', 'Fuego', 'Delantero estrella con gran potencia de tiro.', 'Ataque explosivo', 'uploads/imagenes/personajes/davidsamford_01.png', 160, 110, 95, 76, 50, 84, 80, 68),
	(3, 1, 'Jude Sharp', 'MD', 'Aire', 'Cerebro tactico de Royal Academy.', 'Control y pase', 'uploads/imagenes/personajes/markevans_01.png', 170, 115, 72, 92, 68, 71, 63, 75),
	(4, 2, 'Arion Sherwind', 'MD', 'Aire', 'Motor del equipo Raimon GO.', 'Dinamismo y tecnica', 'uploads/imagenes/personajes/davidsamford_01.png', 175, 118, 78, 90, 65, 89, 70, 79),
	(5, 2, 'Victor Blade', 'DL', 'Fuego', 'Goleador de gran precision en GO.', 'Definicion y desmarque', 'uploads/imagenes/personajes/markevans_01.png', 168, 112, 93, 82, 54, 86, 77, 72);

INSERT INTO supertecnica (supertecnica_id, juego_id, nombre, tipo, poder, coste_pe, nivel_desbloqueo, descripcion, elemento, video) VALUES
	(1, 1, 'God Hand', 'POR', 120, 28, 12, 'Parada iconica de Mark Evans.', 'Montania', 'uploads/imagenes/supertecnicas/Mano.webp'),
	(2, 1, 'Fire Tornado', 'TIR', 130, 32, 14, 'Tiro giratorio de Axel Blaze.', 'Fuego', 'uploads/imagenes/supertecnicas/Pajaro_de_Fuego.webp'),
	(3, 2, 'Mach Wind', 'REG', 110, 24, 10, 'Regate veloz de Arion Sherwind.', 'Aire', 'uploads/imagenes/supertecnicas/Tri_Pegaso.webp'),
	(4, 2, 'Death Sword', 'TIR', 140, 36, 16, 'Remate de alta potencia de Victor Blade.', 'Fuego', 'uploads/imagenes/supertecnicas/Lanza_Helada.webp');

INSERT INTO coleccionable (coleccionable_id, juego_id, nombre, tipo, descripcion, rareza, categoria, localizaciones, curiosidades, tecnicas, imagen) VALUES
	(1, 1, 'Foto del Raimon inicial', 'Foto', 'Instantanea del primer once del Raimon.', 'Comun', 'Historia', 'Club de futbol Raimon', 'Aparece en escenas de inicio.', 'God Hand, Fire Tornado', 'uploads/imagenes/objetos/Foto_Torneo.webp'),
	(2, 1, 'Escudo Royal Academy', 'Escudo', 'Emblema oficial de Royal Academy.', 'Rara', 'Equipo', 'Zona VIP del estadio Royal', 'Representa la disciplina del equipo.', 'Ninguna', 'uploads/imagenes/equipos/Escudo_-_Royal_Academy_29.webp'),
	(3, 2, 'Botas de Arion', 'Objeto', 'Botas usadas por Arion Sherwind.', 'Epica', 'Personaje', 'Vestuario de Raimon GO', 'Mejoran control en lluvia.', 'Mach Wind', 'uploads/imagenes/objetos/Bota_Cronica.webp'),
	(4, 2, 'Uniforme alternativo GO', 'Equipacion', 'Segunda equipacion del Raimon GO.', 'Rara', 'Equipo', 'Tienda de Raimon GO', 'Disponible tras capitulo 2.', 'Death Sword', 'uploads/imagenes/objetos/cuaderno_royal.jpg'),
	(5, 1, 'Escudo Raimon', 'Escudo', 'Escudo oficial del Raimon FC.', 'Rara', 'Equipo', 'Club de futbol Raimon', 'Simbolo del espiritu competitivo del Raimon.', 'Ninguna', 'uploads/imagenes/objetos/Escudo_de_Raimon.jpg');

INSERT INTO personaje_equipo (personaje_id, equipo_id) VALUES
	(1, 1),
	(2, 1),
	(3, 2),
	(4, 3),
	(5, 3);

INSERT INTO tecnicas_personaje (personaje_id, supertecnica_id) VALUES
	(1, 1),
	(2, 2),
	(4, 3),
	(5, 4),
	(3, 1);

INSERT INTO post (post_id, usuario_id, titulo, contenido, categoria) VALUES
	(1, 1, 'Guia inicial para nuevos jugadores', 'Comparto una ruta rapida para avanzar en los primeros partidos.', 'Guia'),
	(2, 3, 'Mejor tecnica de tiro en IE1', 'Para mi Fire Tornado sigue siendo la mas eficiente en early game.', 'Debate'),
	(3, 4, 'Duda con alineaciones en GO', 'Que mediocampistas recomendais para control de balon?', 'Ayuda'),
	(4, 2, 'Torneo local de fin de mes', 'Buscamos equipos para scrims antes del evento presencial.', 'Comunidad');

INSERT INTO comentario (comentario_id, post_id, usuario_id, contenido) VALUES
	(1, 1, 3, 'Muy util, sobre todo la parte de gestion de PE.'),
	(2, 1, 2, 'Anadiria tambien una seccion de entrenamientos.'),
	(3, 2, 1, 'Coincido, Fire Tornado rinde genial en historia.'),
	(4, 3, 4, 'Prueba con Arion y un pivote defensivo.');

INSERT INTO favorito (usuario_id, post_id) VALUES
	(3, 1),
	(4, 1),
	(1, 2),
	(2, 2),
	(3, 4);

INSERT INTO evento (evento_id, titulo, descripcion, fecha_inscripcion, fecha_inicio, fecha_fin, lugar, plazas, precio) VALUES
	(1, 'Liga Escolar Abierta', 'Evento gratuito para equipos novatos.', DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), 'Polideportivo Norte', 16, 0.00),
	(2, 'Bootcamp Tactico Premium', 'Entrenamiento avanzado con analisis de partidos.', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'Centro Elite Inamania', 30, 25.00),
	(3, 'Copa Relampago', 'Formato rapido con plazas limitadas.', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Pabellon Central', 2, 12.00),
	(4, 'Clinica de Porteros', 'Sesion especializada para posicion POR.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'Campo Sur', 12, 0.00),
	(5, 'Final Regional Temporada Pasada', 'Evento historico ya finalizado.', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 11 DAY), 'Estadio Memorial', 40, 18.00),
	(6, 'Open Day Comunidad', 'Jornada abierta sin limite de plazas.', DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'Plaza Central', NULL, 0.00);

-- Inscripciones para cubrir recomendados, evento lleno y seccion de inscritos.
INSERT INTO inscripcion_evento (usuario_id, evento_id, fecha_registro) VALUES
	(1, 1, CURDATE()),
	(2, 1, CURDATE()),
	(3, 2, CURDATE()),
	(1, 3, CURDATE()),
	(4, 3, CURDATE()),
	(3, 5, DATE_SUB(CURDATE(), INTERVAL 15 DAY));
