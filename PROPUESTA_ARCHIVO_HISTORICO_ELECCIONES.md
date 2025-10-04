# PROPUESTA: Sistema de Archivo Histórico de Elecciones

## PROBLEMA IDENTIFICADO

Actualmente, cuando se inicia una nueva elección, existe el riesgo de perder los datos de elecciones anteriores, incluyendo:
- Votos de estudiantes, docentes y administrativos
- Información de candidatos
- Estadísticas y resultados
- Configuraciones de elecciones pasadas

## OBJETIVO

Implementar un sistema de archivo histórico que permita:
1. **Preservar** todos los datos de elecciones anteriores
2. **Mantener** acceso a resultados históricos
3. **Facilitar** consultas y reportes comparativos
4. **Garantizar** integridad de datos a largo plazo

## ANÁLISIS DE LA ESTRUCTURA ACTUAL

### Tablas Principales Identificadas:
- `configuracion_elecciones` - Configuración de cada elección
- `candidatos` - Información de candidatos
- `votos` - Votos de estudiantes (tabla principal)
- `votos_docentes` - Votos de docentes
- `votos_administrativos` - Votos de administrativos
- `estudiantes`, `docentes`, `administrativos` - Datos de votantes

### Estados de Elección Actuales:
- `programada` - Elección programada para el futuro
- `activa` - Elección en curso
- `cerrada` - Elección finalizada
- `cancelada` - Elección cancelada

## PROPUESTA DE SOLUCIÓN

### 1. ESTRUCTURA DE ARCHIVADO POR ELECCIÓN

#### Opción A: Tablas Históricas Separadas (RECOMENDADA)
Crear tablas específicas para cada elección cerrada:

```sql
-- Ejemplo para elección ID 1
CREATE TABLE eleccion_1_candidatos LIKE candidatos;
CREATE TABLE eleccion_1_votos LIKE votos;
CREATE TABLE eleccion_1_votos_docentes LIKE votos_docentes;
CREATE TABLE eleccion_1_votos_administrativos LIKE votos_administrativos;
```

**Ventajas:**
- Separación clara de datos por elección
- Consultas más rápidas
- Fácil respaldo individual
- No afecta rendimiento de elecciones activas

#### Opción B: Campo de Identificación de Elección
Agregar `id_eleccion` a todas las tablas de votos y candidatos.

**Ventajas:**
- Estructura más simple
- Consultas unificadas

**Desventajas:**
- Tablas más grandes
- Posible impacto en rendimiento

### 2. PROCESO DE ARCHIVADO AUTOMÁTICO

#### Trigger de Archivado
Cuando una elección cambia a estado `cerrada`:

```sql
-- Procedimiento almacenado para archivar elección
DELIMITER //
CREATE PROCEDURE ArchivarEleccion(IN eleccion_id INT)
BEGIN
    DECLARE eleccion_nombre VARCHAR(200);
    DECLARE tabla_candidatos VARCHAR(100);
    DECLARE tabla_votos VARCHAR(100);
    DECLARE tabla_votos_docentes VARCHAR(100);
    DECLARE tabla_votos_admin VARCHAR(100);
    
    -- Obtener nombre de la elección
    SELECT nombre_eleccion INTO eleccion_nombre 
    FROM configuracion_elecciones 
    WHERE id = eleccion_id;
    
    -- Definir nombres de tablas históricas
    SET tabla_candidatos = CONCAT('eleccion_', eleccion_id, '_candidatos');
    SET tabla_votos = CONCAT('eleccion_', eleccion_id, '_votos');
    SET tabla_votos_docentes = CONCAT('eleccion_', eleccion_id, '_votos_docentes');
    SET tabla_votos_admin = CONCAT('eleccion_', eleccion_id, '_votos_administrativos');
    
    -- Crear tablas históricas
    SET @sql = CONCAT('CREATE TABLE ', tabla_candidatos, ' LIKE candidatos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('CREATE TABLE ', tabla_votos, ' LIKE votos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('CREATE TABLE ', tabla_votos_docentes, ' LIKE votos_docentes');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('CREATE TABLE ', tabla_votos_admin, ' LIKE votos_administrativos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    -- Copiar datos
    SET @sql = CONCAT('INSERT INTO ', tabla_candidatos, ' SELECT * FROM candidatos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('INSERT INTO ', tabla_votos, ' SELECT * FROM votos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('INSERT INTO ', tabla_votos_docentes, ' SELECT * FROM votos_docentes');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    SET @sql = CONCAT('INSERT INTO ', tabla_votos_admin, ' SELECT * FROM votos_administrativos');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    
    -- Actualizar estado de archivado
    UPDATE configuracion_elecciones 
    SET configuracion_adicional = JSON_SET(
        IFNULL(configuracion_adicional, '{}'),
        '$.archivada', TRUE,
        '$.fecha_archivado', NOW(),
        '$.tablas_historicas', JSON_OBJECT(
            'candidatos', tabla_candidatos,
            'votos', tabla_votos,
            'votos_docentes', tabla_votos_docentes,
            'votos_administrativos', tabla_votos_admin
        )
    )
    WHERE id = eleccion_id;
    
END //
DELIMITER ;
```

