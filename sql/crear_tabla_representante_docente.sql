-- Crear tabla de representante-docente
CREATE TABLE IF NOT EXISTS representante_docente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_repres_docente VARCHAR(20) NOT NULL UNIQUE,
    nombre_repre_docente VARCHAR(100) NOT NULL,
    correo_repre_docente VARCHAR(100) NOT NULL UNIQUE,
    telefono_repre_docente VARCHAR(20),
    direccion_repre_docente VARCHAR(200),
    cargo_repre_docente VARCHAR(100) NOT NULL,
    propuesta_repre_docente VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
