# 🗳️ Propuesta: Sistema de Control Temporal de Votaciones

## 📋 Descripción General

Sistema para que los administradores puedan configurar fechas y horas específicas de inicio y cierre de elecciones, controlando automáticamente el acceso de votantes y manteniendo las mesas de votación cerradas hasta que se active la configuración programada.

## 🎯 Objetivos

- **Control Total**: Los administradores definen exactamente cuándo están disponibles las votaciones
- **Automatización**: Apertura y cierre automático sin intervención manual
- **Seguridad**: Prevenir accesos no autorizados fuera del horario electoral
- **Transparencia**: Logs completos de todos los eventos y accesos
- **Flexibilidad**: Permitir programar múltiples elecciones con diferentes horarios

## 🗄️ Estructura de Base de Datos

### Tabla: `configuracion_elecciones`
```sql
CREATE TABLE configuracion_elecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_eleccion VARCHAR(200) NOT NULL,
    descripcion TEXT,
    fecha_inicio DATETIME NOT NULL,
    fecha_cierre DATETIME NOT NULL,
    estado ENUM('programada', 'activa', 'cerrada', 'cancelada') DEFAULT 'programada',
    tipos_votacion JSON, -- ['estudiantes', 'docentes', 'administrativos']
    configuracion_adicional JSON, -- Configuraciones específicas
    creado_por INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (creado_por) REFERENCES administradores(id),
    INDEX idx_estado (estado),
    INDEX idx_fechas (fecha_inicio, fecha_cierre),
    INDEX idx_creado_por (creado_por)
);
```

### Tabla: `logs_acceso_elecciones`
```sql
CREATE TABLE logs_acceso_elecciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_eleccion INT,
    tipo_usuario ENUM('estudiante', 'docente', 'administrativo', 'administrador'),
    id_usuario VARCHAR(50),
    cedula_usuario VARCHAR(20),
    nombre_usuario VARCHAR(200),
    accion ENUM('intento_login', 'login_exitoso', 'login_bloqueado', 'voto_registrado'),
    motivo VARCHAR(200),
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_evento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_eleccion) REFERENCES configuracion_elecciones(id),
    INDEX idx_eleccion (id_eleccion),
    INDEX idx_tipo_usuario (tipo_usuario),
    INDEX idx_fecha_evento (fecha_evento),
    INDEX idx_accion (accion)
);
```

### Tabla: `configuracion_sistema`
```sql
CREATE TABLE configuracion_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion VARCHAR(500),
    tipo ENUM('string', 'integer', 'boolean', 'datetime', 'json') DEFAULT 'string',
    categoria VARCHAR(100),
    modificado_por INT,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (modificado_por) REFERENCES administradores(id),
    INDEX idx_clave (clave),
    INDEX idx_categoria (categoria)
);
```

## 🔧 Componentes a Implementar

### 1. **Modelo: EleccionConfigModel.php**
```php
<?php
namespace models;

class EleccionConfigModel {
    // Gestión de configuraciones
    public function getConfiguracionActiva()
    public function crearConfiguracion($datos)
    public function actualizarConfiguracion($id, $datos)
    public function eliminarConfiguracion($id)
    
    // Control de estado
    public function verificarEleccionesActivas()
    public function activarEleccionesProgramadas()
    public function cerrarEleccionesVencidas()
    public function cambiarEstadoEleccion($id, $estado)
    
    // Consultas específicas
    public function getProximaEleccion()
    public function getEleccionesHistoricas()
    public function getEleccionesPorEstado($estado)
    
    // Validaciones
    public function validarHorarios($fechaInicio, $fechaCierre)
    public function verificarConflictosHorarios($fechaInicio, $fechaCierre)
}
```

