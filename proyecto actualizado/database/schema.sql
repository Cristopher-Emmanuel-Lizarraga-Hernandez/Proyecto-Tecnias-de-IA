-- ============================================================
-- Base de datos: pase_inteligente_escolar
-- Sistema de Autenticación Biométrica Facial para Recogida Escolar
-- ============================================================

CREATE DATABASE IF NOT EXISTS pase_inteligente_escolar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pase_inteligente_escolar;

-- ------------------------------------------------------------
-- Tabla: alumnos
-- ------------------------------------------------------------
CREATE TABLE alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    grupo VARCHAR(20),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Tabla: tutores
-- Cada tutor está asociado a un alumno y a un "embedding_label",
-- que es el identificador numérico que usa el modelo de
-- reconocimiento facial (LBPH/embeddings) para referenciarlo.
-- ------------------------------------------------------------
CREATE TABLE tutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    alumno_id INT NOT NULL,
    embedding_label INT NOT NULL,
    parentesco VARCHAR(50) DEFAULT 'tutor',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uk_embedding_label (embedding_label)
);

-- ------------------------------------------------------------
-- Tabla: bitacora_recogida
-- Registro auditable de cada intento de recogida, autorizado o no.
-- tutor_id/alumno_id quedan NULL cuando no hubo coincidencia
-- (persona no reconocida).
-- ------------------------------------------------------------
CREATE TABLE bitacora_recogida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NULL,
    alumno_id INT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confianza DECIMAL(5,2),
    autorizado TINYINT(1) NOT NULL,
    FOREIGN KEY (tutor_id) REFERENCES tutores(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- (Sin datos de ejemplo: los registros se crean con registrar.py o el panel web)

-- Consulta de ejemplo: historial con nombres (para el panel administrativo)
-- SELECT b.fecha_hora, t.nombre AS tutor, a.nombre AS alumno, b.confianza, b.autorizado
-- FROM bitacora_recogida b
-- LEFT JOIN tutores t ON b.tutor_id = t.id
-- LEFT JOIN alumnos a ON b.alumno_id = a.id
-- ORDER BY b.fecha_hora DESC;
