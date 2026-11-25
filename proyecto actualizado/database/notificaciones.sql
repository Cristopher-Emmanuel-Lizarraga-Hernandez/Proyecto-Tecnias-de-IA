USE pase_inteligente_escolar;

-- Tabla de profesores
CREATE TABLE profesores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de aulas
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    profesor_id INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (profesor_id) 
        REFERENCES profesores(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- Relacionar alumnos con aulas
ALTER TABLE alumnos
ADD COLUMN aula_id INT NULL,
ADD CONSTRAINT fk_alumnos_aulas
    FOREIGN KEY (aula_id)
    REFERENCES aulas(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- Historial de notificaciones
CREATE TABLE notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bitacora_id INT NOT NULL,
    profesor_id INT NULL,
    correo_destino VARCHAR(150) NOT NULL,
    asunto VARCHAR(200) NOT NULL,
    mensaje TEXT NOT NULL,
    estado ENUM(
        'pendiente',
        'enviada',
        'fallida'
    ) NOT NULL DEFAULT 'pendiente',

    intentos INT UNSIGNED DEFAULT 0,
    error_envio TEXT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_envio DATETIME NULL,

    UNIQUE KEY uk_notificacion_bitacora (bitacora_id),

    FOREIGN KEY (bitacora_id)
        REFERENCES bitacora_recogida(id)
        ON DELETE RESTRICT,

    FOREIGN KEY (profesor_id)
        REFERENCES profesores(id)
        ON DELETE SET NULL
);