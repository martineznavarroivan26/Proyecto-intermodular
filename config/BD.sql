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