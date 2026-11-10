-- ------------------------------------------------------------
-- Tabla: usuarios (acceso al panel web)
-- Ejecutar una vez en phpMyAdmin sobre la base pase_inteligente_escolar.
-- El primer administrador se crea con: php database/crear_admin.php
-- ------------------------------------------------------------
USE pase_inteligente_escolar;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin','personal') NOT NULL DEFAULT 'personal',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_correo (correo)
);