# Diseño de Sistema de Mesas Virtuales

## Concepto General

El sistema de **mesas virtuales** permite organizar a los estudiantes en grupos lógicos para facilitar la administración del proceso de votación, similar a las mesas de votación tradicionales pero adaptado al entorno digital.

## Estructura de Tablas

### 📋 1. Tabla Principal: `mesas_virtuales`

```sql
CREATE TABLE mesas_virtuales (
    id_mesa INT AUTO_INCREMENT PRIMARY KEY,
    nombre_mesa VARCHAR(100) NOT NULL,
    descripcion TEXT,
    grado_asignado VARCHAR(10) NOT NULL,
    grupo_asignado VARCHAR(10),
    capacidad_maxima INT DEFAULT 50,
    estado_mesa ENUM('activa', 'inactiva', 'cerrada') DEFAULT 'activa',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Propósito**: Define cada mesa virtual con sus características y limitaciones.

**Campos Clave**:
- `grado_asignado`: Grado específico (6, 7, 8, 9, 10, 11)
- `grupo_asignado`: Grupo específico (A, B, C, D) - opcional
- `capacidad_maxima`: Límite de estudiantes por mesa
- `estado_mesa`: Control de disponibilidad

### 🔗 2. Tabla de Relación: `estudiantes_mesas`

```sql
CREATE TABLE estudiantes_mesas (
    id_asignacion INT AUTO_INCREMENT PRIMARY KEY,
    id_estudiante VARCHAR(20) NOT NULL,
    id_mesa INT NOT NULL,
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_asignacion ENUM('asignado', 'votado', 'ausente') DEFAULT 'asignado',
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_mesa (id_estudiante, id_mesa)
);
```

**Propósito**: Relaciona estudiantes con sus mesas asignadas y controla su estado de participación.

**Estados de Asignación**:
- `asignado`: Estudiante asignado pero no ha votado
- `votado`: Estudiante ha completado su votación
- `ausente`: Estudiante no participó en la votación

### 👥 3. Tabla Opcional: `supervisores_mesa`

```sql
CREATE TABLE supervisores_mesa (
    id_supervisor INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    nombre_supervisor VARCHAR(100) NOT NULL,
    cedula_supervisor VARCHAR(20) NOT NULL,
    rol_supervisor ENUM('presidente', 'secretario', 'vocal') DEFAULT 'vocal',
    fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE
);
```

**Propósito**: Asigna personal responsable para supervisar cada mesa virtual.

### 📊 4. Tabla de Estadísticas: `estadisticas_mesa`

```sql
CREATE TABLE estadisticas_mesa (
    id_estadistica INT AUTO_INCREMENT PRIMARY KEY,
    id_mesa INT NOT NULL,
    total_estudiantes_asignados INT DEFAULT 0,
    total_votos_emitidos INT DEFAULT 0,
    total_ausentes INT DEFAULT 0,
    porcentaje_participacion DECIMAL(5,2) DEFAULT 0.00,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_mesa) REFERENCES mesas_virtuales(id_mesa) ON DELETE CASCADE
);
```

**Propósito**: Almacena estadísticas calculadas para reportes y análisis.

## Relaciones del Sistema

```
┌─────────────────┐    1:N    ┌──────────────────┐    N:1    ┌─────────────┐
│  mesas_virtuales│◄──────────┤estudiantes_mesas │──────────►│ estudiantes │
└─────────────────┘           └──────────────────┘           └─────────────┘
         │ 1:N                                                        
         ▼                                                            
┌─────────────────┐                                                   
│supervisores_mesa│                                                   
└─────────────────┘                                                   
         │ 1:1                                                        
         ▼                                                            
┌─────────────────┐                                                   
│estadisticas_mesa│                                                   
└─────────────────┘                                                   
```

## Casos de Uso

### 🎯 Escenario 1: Organización por Grado
```sql
-- Mesa para todo el grado 6
INSERT INTO mesas_virtuales (nombre_mesa, grado_asignado, capacidad_maxima) 
VALUES ('Mesa Grado 6', '6', 100);
```

### 🎯 Escenario 2: Organización por Grado y Grupo
```sql
-- Mesa específica para grado 7, grupo A
INSERT INTO mesas_virtuales (nombre_mesa, grado_asignado, grupo_asignado, capacidad_maxima) 
VALUES ('Mesa 7A', '7', 'A', 35);
```

### 🎯 Escenario 3: Múltiples Mesas por Grado
```sql
-- Varias mesas para grado 11 (por capacidad)
INSERT INTO mesas_virtuales (nombre_mesa, grado_asignado, capacidad_maxima) VALUES
('Mesa 11 - Sección 1', '11', 25),
('Mesa 11 - Sección 2', '11', 25);
```

## Consultas Útiles

### 📈 Estadísticas por Mesa
```sql
SELECT 
    mv.nombre_mesa,
    mv.grado_asignado,
    mv.grupo_asignado,
    COUNT(em.id_estudiante) as total_estudiantes,
    SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) as votos_emitidos,
    ROUND(
        (SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) * 100.0 / COUNT(em.id_estudiante)), 2
    ) as porcentaje_participacion
