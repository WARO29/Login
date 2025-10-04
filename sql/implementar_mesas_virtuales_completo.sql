-- =====================================================
-- IMPLEMENTACIÓN COMPLETA DE MESAS VIRTUALES
-- Incluye: Preescolar, 1°, 2°, 3°, 4°, 5°, 6°, 7°, 8°, 9°, 10°, 11°
-- =====================================================

-- 1. CREAR TABLAS PRINCIPALES
-- =====================================================

-- Tabla de mesas virtuales (actualizada para incluir id_eleccion)
CREATE TABLE IF NOT EXISTS mesas_virtuales (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    id_eleccion INT NOT NULL,
    nombre_mesa VARCHAR(100) NOT NULL,
    grado_asignado VARCHAR(15) NOT NULL, -- 'preescolar', '1', '2', ..., '11'
    estado_mesa ENUM('activa', 'cerrada') DEFAULT 'activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_eleccion) REFERENCES configuracion_elecciones(id) ON DELETE CASCADE,
    INDEX idx_eleccion_grado (id_eleccion, grado_asignado),
    INDEX idx_estado (estado_mesa)
);

-- Tabla de personal de mesa (jurados y testigos)
CREATE TABLE IF NOT EXISTS personal_mesa (
    id_personal INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    tipo_personal ENUM('jurado', 'testigo_docente', 'testigo_estudiante') NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    documento_identidad VARCHAR(20) NOT NULL,
    telefono VARCHAR(15),
    email VARCHAR(100),
    observaciones TEXT,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    INDEX idx_mesa_tipo (id_mesa, tipo_personal),
    INDEX idx_documento (documento_identidad)
);

-- Tabla de asignación de estudiantes a mesas
CREATE TABLE IF NOT EXISTS estudiantes_mesas (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante VARCHAR(20) NOT NULL,
    id_mesa INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_voto ENUM('pendiente', 'votado', 'ausente') DEFAULT 'pendiente',
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    INDEX idx_estudiante (id_estudiante),
    INDEX idx_mesa (id_mesa),
    INDEX idx_estado_voto (estado_voto),
    UNIQUE KEY unique_estudiante_mesa (id_estudiante, id_mesa)
);

-- 2. MODIFICAR TABLAS DE VOTOS PARA INCLUIR id_eleccion
-- =====================================================

-- Agregar id_eleccion a tabla votos (estudiantes)
ALTER TABLE votos ADD COLUMN IF NOT EXISTS id_eleccion INT;
ALTER TABLE votos ADD INDEX IF NOT EXISTS idx_eleccion (id_eleccion);

-- Agregar id_eleccion a tabla votos_docentes
ALTER TABLE votos_docentes ADD COLUMN IF NOT EXISTS id_eleccion INT;
ALTER TABLE votos_docentes ADD INDEX IF NOT EXISTS idx_eleccion (id_eleccion);

-- Agregar id_eleccion a tabla votos_administrativos (si existe)
CREATE TABLE IF NOT EXISTS votos_administrativos (
    id_voto INT AUTO_INCREMENT PRIMARY KEY,
    cedula_administrativo VARCHAR(20) NOT NULL,
    id_candidato_docente INT,
    voto_blanco_docente BOOLEAN DEFAULT FALSE,
    fecha_voto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_eleccion INT,
    INDEX idx_cedula (cedula_administrativo),
    INDEX idx_eleccion (id_eleccion)
);

-- 3. CREAR TABLA DE HISTÓRICO DE ELECCIONES
-- =====================================================

CREATE TABLE IF NOT EXISTS historico_elecciones (
    id_historico INT AUTO_INCREMENT PRIMARY KEY,
    id_eleccion INT NOT NULL,
    nombre_eleccion VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATETIME NOT NULL,
    fecha_cierre DATETIME NOT NULL,
    fecha_finalizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_estudiantes_habilitados INT DEFAULT 0,
    total_docentes_habilitados INT DEFAULT 0,
    total_administrativos_habilitados INT DEFAULT 0,
    total_votos_estudiantes INT DEFAULT 0,
    total_votos_docentes INT DEFAULT 0,
    total_votos_administrativos INT DEFAULT 0,
    porcentaje_participacion_estudiantes DECIMAL(5,2) DEFAULT 0.00,
    porcentaje_participacion_docentes DECIMAL(5,2) DEFAULT 0.00,
    porcentaje_participacion_administrativos DECIMAL(5,2) DEFAULT 0.00,
    ganador_estudiante VARCHAR(200),
    ganador_docente VARCHAR(200),
    tipos_votacion JSON,
    configuracion_adicional JSON,
    FOREIGN KEY (id_eleccion) REFERENCES configuracion_elecciones(id),
    INDEX idx_fecha_finalizacion (fecha_finalizacion),
    INDEX idx_eleccion (id_eleccion)
);

