<?php
/**
 * Script para extender la fecha de cierre de la elección activa existente
 * Esto solucionará el problema de inconsistencia de fechas
 */

require_once 'autoload.php';
require_once 'config/config.php';

use models\EleccionConfigModel;

echo "=== EXTENDER ELECCIÓN ACTIVA ===\n";

$eleccionModel = new EleccionConfigModel();

// Obtener todas las elecciones activas
$eleccionesActivas = $eleccionModel->getEleccionesPorEstado('activa');

if (empty($eleccionesActivas)) {
    echo "❌ No hay elecciones activas para extender\n";
    exit;
}

foreach ($eleccionesActivas as $eleccion) {
    echo "📋 Elección encontrada: {$eleccion['nombre_eleccion']}\n";
    echo "   ID: {$eleccion['id']}\n";
    echo "   Fecha actual de cierre: {$eleccion['fecha_cierre']}\n";
    
    // Extender la fecha de cierre por 6 horas más
    $nuevaFechaCierre = date('Y-m-d H:i:s', strtotime($eleccion['fecha_cierre'] . ' +6 hours'));
    
    // Actualizar la elección
    $datosActualizados = [
        'nombre_eleccion' => $eleccion['nombre_eleccion'],
        'descripcion' => $eleccion['descripcion'],
        'fecha_inicio' => $eleccion['fecha_inicio'],
        'fecha_cierre' => $nuevaFechaCierre,
        'estado' => 'activa',
        'tipos_votacion' => $eleccion['tipos_votacion'],
        'configuracion_adicional' => $eleccion['configuracion_adicional']
    ];
    
    $resultado = $eleccionModel->actualizarConfiguracion($eleccion['id'], $datosActualizados);
    
    if ($resultado) {
        echo "✅ Elección extendida exitosamente\n";
        echo "   Nueva fecha de cierre: $nuevaFechaCierre\n";
    } else {
        echo "❌ Error al extender la elección\n";
    }
}

// Verificar el estado actual después de la actualización
echo "\n=== VERIFICACIÓN FINAL ===\n";
$eleccionActiva = $eleccionModel->getConfiguracionActiva();

if ($eleccionActiva) {
    echo "✅ Elección activa confirmada:\n";
    echo "   Nombre: {$eleccionActiva['nombre_eleccion']}\n";
    echo "   Inicio: {$eleccionActiva['fecha_inicio']}\n";
    echo "   Cierre: {$eleccionActiva['fecha_cierre']}\n";
    echo "   Tipos habilitados: " . implode(', ', $eleccionActiva['tipos_votacion']) . "\n";
    
    // Verificar tiempo restante
    $fechaCierre = new DateTime($eleccionActiva['fecha_cierre']);
    $ahora = new DateTime();
    $diferencia = $ahora->diff($fechaCierre);
    
    if ($fechaCierre > $ahora) {
        echo "   ⏰ Tiempo restante: {$diferencia->h} horas y {$diferencia->i} minutos\n";
    } else {
        echo "   ⚠️  La elección ya debería haber terminado\n";
    }
} else {
    echo "❌ No se encontró elección activa después de la actualización\n";
}

echo "\nScript completado.\n";
?>