FROM mesas_virtuales mv
LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
GROUP BY mv.id_mesa
ORDER BY mv.grado_asignado, mv.grupo_asignado;
```

### 🔍 Buscar Mesa de un Estudiante
```sql
SELECT 
    e.nombre,
    e.grado,
    e.grupo,
    mv.nombre_mesa,
    em.estado_asignacion,
    em.fecha_asignacion
FROM estudiantes e
JOIN estudiantes_mesas em ON e.id_estudiante = em.id_estudiante
JOIN mesas_virtuales mv ON em.id_mesa = mv.id_mesa
WHERE e.id_estudiante = '1234567890';
```

### 📊 Resumen por Grado
```sql
SELECT 
    mv.grado_asignado,
    COUNT(DISTINCT mv.id_mesa) as total_mesas,
    COUNT(em.id_estudiante) as total_estudiantes,
    SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) as total_votos,
    ROUND(AVG(
        CASE WHEN COUNT(em.id_estudiante) > 0 THEN
            (SUM(CASE WHEN em.estado_asignacion = 'votado' THEN 1 ELSE 0 END) * 100.0 / COUNT(em.id_estudiante))
        ELSE 0 END
    ), 2) as participacion_promedio
FROM mesas_virtuales mv
LEFT JOIN estudiantes_mesas em ON mv.id_mesa = em.id_mesa
WHERE mv.estado_mesa = 'activa'
GROUP BY mv.grado_asignado
ORDER BY mv.grado_asignado;
```

## Procedimientos Automáticos

### 🤖 Asignación Automática de Estudiantes
```sql
CALL AsignarEstudiantesAMesas();
```
- Asigna automáticamente estudiantes a mesas según su grado y grupo
- Respeta la capacidad máxima de cada mesa
- Distribuye equitativamente la carga

### 📊 Actualización de Estadísticas
```sql
CALL ActualizarEstadisticasMesas();
```
- Recalcula todas las estadísticas de participación
- Actualiza porcentajes y totales
- Útil para reportes en tiempo real

## Ventajas del Diseño

### ✅ **Escalabilidad**
- Fácil agregar nuevas mesas
- Modificar capacidades según necesidades
- Adaptable a diferentes estructuras educativas

### ✅ **Flexibilidad**
- Mesas por grado, grupo, o mixtas
- Estados controlables (activa/inactiva/cerrada)
- Supervisores opcionales

### ✅ **Control**
- Capacidad máxima por mesa
- Estados de asignación detallados
- Auditoría con fechas de creación/modificación

### ✅ **Reportes**
- Estadísticas automáticas
- Consultas optimizadas
- Análisis por mesa, grado o general

### ✅ **Integridad**
- Claves foráneas para consistencia
- Índices para rendimiento
- Restricciones para evitar duplicados

## Implementación Recomendada

### 🚀 **Fase 1: Estructura Básica**
1. Crear tablas principales
2. Insertar mesas según estructura institucional
3. Configurar índices y restricciones

### 🚀 **Fase 2: Asignación**
1. Implementar procedimiento de asignación automática
2. Crear interfaz para asignación manual
3. Validar distribución equitativa

### 🚀 **Fase 3: Monitoreo**
1. Implementar actualización de estadísticas
2. Crear dashboards de participación
3. Generar reportes automáticos

### 🚀 **Fase 4: Optimización**
1. Ajustar capacidades según uso real
2. Optimizar consultas frecuentes
3. Implementar alertas de capacidad

## Consideraciones Técnicas

### 🔧 **Rendimiento**
- Índices en campos de búsqueda frecuente
- Procedimientos almacenados para operaciones complejas
- Estadísticas precalculadas para reportes rápidos

### 🔧 **Seguridad**
- Validación de capacidades máximas
- Control de estados para prevenir modificaciones no autorizadas
- Auditoría de cambios con timestamps

### 🔧 **Mantenimiento**
- Procedimientos para limpieza de datos antiguos
- Respaldos automáticos de configuraciones
- Logs de operaciones críticas

Este diseño proporciona una base sólida y escalable para implementar un sistema de mesas virtuales que facilite la organización y administración del proceso de votación estudiantil.