-- =====================================================
-- SISTEMA DE MESAS VIRTUALES PARA VOTACIÓN
-- =====================================================

-- Tabla principal de mesas virtuales
CREATE TABLE mesas_virtuales (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    nombre_mesa VARCHAR(100) NOT NULL,
    descripcion TEXT,
    grado_asignado VARCHAR(10) NOT NULL, -- '6', '7', '8', '9', '10', '11'
    grupo_asignado VARCHAR(10), -- 'A', 'B', 'C', 'D' (opcional para mayor especificidad)
    capacidad_maxima INT DEFAULT 50, -- Máximo de estudiantes por mesa
    estado_mesa ENUM('activa', 'inactiva', 'cerrada') DEFAULT 'activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimizar consultas
    INDEX idx_grado (grado_asignado),
    INDEX idx_estado (estado_mesa),
    INDEX idx_grado_grupo (grado_asignado, grupo_asignado)
);

-- Tabla de asignación de estudiantes a mesas (relación muchos a muchos)
CREATE TABLE estudiantes_mesas (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante VARCHAR(20) NOT NULL, -- Referencia a estudiantes.id_estudiante
    id_mesa INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_asignacion ENUM('asignado', 'votado', 'ausente') DEFAULT 'asignado',
    
    -- Claves foráneas
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_estudiante (id_estudiante),
    INDEX idx_mesa (id_mesa),
    INDEX idx_estado_asignacion (estado_asignacion),
    
    -- Evitar duplicados: un estudiante solo puede estar en una mesa
    UNIQUE KEY unique_estudiante_mesa (id_estudiante, id_mesa)
);

-- Tabla de supervisores/administradores de mesa (opcional)
CREATE TABLE supervisores_mesa (
    id_supervisor INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    nombre_supervisor VARCHAR(100) NOT NULL,
    cedula_supervisor VARCHAR(20) NOT NULL,
    rol_supervisor ENUM('presidente', 'secretario', 'vocal') DEFAULT 'vocal',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Clave foránea
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_mesa_supervisor (id_mesa),
    INDEX idx_cedula (cedula_supervisor)
);

-- Tabla de estadísticas por mesa (para reportes)
CREATE TABLE estadisticas_mesa (
    id_estadistica INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    total_estudiantes_asignados INT DEFAULT 0,
    total_votos_emitidos INT DEFAULT 0,
    total_ausentes INT DEFAULT 0,
    porcentaje_participacion DECIMAL(5,2) DEFAULT 0.00,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clave foránea
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    
    -- Índice
    UNIQUE KEY unique_mesa_estadistica (id_mesa)
);

-- =====================================================
-- DATOS DE EJEMPLO
-- =====================================================

-- Insertar mesas virtuales por grado
INSERT INTO mesas_virtuales (nombre_mesa, descripcion, grado_asignado, grupo_asignado, capacidad_maxima) VALUES
-- Grado 6
('Mesa Virtual 6A', 'Mesa para estudiantes de grado 6 grupo A', '6', 'A', 30),
('Mesa Virtual 6B', 'Mesa para estudiantes de grado 6 grupo B', '6', 'B', 30),
('Mesa Virtual 6C', 'Mesa para estudiantes de grado 6 grupo C', '6', 'C', 30),

-- Grado 7
('Mesa Virtual 7A', 'Mesa para estudiantes de grado 7 grupo A', '7', 'A', 35),
('Mesa Virtual 7B', 'Mesa para estudiantes de grado 7 grupo B', '7', 'B', 35),

-- Grado 8
('Mesa Virtual 8A', 'Mesa para estudiantes de grado 8 grupo A', '8', 'A', 40),
('Mesa Virtual 8B', 'Mesa para estudiantes de grado 8 grupo B', '8', 'B', 40),

-- Grado 9
('Mesa Virtual 9A', 'Mesa para estudiantes de grado 9 grupo A', '9', 'A', 35),
('Mesa Virtual 9B', 'Mesa para estudiantes de grado 9 grupo B', '9', 'B', 35),

-- Grado 10
('Mesa Virtual 10A', 'Mesa para estudiantes de grado 10 grupo A', '10', 'A', 30),
('Mesa Virtual 10B', 'Mesa para estudiantes de grado 10 grupo B', '10', 'B', 30),

-- Grado 11
('Mesa Virtual 11A', 'Mesa para estudiantes de grado 11 grupo A', '11', 'A', 25),
('Mesa Virtual 11B', 'Mesa para estudiantes de grado 11 grupo B', '11', 'B', 25);

-- =====================================================
-- CONSULTAS ÚTILES DE EJEMPLO
-- =====================================================

-- Ver todas las mesas con su información
-- SELECT * FROM mesas_virtuales ORDER BY grado_asignado, grupo_asignado;

-- Contar estudiantes por mesa
-- SELECT 
--     mv.nombre_mesa,
--     mv.grado_asignado,
--     mv.grupo_asignado,
--     COUNT(em.id_estudiante) as total_estudiantes,
--     mv.capacidad_maxima,
--     (mv.capacidad_maxima - COUNT(em.id_estudiante)) as espacios_disponibles
-- FROM mesas_virtuales mv
-- LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
-- GROUP BY mv.id_mesa
-- ORDER BY mv.grado_asignado, mv.grupo_asignado;

-- Buscar mesa de un estudiante específico
-- SELECT 
--     e.nombre as nombre_estudiante,
--     e.grado,
--     e.grupo,
--     mv.nombre_mesa,
--     em.estado_asignacion
-- FROM estudiantes e
-- JOIN estudiantes_mesas em ON e.id_estudiante = em.id_estudiante
-- JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa
-- WHERE e.id_estudiante = '1234567890';

