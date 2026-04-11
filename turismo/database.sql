-- Base de datos: Lugares Turísticos de San Miguel
CREATE DATABASE IF NOT EXISTS turismo_sanmiguel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE turismo_sanmiguel;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de lugares turísticos
CREATE TABLE IF NOT EXISTS lugares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    categoria ENUM('natural', 'cultural', 'historico', 'religioso', 'recreativo') NOT NULL,
    descripcion TEXT NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    horario VARCHAR(100),
    entrada DECIMAL(6,2) DEFAULT 0.00,
    calificacion TINYINT CHECK (calificacion BETWEEN 1 AND 5),
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

INSERT INTO usuarios (nombre, email, password) VALUES
('admin', 'administrador@turismo.com', '198420');