-- 4. PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER //

-- Procedimiento para crear mesas automáticamente para una elección
CREATE PROCEDURE CrearMesasParaEleccion(IN eleccion_id INT)
BEGIN
    -- Crear las 12 mesas (preescolar a 11°)
    INSERT INTO mesas_virtuales (id_eleccion, nombre_mesa, grado_asignado) VALUES
    (eleccion_id, 'Mesa Virtual Preescolar', 'preescolar'),
    (eleccion_id, 'Mesa Virtual Grado 1°', '1'),
    (eleccion_id, 'Mesa Virtual Grado 2°', '2'),
    (eleccion_id, 'Mesa Virtual Grado 3°', '3'),
    (eleccion_id, 'Mesa Virtual Grado 4°', '4'),
    (eleccion_id, 'Mesa Virtual Grado 5°', '5'),
    (eleccion_id, 'Mesa Virtual Grado 6°', '6'),
    (eleccion_id, 'Mesa Virtual Grado 7°', '7'),
    (eleccion_id, 'Mesa Virtual Grado 8°', '8'),
    (eleccion_id, 'Mesa Virtual Grado 9°', '9'),
    (eleccion_id, 'Mesa Virtual Grado 10°', '10'),
    (eleccion_id, 'Mesa Virtual Grado 11°', '11');
    
    SELECT CONCAT('Mesas creadas exitosamente para elección ID: ', eleccion_id) as mensaje;
END //

-- Procedimiento para asignar estudiantes a mesas por grado
CREATE PROCEDURE AsignarEstudiantesAMesasPorGrado(IN eleccion_id INT)
BEGIN
    -- Limpiar asignaciones previas para esta elección
    DELETE em FROM estudiantes_mesas em 
    JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa 
    WHERE mv.id_eleccion = eleccion_id;
    
    -- Asignar estudiantes activos a sus mesas correspondientes
    INSERT INTO estudiantes_mesas (id_estudiante, id_mesa)
    SELECT 
        e.id_estudiante,
        mv.id_mesa
    FROM estudiantes e
    JOIN mesas_virtuales mv ON e.grado = mv.grado_asignado
    WHERE e.estado = 1 
    AND mv.id_eleccion = eleccion_id;
    
    -- Mostrar resultado
    SELECT 
        CONCAT('Estudiantes asignados para elección ', eleccion_id) as mensaje,
        COUNT(*) as total_asignados
    FROM estudiantes_mesas em
    JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa
    WHERE mv.id_eleccion = eleccion_id;
END //

-- Procedimiento para finalizar elección y crear histórico
CREATE PROCEDURE FinalizarEleccionYCrearHistorico(IN eleccion_id INT)
BEGIN
    DECLARE eleccion_nombre VARCHAR(200);
    DECLARE eleccion_descripcion TEXT;
    DECLARE eleccion_inicio DATETIME;
    DECLARE eleccion_cierre DATETIME;
    DECLARE eleccion_tipos JSON;
    DECLARE eleccion_config JSON;
    
    -- Obtener datos de la elección
    SELECT nombre_eleccion, descripcion, fecha_inicio, fecha_cierre, tipos_votacion, configuracion_adicional
    INTO eleccion_nombre, eleccion_descripcion, eleccion_inicio, eleccion_cierre, eleccion_tipos, eleccion_config
    FROM configuracion_elecciones 
    WHERE id = eleccion_id;
    
    -- Crear registro histórico
    INSERT INTO historico_elecciones (
        id_eleccion, nombre_eleccion, descripcion, fecha_inicio, fecha_cierre,
        total_estudiantes_habilitados, total_docentes_habilitados, total_administrativos_habilitados,
        total_votos_estudiantes, total_votos_docentes, total_votos_administrativos,
        porcentaje_participacion_estudiantes, porcentaje_participacion_docentes, porcentaje_participacion_administrativos,
        tipos_votacion, configuracion_adicional
    )
    SELECT 
        eleccion_id,
        eleccion_nombre,
        eleccion_descripcion,
        eleccion_inicio,
        eleccion_cierre,
        (SELECT COUNT(*) FROM estudiantes WHERE estado = 1) as total_estudiantes,
        (SELECT COUNT(*) FROM docentes) as total_docentes,
        (SELECT COUNT(*) FROM administrativos) as total_administrativos,
        (SELECT COUNT(*) FROM votos WHERE id_eleccion = eleccion_id) as votos_estudiantes,
        (SELECT COUNT(*) FROM votos_docentes WHERE id_eleccion = eleccion_id) as votos_docentes,
        (SELECT COUNT(*) FROM votos_administrativos WHERE id_eleccion = eleccion_id) as votos_administrativos,
        ROUND((SELECT COUNT(*) FROM votos WHERE id_eleccion = eleccion_id) * 100.0 / 
              NULLIF((SELECT COUNT(*) FROM estudiantes WHERE estado = 1), 0), 2),
        ROUND((SELECT COUNT(*) FROM votos_docentes WHERE id_eleccion = eleccion_id) * 100.0 / 
              NULLIF((SELECT COUNT(*) FROM docentes), 0), 2),
        ROUND((SELECT COUNT(*) FROM votos_administrativos WHERE id_eleccion = eleccion_id) * 100.0 / 
              NULLIF((SELECT COUNT(*) FROM administrativos), 0), 2),
        eleccion_tipos,
        eleccion_config;
    
    -- Cambiar estado de la elección a cerrada
    UPDATE configuracion_elecciones 
    SET estado = 'cerrada' 
    WHERE id = eleccion_id;
    
    SELECT CONCAT('Elección ', eleccion_id, ' finalizada y guardada en histórico') as mensaje;
