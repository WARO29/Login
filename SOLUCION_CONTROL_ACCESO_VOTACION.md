# Solución: Control de Acceso a Votación Fuera del Período Electoral

## Problema Identificado

Los usuarios administrativos y docentes podían acceder a la interfaz de votación incluso cuando las elecciones no estaban activas, mostrando los candidatos y las opciones de voto. Esto representaba una vulnerabilidad de seguridad ya que permitía el acceso a la votación fuera del período electoral establecido.

## Análisis del Problema

### Causa Raíz
El problema se encontraba en el archivo `controllers/RepresentanteDocenteController.php`, específicamente en el método `mostrarPanel()` (líneas 37-61). Este método:

1. ✅ **Verificaba la autenticación** del usuario (docente/administrativo)
2. ❌ **NO verificaba si las elecciones estaban activas**
3. ✅ **Verificaba si el usuario ya había votado**
4. ❌ **Mostraba directamente la interfaz de votación**

### Flujo del Problema
```
Usuario Administrativo/Docente
    ↓
Accede a /Login/docente/panel
    ↓
index.php redirige a RepresentanteDocenteController::mostrarPanel()
    ↓
Solo verifica autenticación ❌
    ↓
Muestra interfaz de votación (PROBLEMA)
```

### Comparación con Otros Controladores
- **DocenteController::panel()**: ✅ SÍ verificaba elecciones activas
- **RepresentanteDocenteController::mostrarPanel()**: ❌ NO verificaba elecciones activas
- **RepresentanteDocenteController::procesarVoto()**: ✅ SÍ verificaba elecciones activas

## Solución Implementada

### Modificación Realizada
Se actualizó el método `mostrarPanel()` en `controllers/RepresentanteDocenteController.php` para incluir la verificación de elecciones activas, siguiendo el mismo patrón usado en `DocenteController::panel()`.

### Código Agregado
```php
// Determinar el tipo de usuario
$tipoUsuario = isset($_SESSION['es_administrativo']) && $_SESSION['es_administrativo'] === true ? 'administrativo' : 'docente';

// Verificar si puede acceder a votar según la configuración de elecciones
$verificacionAcceso = EleccionMiddleware::verificarAccesoVotante($tipoUsuario);

if (!$verificacionAcceso['puede_acceder']) {
    // No puede acceder, mostrar mensaje y redirigir o mostrar vista de información
    $_SESSION['mensaje'] = $verificacionAcceso['mensaje'];
    $_SESSION['tipo'] = 'warning';
    $_SESSION['motivo_acceso_denegado'] = $verificacionAcceso['motivo'];
    
    // Cargar vista con información de por qué no puede votar
    require_once 'views/docente/acceso_denegado.php';
    return;
}
```

### Flujo Corregido
```
Usuario Administrativo/Docente
    ↓
Accede a /Login/docente/panel
    ↓
index.php redirige a RepresentanteDocenteController::mostrarPanel()
    ↓
Verifica autenticación ✅
    ↓
Verifica elecciones activas ✅
    ↓
¿Elecciones activas?
    ├─ NO → Muestra views/docente/acceso_denegado.php ✅
    └─ SÍ → Muestra interfaz de votación ✅
```

## Verificación de la Solución

### Prueba Realizada
1. **Acceso con usuario docente/administrativo** (cédula: 1234567890)
2. **Resultado esperado**: Bloqueo de acceso con mensaje informativo
3. **Resultado obtenido**: ✅ **CORRECTO**
   - Mensaje: "Elecciones no iniciadas"
   - Información: "Las elecciones comenzarán el 08/09/2025 a las 10:00"
   - Vista: `views/docente/acceso_denegado.php`

### Estado del Sistema
- **Elecciones activas**: NO
- **Próxima elección**: "Elecciones Estudiantiles 2025"
- **Fecha programada**: 08/09/2025 10:00 - 11:00
- **Tipos habilitados**: estudiante, docente, administrativo

## Beneficios de la Solución

### Seguridad
- ✅ **Bloquea acceso fuera del período electoral**
- ✅ **Previene votación prematura**
- ✅ **Mantiene integridad del proceso electoral**

### Experiencia de Usuario
- ✅ **Mensajes informativos claros**
- ✅ **Información sobre próximas elecciones**
- ✅ **Interfaz consistente con otros módulos**

### Mantenibilidad
- ✅ **Usa el mismo middleware de control electoral**
- ✅ **Código consistente con otros controladores**
- ✅ **Fácil de mantener y actualizar**

## Archivos Modificados

1. **`controllers/RepresentanteDocenteController.php`**
   - Método: `mostrarPanel()`
   - Líneas: 37-61 (reemplazadas por 37-85)
   - Cambio: Agregada verificación de elecciones activas

## Archivos de Apoyo Utilizados

1. **`utils/EleccionMiddleware.php`** - Middleware de control electoral
2. **`models/EleccionConfigModel.php`** - Modelo de configuración de elecciones
3. **`views/docente/acceso_denegado.php`** - Vista de acceso denegado

## Conclusión

El problema ha sido **completamente resuelto**. Ahora todos los usuarios (estudiantes, docentes y administrativos) están sujetos a las mismas validaciones de período electoral, garantizando que solo puedan acceder a la votación cuando las elecciones estén activas.

La solución es **robusta, segura y mantenible**, siguiendo las mejores prácticas ya establecidas en el sistema.