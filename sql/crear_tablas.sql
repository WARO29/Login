-- Tabla de estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    documento VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    grado VARCHAR(2) NOT NULL,
    grupo VARCHAR(1) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de docentes (si no existe ya)
CREATE TABLE IF NOT EXISTS docentes (
    codigo_docente VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) NOT NULL,
    area VARCHAR(50) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de representante_docente (si no existe ya)
CREATE TABLE IF NOT EXISTS representante_docente (
    codigo_repres_docente VARCHAR(20) PRIMARY KEY,
    nombre_repre_docente VARCHAR(100) NOT NULL,
    correo_repre_docente VARCHAR(100) NOT NULL,
    telefono_repre_docente VARCHAR(15),
    direccion_repre_docente VARCHAR(200),
    cargo_repre_docente VARCHAR(50) NOT NULL,
    propuesta_repre_docente VARCHAR(255)
);
