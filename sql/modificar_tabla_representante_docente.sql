-- Modificar la tabla representante_docente para usar codigo_repres_docente como llave primaria
-- Primero, eliminamos la tabla existente
DROP TABLE IF EXISTS representante_docente;

-- Creamos la tabla nuevamente con codigo_repres_docente como llave primaria
CREATE TABLE IF NOT EXISTS representante_docente (
    codigo_repres_docente VARCHAR(20) PRIMARY KEY,
    nombre_repre_docente VARCHAR(100) NOT NULL,
    correo_repre_docente VARCHAR(100) NOT NULL UNIQUE,
    telefono_repre_docente VARCHAR(20),
    direccion_repre_docente VARCHAR(200),
    cargo_repre_docente VARCHAR(100) NOT NULL,
    propuesta_repre_docente VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
