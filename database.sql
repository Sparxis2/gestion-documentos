-- ============================================================
--  Gestión de Documentos · Oficina Digital
--  Local (XAMPP): importar en phpMyAdmin con la base "appweb" seleccionada.
--  Nube: no hace falta importarlo; el sistema crea las tablas solo
--  la primera vez que se abre (includes/almacen.php → preparar_bd).
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre        VARCHAR(120) NOT NULL,
  usuario       VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol           ENUM('admin','capturista') NOT NULL DEFAULT 'capturista',
  foto          VARCHAR(255) DEFAULT NULL,
  activo        TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  no_oficio        VARCHAR(30)  NOT NULL,
  fecha            DATE         NOT NULL,
  asunto           VARCHAR(255) NOT NULL,
  remitente        VARCHAR(150) NOT NULL,
  destinatario     VARCHAR(150) NOT NULL,
  estatus          ENUM('Recibido','En proceso','Atendido') NOT NULL DEFAULT 'Recibido',
  -- Solo se guarda el NOMBRE del archivo; el archivo vive en la carpeta pdf/
  archivo          VARCHAR(255) DEFAULT NULL,
  archivo_original VARCHAR(255) DEFAULT NULL,
  registrado_por   INT UNSIGNED DEFAULT NULL,
  creado_en        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_documentos_fecha (fecha),
  KEY idx_documentos_estatus (estatus),
  KEY idx_documentos_oficio (no_oficio),
  CONSTRAINT fk_documentos_usuario FOREIGN KEY (registrado_por)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Archivos guardados dentro de MySQL (solo en la nube, donde no hay disco permanente)
CREATE TABLE IF NOT EXISTS archivos (
  clave     VARCHAR(300) NOT NULL,
  tipo      VARCHAR(100) NOT NULL,
  tamano    INT UNSIGNED NOT NULL,
  datos     LONGBLOB     NOT NULL,
  creado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesiones de usuario (solo en la nube, donde cada petición puede ir a otro servidor)
CREATE TABLE IF NOT EXISTS sesiones (
  id          VARCHAR(128) NOT NULL,
  datos       MEDIUMBLOB   NOT NULL,
  actualizado INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sesiones_actualizado (actualizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracion (
  clave VARCHAR(50)  NOT NULL,
  valor VARCHAR(255) NOT NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario inicial:  admin / admin123   (cámbiala en Configuración)
INSERT IGNORE INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES
('Administrador', 'admin', '$2y$10$RmUWL.GaOwj3T.JIqtNPduj8fH/vFFh1dA1Nev4Ek8dE4BV2vh1wS', 'admin', 1);

INSERT IGNORE INTO configuracion (clave, valor) VALUES
('institucion', 'UNACH'),
('sistema',     'Gestión de Documentos'),
('subtitulo',   'Oficina Digital'),
('area',        'ETDA · Campus IV'),
('por_pagina',  '10'),
('version_bd',  '3');