### 2. **Controlador: EleccionConfigController.php**
```php
<?php
namespace controllers;

class EleccionConfigController {
    // Vistas
    public function panelConfiguracion()
    public function formularioNuevaEleccion()
    public function detalleEleccion($id)
    
    // Acciones CRUD
    public function crearEleccion()
    public function editarEleccion($id)
    public function eliminarEleccion($id)
    
    // Control de estado
    public function activarEleccion($id)
    public function cerrarEleccion($id)
    public function cancelarEleccion($id)
    
    // APIs
    public function obtenerEstadoElecciones()
    public function obtenerConfiguracionActual()
    public function verificarDisponibilidadVotacion()
}
```

### 3. **Middleware: EleccionMiddleware.php**
```php
<?php
namespace utils;

class EleccionMiddleware {
    // Validación de acceso
    public static function verificarAccesoVotante($tipoUsuario)
    public static function verificarEleccionesActivas()
    public static function verificarHorarioVotacion()
    
    // Gestión de logs
    public static function registrarIntentoAcceso($datos)
    public static function registrarAccesoExitoso($datos)
    public static function registrarAccesoBloqueado($datos)
    
    // Mensajes informativos
    public static function obtenerMensajeEstado()
    public static function obtenerTiempoRestante()
    public static function obtenerInformacionEleccion()
    
    // Validaciones específicas
    public static function puedeVotar($tipoUsuario, $idUsuario)
    public static function yaVoto($tipoUsuario, $idUsuario)
}
```

### 4. **Utilidad: ConfiguracionSistema.php**
```php
<?php
namespace utils;

class ConfiguracionSistema {
    // Gestión de configuraciones
    public static function obtener($clave, $valorPorDefecto = null)
    public static function establecer($clave, $valor, $descripcion = '')
    public static function eliminar($clave)
    
    // Configuraciones específicas
    public static function getHorarioVotacion()
    public static function setHorarioVotacion($inicio, $cierre)
    public static function getConfiguracionElecciones()
    
    // Validaciones
    public static function validarConfiguracion($clave, $valor)
    public static function obtenerConfiguracionesPorCategoria($categoria)
}
```

## 🎨 Interfaz de Usuario Propuesta

### 1. **Panel de Configuración de Elecciones** 
**Archivo**: `views/admin/configuracion_elecciones.php`

#### Sección: Estado Actual
- **Indicador visual** del estado actual (Programada/Activa/Cerrada)
- **Contador en tiempo real** para inicio/cierre
- **Estadísticas de participación** en vivo
- **Botones de acción rápida** (Activar/Cerrar/Cancelar)

#### Sección: Nueva Configuración
- **Formulario de programación**:
  - Nombre de la elección
  - Descripción
  - Fecha y hora de inicio (date-time picker)
  - Fecha y hora de cierre (date-time picker)
  - Tipos de votación habilitados (checkboxes)
  - Configuraciones adicionales

#### Sección: Historial y Gestión
- **Lista de elecciones programadas**
- **Historial de elecciones anteriores**
- **Logs de acceso y eventos**
- **Estadísticas y reportes**

### 2. **Pantallas de Estado para Votantes**

#### Para Estudiantes (`views/estudiantes/estado_elecciones.php`)
```html
<!-- Elecciones no iniciadas -->
<div class="alert alert-info">
    <h4>🕐 Elecciones Programadas</h4>
    <p>Las elecciones comenzarán el <strong>15 de Marzo de 2025</strong> a las <strong>08:00 AM</strong></p>
    <div id="contador-regresivo">Tiempo restante: 2 días, 5 horas, 30 minutos</div>
</div>

<!-- Elecciones activas -->
<div class="alert alert-success">
    <h4>✅ Elecciones Activas</h4>
    <p>Puedes ejercer tu voto hasta las <strong>17:00 PM</strong></p>
    <a href="/Login/estudiantes/votar" class="btn btn-primary">Votar Ahora</a>
</div>

<!-- Elecciones cerradas -->
<div class="alert alert-warning">
    <h4>🔒 Elecciones Finalizadas</h4>
    <p>Las elecciones han finalizado. Gracias por participar.</p>
    <a href="/Login/resultados" class="btn btn-info">Ver Resultados</a>
</div>
```