### 3. LIMPIEZA DE DATOS ACTUALES

#### Proceso para Nueva Elección
```sql
-- Procedimiento para limpiar datos y preparar nueva elección
DELIMITER //
CREATE PROCEDURE PrepararNuevaEleccion()
BEGIN
    -- Verificar que no hay elecciones activas
    IF (SELECT COUNT(*) FROM configuracion_elecciones WHERE estado = 'activa') > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede preparar nueva elección: hay elecciones activas';
    END IF;
    
    -- Limpiar tablas de votos
    DELETE FROM votos;
    DELETE FROM votos_docentes;
    DELETE FROM votos_administrativos;
    
    -- Limpiar candidatos (opcional, según necesidad)
    DELETE FROM candidatos;
    
    -- Resetear auto_increment
    ALTER TABLE votos AUTO_INCREMENT = 1;
    ALTER TABLE votos_docentes AUTO_INCREMENT = 1;
    ALTER TABLE votos_administrativos AUTO_INCREMENT = 1;
    ALTER TABLE candidatos AUTO_INCREMENT = 1;
    
END //
DELIMITER ;
```

### 4. MODELO DE DATOS PARA CONSULTAS HISTÓRICAS

#### Clase PHP para Manejo Histórico
```php
<?php
namespace models;

class EleccionHistoricaModel {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Obtiene lista de elecciones archivadas
     */
    public function getEleccionesArchivadas() {
        $sql = "SELECT * FROM configuracion_elecciones 
                WHERE estado = 'cerrada' 
                AND JSON_EXTRACT(configuracion_adicional, '$.archivada') = true
                ORDER BY fecha_cierre DESC";
        
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Obtiene resultados de una elección específica
     */
    public function getResultadosEleccion($eleccion_id) {
        // Obtener información de tablas históricas
        $sql = "SELECT configuracion_adicional FROM configuracion_elecciones WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $eleccion_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) return null;
        
        $config = json_decode($result['configuracion_adicional'], true);
        $tablas = $config['tablas_historicas'] ?? [];
        
        // Consultar datos históricos
        $resultados = [];
        
        // Candidatos
        if (isset($tablas['candidatos'])) {
            $sql = "SELECT * FROM " . $tablas['candidatos'];
            $candidatos = $this->db->query($sql);
            $resultados['candidatos'] = $candidatos ? $candidatos->fetch_all(MYSQLI_ASSOC) : [];
        }
        
        // Votos estudiantes
        if (isset($tablas['votos'])) {
            $sql = "SELECT COUNT(*) as total FROM " . $tablas['votos'];
            $votos = $this->db->query($sql);
            $resultados['total_votos_estudiantes'] = $votos ? $votos->fetch_assoc()['total'] : 0;
        }
        
        return $resultados;
    }
    
    /**
     * Compara resultados entre elecciones
     */
    public function compararElecciones($eleccion_id1, $eleccion_id2) {
        $resultados1 = $this->getResultadosEleccion($eleccion_id1);
        $resultados2 = $this->getResultadosEleccion($eleccion_id2);
        
        return [
            'eleccion_1' => $resultados1,
            'eleccion_2' => $resultados2,
            'comparacion' => $this->calcularComparacion($resultados1, $resultados2)
        ];
    }
}
```

### 5. INTERFAZ DE USUARIO