END //

DELIMITER ;

-- 5. DATOS DE EJEMPLO PARA TESTING
-- =====================================================

-- Crear mesas para la elección actual (ID 8)
-- CALL CrearMesasParaEleccion(8);

-- Asignar estudiantes a las mesas
-- CALL AsignarEstudiantesAMesasPorGrado(8);

-- 6. CONSULTAS ÚTILES
-- =====================================================

-- Ver todas las mesas de una elección con estadísticas
/*
SELECT 
    mv.nombre_mesa,
    mv.grado_asignado,
    COUNT(DISTINCT pm.id_personal) as personal_asignado,
    COUNT(DISTINCT em.id_estudiante) as estudiantes_asignados,
    SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) as votos_emitidos,
    ROUND(
        SUM(CASE WHEN em.estado_voto = 'votado' THEN 1 ELSE 0 END) * 100.0 / 
        NULLIF(COUNT(DISTINCT em.id_estudiante), 0), 2
    ) as porcentaje_participacion,
    CASE 
        WHEN COUNT(DISTINCT pm.id_personal) = 4 THEN 'COMPLETA' 
        ELSE 'INCOMPLETA' 
    END as estado_personal
FROM mesas_virtuales mv
LEFT JOIN personal_mesa pm ON mv.id_mesa = pm.id_mesa
LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
WHERE mv.id_eleccion = 8
GROUP BY mv.id_mesa
ORDER BY 
    CASE mv.grado_asignado 
        WHEN 'preescolar' THEN 0
        WHEN '1' THEN 1
        WHEN '2' THEN 2
        WHEN '3' THEN 3
        WHEN '4' THEN 4
        WHEN '5' THEN 5
        WHEN '6' THEN 6
        WHEN '7' THEN 7
        WHEN '8' THEN 8
        WHEN '9' THEN 9
        WHEN '10' THEN 10
        WHEN '11' THEN 11
    END;
*/

-- Ver personal de una mesa específica
/*
SELECT 
    mv.nombre_mesa,
    pm.tipo_personal,
    pm.nombre_completo,
    pm.documento_identidad,
    pm.telefono,
    pm.email
FROM mesas_virtuales mv
JOIN personal_mesa pm ON mv.id_mesa = pm.id_mesa
WHERE mv.id_mesa = 1
ORDER BY 
    CASE pm.tipo_personal 
        WHEN 'jurado' THEN 1 
        WHEN 'testigo_docente' THEN 2 
        WHEN 'testigo_estudiante' THEN 3 
    END;
*/

-- Ver histórico de elecciones
/*
SELECT 
    he.nombre_eleccion,
    he.fecha_inicio,
    he.fecha_cierre,
    he.fecha_finalizacion,
    he.total_votos_estudiantes,
    he.total_votos_docentes,
    he.porcentaje_participacion_estudiantes,
    he.porcentaje_participacion_docentes
FROM historico_elecciones he
ORDER BY he.fecha_finalizacion DESC;
*/