-- Estadísticas generales por grado
-- SELECT 
--     grado_asignado,
--     COUNT(*) as total_mesas,
--     SUM(capacidad_maxima) as capacidad_total,
--     AVG(capacidad_maxima) as capacidad_promedio
-- FROM mesas_virtuales 
-- WHERE estado_mesa = 'activa'
-- GROUP BY grado_asignado
-- ORDER BY grado_asignado;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS ÚTILES
-- =====================================================

DELIMITER //

-- Procedimiento para asignar automáticamente estudiantes a mesas por grado y grupo
CREATE PROCEDURE AsignarEstudiantesAMesas()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id_estudiante VARCHAR(20);
    DECLARE v_grado VARCHAR(10);
    DECLARE v_grupo VARCHAR(10);
    DECLARE v_id_mesa INT;
    
    -- Cursor para recorrer estudiantes sin mesa asignada
    DECLARE estudiante_cursor CURSOR FOR 
        SELECT e.id_estudiante, e.grado, e.grupo
        FROM estudiantes e
        LEFT JOIN estudiantes_mesas em ON e.id_estudiante = em.id_estudiante
        WHERE em.id_estudiante IS NULL
        AND e.estado = 'activo';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN estudiante_cursor;
    
    read_loop: LOOP
        FETCH estudiante_cursor INTO v_id_estudiante, v_grado, v_grupo;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Buscar mesa disponible para el grado y grupo
        SELECT mv.id_mesa INTO v_id_mesa
        FROM mesas_virtuales mv
        LEFT JOIN (
            SELECT id_mesa, COUNT(*) as ocupados
            FROM estudiantes_mesas
            GROUP BY id_mesa
        ) ocupacion ON mv.id_mesa = ocupacion.id_mesa
        WHERE mv.grado_asignado = v_grado
        AND (mv.grupo_asignado = v_grupo OR mv.grupo_asignado IS NULL)
        AND mv.estado_mesa = 'activa'
        AND (ocupacion.ocupados < mv.capacidad_maxima OR ocupacion.ocupados IS NULL)
        ORDER BY ocupacion.ocupados ASC
        LIMIT 1;
        
        -- Si se encontró mesa disponible, asignar estudiante
        IF v_id_mesa IS NOT NULL THEN
            INSERT INTO estudiantes_mesas (id_estudiante, id_mesa)
            VALUES (v_id_estudiante, v_id_mesa);
        END IF;
        
        SET v_id_mesa = NULL;
    END LOOP;
    
    CLOSE estudiante_cursor;
END //

-- Procedimiento para actualizar estadísticas de mesas
CREATE PROCEDURE ActualizarEstadisticasMesas()
BEGIN
    -- Limpiar estadísticas existentes
    DELETE FROM estadisticas_mesa;
    
    -- Insertar nuevas estadísticas
    INSERT INTO estadisticas_mesa (
        id_mesa, 
        total_estudiantes_asignados, 
        total_votos_emitidos, 
        total_ausentes, 
        porcentaje_participacion
    )
    SELECT 
        mv.id_mesa,
        COUNT(em.id_estudiante) as total_asignados,
        SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) as total_votado,
        SUM(CASE WHEN em.estado_asignacion = 'ausente' THEN 1 ELSE 0 END) as total_ausente,
        CASE 
            WHEN COUNT(em.id_estudiante) > 0 THEN
                (SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) * 100.0 / COUNT(em.id_estudiante))
            ELSE 0
        END as porcentaje
    FROM mesas_virtuales mv
    LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
    GROUP BY mv.id_mesa;
END //

DELIMITER ;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX idx_grado_grupo_estado ON mesas_virtuales(grado_asignado, grupo_asignado, estado_mesa);

-- Índice para consultas de estadísticas
CREATE INDEX idx_estudiante_estado ON estudiantes_mesas(id_estudiante, estado_asignacion);

-- =====================================================
-- COMENTARIOS Y NOTAS
-- =====================================================

/*
ESTRUCTURA DEL SISTEMA DE MESAS VIRTUALES:

1. MESAS_VIRTUALES: Tabla principal que define cada mesa virtual
   - Cada mesa está asociada a un grado específico
   - Opcionalmente puede estar asociada a un grupo específico
   - Tiene capacidad máxima configurable
   - Estado controlable (activa/inactiva/cerrada)

2. ESTUDIANTES_MESAS: Tabla de relación que asigna estudiantes a mesas
   - Relación muchos a muchos entre estudiantes y mesas
   - Control de estado de cada asignación
   - Previene duplicados con clave única

3. SUPERVISORES_MESA: Tabla opcional para asignar supervisores
   - Permite asignar personal responsable de cada mesa
   - Diferentes roles (presidente, secretario, vocal)

4. ESTADISTICAS_MESA: Tabla para reportes y análisis
   - Estadísticas calculadas automáticamente
   - Porcentajes de participación
   - Totales por mesa

VENTAJAS DEL DISEÑO:
- Escalable: Fácil agregar nuevas mesas o modificar existentes
- Flexible: Permite mesas por grado, por grupo, o mixtas
- Controlable: Estados para activar/desactivar mesas
- Auditable: Fechas de creación y modificación
- Eficiente: Índices optimizados para consultas frecuentes
- Estadístico: Reportes automáticos de participación

USO RECOMENDADO:
1. Crear mesas según la estructura de grados de la institución
2. Asignar estudiantes automáticamente con el procedimiento
3. Monitorear participación con las estadísticas
4. Generar reportes por mesa, grado o general
*/