### 3. **Widget de Estado en Dashboard**
```html
<div class="card border-primary">
    <div class="card-header bg-primary text-white">
        <h5><i class="fas fa-vote-yea"></i> Estado de Elecciones</h5>
    </div>
    <div class="card-body">
        <div id="estado-elecciones">
            <!-- Contenido dinámico según estado -->
        </div>
    </div>
</div>
```

## ⚙️ Lógica de Control Propuesta

### 1. **Validación en Cada Login**
```php
// En AuthController, DocenteController, etc.
public function autenticar() {
    // ... validación de credenciales ...
    
    if ($tipoUsuario !== 'administrador') {
        $estadoElecciones = EleccionMiddleware::verificarAccesoVotante($tipoUsuario);
        
        if (!$estadoElecciones['puede_acceder']) {
            EleccionMiddleware::registrarAccesoBloqueado([
                'tipo_usuario' => $tipoUsuario,
                'id_usuario' => $idUsuario,
                'motivo' => $estadoElecciones['motivo']
            ]);
            
            $_SESSION['mensaje'] = $estadoElecciones['mensaje'];
            $_SESSION['tipo'] = 'warning';
            header('Location: /Login/estado-elecciones');
            exit;
        }
    }
    
    // ... continuar con login normal ...
}
```

### 2. **Cron Job Automático**
```php
// cron/actualizar_estado_elecciones.php
<?php
require_once '../models/EleccionConfigModel.php';

$eleccionModel = new EleccionConfigModel();

// Activar elecciones programadas que llegaron a su hora
$eleccionModel->activarEleccionesProgramadas();

// Cerrar elecciones que llegaron a su hora de cierre
$eleccionModel->cerrarEleccionesVencidas();

// Log del proceso
error_log("Cron job ejecutado: " . date('Y-m-d H:i:s'));
?>
```

### 3. **API de Estado en Tiempo Real**
```php
// api/estado_elecciones.php
{
    "estado": "activa",
    "eleccion_actual": {
        "id": 1,
        "nombre": "Elecciones Estudiantiles 2025",
        "fecha_inicio": "2025-03-15 08:00:00",
        "fecha_cierre": "2025-03-15 17:00:00"
    },
    "tiempo_restante": {
        "para_inicio": 0,
        "para_cierre": 3600,
        "formato_humano": "1 hora restante"
    },
    "permisos": {
        "estudiantes": true,
        "docentes": true,
        "administrativos": true
    },
    "mensaje": "Las elecciones están activas. Puedes ejercer tu voto.",
    "permite_votacion": true
}
```

## 🛡️ Características de Seguridad

### 1. **Validación Múltiple**
- **Frontend**: JavaScript para validación inmediata
- **Backend**: PHP para validación servidor
- **Base de datos**: Constraints y triggers para integridad

### 2. **Sistema de Logs Completo**
```php
// Tipos de eventos registrados:
- intento_login: Cualquier intento de acceso
- login_exitoso: Acceso autorizado
- login_bloqueado: Acceso denegado por horario
- voto_registrado: Voto exitosamente emitido
- configuracion_cambiada: Cambios en configuración
- eleccion_activada: Elección iniciada
- eleccion_cerrada: Elección finalizada
```

### 3. **Protección contra Manipulación**
- **Roles estrictos**: Solo administradores pueden configurar
- **Validación de fechas**: Inicio debe ser anterior al cierre
- **Confirmación doble**: Para acciones críticas (activar/cerrar)
- **Backup automático**: De configuraciones antes de cambios

## 📱 Experiencia de Usuario

### 1. **Para Administradores**
- **Dashboard con estado en tiempo real**
- **Alertas automáticas** sobre eventos importantes
- **Botones de acción rápida** para control manual
- **Reportes y estadísticas** detallados

### 2. **Para Votantes**
- **Información clara** sobre disponibilidad
- **Contador regresivo visual** para inicio/cierre
- **Redirección automática** cuando se abren elecciones
- **Mensajes informativos** sobre el estado actual