#### Panel de Administración - Sección Histórico
- **Lista de Elecciones Pasadas**: Tabla con todas las elecciones archivadas
- **Ver Resultados**: Botón para consultar resultados de elección específica
- **Comparar Elecciones**: Herramienta para comparar dos elecciones
- **Exportar Datos**: Funcionalidad para exportar datos históricos
- **Estadísticas Generales**: Dashboard con métricas históricas

#### Funcionalidades Específicas:
1. **Búsqueda por Fecha**: Filtrar elecciones por rango de fechas
2. **Búsqueda por Nombre**: Buscar elecciones por nombre
3. **Reportes PDF**: Generar reportes de elecciones pasadas
4. **Gráficos Comparativos**: Visualización de tendencias

### 6. PROCESO DE IMPLEMENTACIÓN

#### Fase 1: Preparación (1-2 días)
- [ ] Crear procedimientos almacenados de archivado
- [ ] Implementar modelo PHP para manejo histórico
- [ ] Crear tablas de prueba

#### Fase 2: Integración (2-3 días)
- [ ] Modificar controlador de elecciones
- [ ] Integrar proceso de archivado automático
- [ ] Crear interfaz básica de consulta

#### Fase 3: Interfaz Completa (3-4 días)
- [ ] Desarrollar panel de administración histórico
- [ ] Implementar funcionalidades de comparación
- [ ] Crear reportes y exportación

#### Fase 4: Pruebas y Optimización (1-2 días)
- [ ] Pruebas de archivado
- [ ] Pruebas de consulta histórica
- [ ] Optimización de rendimiento

### 7. CONSIDERACIONES TÉCNICAS

#### Almacenamiento
- **Espacio en Disco**: Cada elección archivada ocupará espacio adicional
- **Respaldos**: Incluir tablas históricas en rutinas de respaldo
- **Limpieza**: Política de retención de datos (ej: mantener últimas 10 elecciones)

#### Rendimiento
- **Índices**: Crear índices apropiados en tablas históricas
- **Consultas**: Optimizar consultas para grandes volúmenes de datos
- **Cache**: Implementar cache para consultas frecuentes

#### Seguridad
- **Permisos**: Restringir acceso a datos históricos
- **Auditoría**: Registrar accesos a datos históricos
- **Integridad**: Verificaciones de integridad de datos archivados

### 8. BENEFICIOS ESPERADOS

1. **Preservación de Datos**: Garantía de no pérdida de información histórica
2. **Análisis Comparativo**: Capacidad de analizar tendencias entre elecciones
3. **Cumplimiento Legal**: Mantenimiento de registros para auditorías
4. **Mejora Continua**: Datos para optimizar procesos electorales futuros
5. **Transparencia**: Acceso a resultados históricos para la comunidad

### 9. RIESGOS Y MITIGACIONES

#### Riesgos Identificados:
- **Fallo en Archivado**: Pérdida de datos durante el proceso
- **Corrupción de Datos**: Datos históricos dañados
- **Espacio Insuficiente**: Falta de espacio para almacenamiento

#### Mitigaciones:
- **Transacciones**: Usar transacciones para garantizar atomicidad
- **Verificaciones**: Validar integridad después del archivado
- **Monitoreo**: Alertas de espacio en disco
- **Respaldos**: Respaldos automáticos antes del archivado

### 10. CRONOGRAMA ESTIMADO

**Tiempo Total Estimado: 8-11 días hábiles**

- **Semana 1**: Fases 1 y 2 (Preparación e Integración)
- **Semana 2**: Fases 3 y 4 (Interfaz y Pruebas)

### 11. RECURSOS NECESARIOS

- **Desarrollador Backend**: Para procedimientos almacenados y modelos PHP
- **Desarrollador Frontend**: Para interfaces de usuario
- **DBA/Administrador**: Para optimización y configuración de base de datos
- **Tester**: Para pruebas de funcionalidad

---

## CONCLUSIÓN

Esta propuesta garantiza la preservación completa de datos históricos de elecciones, permitiendo iniciar nuevas elecciones sin pérdida de información previa. El sistema propuesto es escalable, eficiente y proporciona herramientas valiosas para análisis y comparación de resultados electorales a lo largo del tiempo.

La implementación por fases permite un desarrollo controlado y la posibilidad de ajustes durante el proceso, minimizando riesgos y garantizando la continuidad del servicio.