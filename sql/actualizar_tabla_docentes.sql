-- Actualizar tabla docentes con los campos solicitados
DROP TABLE IF EXISTS docentes;

CREATE TABLE IF NOT EXISTS docentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_docente VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    direccion VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos datos de ejemplo (opcional)
-- INSERT INTO docentes (codigo_docente, nombre, correo, telefono, direccion) VALUES
--    ('DOC001', 'Juan Pérez', 'juan.perez@universidad.edu', '3001234567', 'Calle 123 #45-67'),
--    ('DOC002', 'María López', 'maria.lopez@universidad.edu', '3109876543', 'Avenida 45 #12-34');