## 🔄 Flujo de Trabajo Propuesto

### 1. **Configuración Inicial**
```
Administrador → Panel Configuración → Nueva Elección → 
Definir fechas/horas → Guardar → Estado: "Programada"
```

### 2. **Activación Automática**
```
Cron Job (cada minuto) → Verificar hora actual → 
Si hora >= fecha_inicio → Cambiar estado a "Activa" → 
Permitir acceso a votantes
```

### 3. **Durante la Votación**
```
Votante intenta login → Middleware verifica estado → 
Si elecciones activas → Permitir acceso → 
Registrar en logs → Continuar a votación
```

### 4. **Cierre Automático**
```
Cron Job → Verificar hora actual → 
Si hora >= fecha_cierre → Cambiar estado a "Cerrada" → 
Bloquear nuevos accesos → Generar reportes finales
```

## 📊 Funcionalidades Específicas

### 1. **Panel de Control en Tiempo Real**
- **Widget de estado** en dashboard principal
- **Métricas en vivo**: participación, votos por minuto
- **Alertas automáticas**: problemas, hitos importantes
- **Control manual**: botones de emergencia

### 2. **Sistema de Notificaciones**
- **Email automático** a administradores sobre eventos
- **Alertas en dashboard** para cambios de estado
- **Notificaciones push** (opcional) para móviles

### 3. **Reportes y Auditoría**
- **Reporte de participación** por horarios
- **Log de accesos** con filtros avanzados
- **Estadísticas de uso** del sistema
- **Exportación** de datos para análisis

## 🚀 Implementación por Fases

### **Fase 1: Base del Sistema**
- Crear tablas de base de datos
- Implementar modelos básicos
- Crear middleware de validación
- Panel básico de configuración

### **Fase 2: Interfaz de Usuario**
- Panel de configuración completo
- Pantallas de estado para votantes
- Widget de dashboard
- Sistema de mensajes

### **Fase 3: Automatización**
- Cron job para control automático
- API de estado en tiempo real
- Sistema de logs completo
- Validaciones avanzadas

### **Fase 4: Características Avanzadas**
- Sistema de notificaciones
- Reportes y estadísticas
- Exportación de datos
- Optimizaciones de rendimiento

## 💡 Beneficios Esperados

### **Para Administradores**
✅ **Control total** sobre horarios de votación
✅ **Automatización completa** sin intervención manual
✅ **Visibilidad completa** de eventos y accesos
✅ **Flexibilidad** para múltiples elecciones
✅ **Seguridad mejorada** contra accesos no autorizados

### **Para Votantes**
✅ **Información clara** sobre disponibilidad
✅ **Experiencia consistente** sin sorpresas
✅ **Acceso garantizado** durante horarios válidos
✅ **Transparencia** sobre el proceso electoral

### **Para el Sistema**
✅ **Integridad electoral** mejorada
✅ **Auditoría completa** de eventos
✅ **Escalabilidad** para futuras elecciones
✅ **Mantenimiento simplificado**

## 🔧 Configuraciones Adicionales Sugeridas

### 1. **Configuraciones Flexibles**
- **Horarios diferentes** por tipo de votante
- **Extensiones de tiempo** de emergencia
- **Pausas programadas** durante el día
- **Zonas horarias** múltiples (si aplica)

### 2. **Reglas de Negocio**
- **Tiempo mínimo** entre configuración y inicio
- **Duración mínima/máxima** de elecciones
- **Restricciones de días** (no fines de semana, etc.)
- **Validaciones de capacidad** del sistema

### 3. **Contingencias**
- **Modo de emergencia** para extensiones
- **Backup automático** de configuraciones
- **Rollback** de cambios problemáticos
- **Monitoreo de salud** del sistema

Esta propuesta proporciona un sistema robusto, seguro y automatizado para el control temporal de las votaciones, asegurando que las elecciones solo estén disponibles en los horarios específicamente configurados por los administradores.