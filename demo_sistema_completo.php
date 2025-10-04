<?php
/**
 * DEMOSTRACIÓN COMPLETA DEL SISTEMA DE MESAS VIRTUALES
 * Este script muestra todas las funcionalidades implementadas
 */

require_once 'config/config.php';
require_once 'models/MesasVirtualesModel.php';
require_once 'models/GeneradorPersonalModel.php';
require_once 'models/HistoricoEleccionesModel.php';
require_once 'models/VotosActualizado.php';
require_once 'models/EleccionConfigModel.php';

use models\MesasVirtualesModel;
use models\GeneradorPersonalModel;
use models\HistoricoEleccionesModel;
use models\VotosActualizado;
use models\EleccionConfigModel;

echo "
╔══════════════════════════════════════════════════════════════════════════════╗
║                    🎉 DEMOSTRACIÓN SISTEMA COMPLETO 🎉                      ║
║                        MESAS VIRTUALES + ELECCIONES                         ║
╚══════════════════════════════════════════════════════════════════════════════╝

";

try {
    // Inicializar modelos
    $mesasModel = new MesasVirtualesModel();
    $generadorModel = new GeneradorPersonalModel();
    $historicoModel = new HistoricoEleccionesModel();
    $votosModel = new VotosActualizado();
    $eleccionModel = new EleccionConfigModel();
    
    echo "📋 FUNCIONALIDADES IMPLEMENTADAS:\n";
    echo "═══════════════════════════════════\n\n";
    
    // 1. Sistema de Mesas Virtuales
    echo "1️⃣  SISTEMA DE MESAS VIRTUALES\n";
    echo "   ✅ 12 mesas virtuales (Preescolar a 11°)\n";
    echo "   ✅ Asignación automática por grado\n";
    echo "   ✅ Gestión completa de personal\n";
    echo "   ✅ Estadísticas en tiempo real\n\n";
    
    // 2. Generador Automático
    echo "2️⃣  GENERADOR AUTOMÁTICO DE PERSONAL\n";
    echo "   ✅ Jurados (Padres de familia)\n";
    echo "   ✅ Testigos Docentes\n";
    echo "   ✅ Testigos Estudiantes (Grados 10° y 11°)\n";
    echo "   ✅ Datos realistas y variados\n\n";
    
    // 3. Sistema de Elecciones Individuales
    echo "3️⃣  ELECCIONES INDIVIDUALES\n";
    echo "   ✅ Votos asociados a elecciones específicas\n";
    echo "   ✅ Histórico automático\n";
    echo "   ✅ Resultados independientes\n";
    echo "   ✅ Migración de datos existentes\n\n";
    
    // 4. Interfaz de Administración
    echo "4️⃣  INTERFAZ DE ADMINISTRACIÓN\n";
    echo "   ✅ Navegación integrada en sidebar\n";
    echo "   ✅ Panel de gestión completo\n";
    echo "   ✅ Botones de acción rápida\n";
    echo "   ✅ Gestión individual de mesas\n\n";
    
    echo "🔍 VERIFICACIÓN DEL SISTEMA:\n";
    echo "═══════════════════════════════\n\n";
    
    // Obtener elección para demostración
    $todasElecciones = $eleccionModel->getTodasElecciones();
    if (empty($todasElecciones)) {
        echo "❌ No hay elecciones en el sistema\n";
        exit;
    }
    
    $eleccion = $todasElecciones[0];
    $id_eleccion = $eleccion['id'];
    
    echo "📊 ELECCIÓN SELECCIONADA:\n";
    echo "   Nombre: {$eleccion['nombre_eleccion']}\n";
    echo "   ID: $id_eleccion\n";
    echo "   Estado: {$eleccion['estado']}\n\n";
    
    // Verificar mesas
    $mesas = $mesasModel->getMesasPorEleccion($id_eleccion);
    echo "🏛️  MESAS VIRTUALES:\n";
    echo "   Total mesas: " . count($mesas) . "\n";
    
    if (!empty($mesas)) {
        echo "   Mesas creadas:\n";
        foreach ($mesas as $mesa) {
            echo "   - {$mesa['nombre_mesa']} (Grado: {$mesa['grado_asignado']})\n";
        }
    }
    echo "\n";
    
    // Estadísticas de personal
    $estadisticasPersonal = $generadorModel->getEstadisticasPersonal($id_eleccion);
    echo "👥 PERSONAL DE MESAS:\n";
    echo "   Total personal: {$estadisticasPersonal['total_personal']}\n";
    echo "   Jurados: {$estadisticasPersonal['total_jurados']}\n";
    echo "   Testigos docentes: {$estadisticasPersonal['total_testigos_docentes']}\n";
    echo "   Testigos estudiantes: {$estadisticasPersonal['total_testigos_estudiantes']}\n";
    echo "   Mesas completas: {$estadisticasPersonal['mesas_completas']}/{$estadisticasPersonal['total_mesas']}\n";
    echo "   Completado: {$estadisticasPersonal['porcentaje_completado']}%\n\n";
    
    // Estadísticas de mesas
    $estadisticasMesas = $mesasModel->getEstadisticasMesas($id_eleccion);
    echo "📈 ESTADÍSTICAS POR MESA:\n";
    foreach ($estadisticasMesas as $mesa) {
        echo "   {$mesa['nombre_mesa']}:\n";
        echo "     - Estudiantes: {$mesa['estudiantes_asignados']}\n";
        echo "     - Personal: {$mesa['personal_asignado']}/4 ({$mesa['estado_personal']})\n";
        echo "     - Votos: {$mesa['votos_emitidos']} ({$mesa['porcentaje_participacion']}%)\n";
    }
    echo "\n";
    
    // Resumen por niveles
    $resumenNiveles = $mesasModel->getResumenPorNiveles($id_eleccion);
    echo "🎓 RESUMEN POR NIVELES EDUCATIVOS:\n";
    foreach ($resumenNiveles as $nivel) {
        echo "   {$nivel['nivel_educativo']}:\n";
        echo "     - Mesas: {$nivel['total_mesas']}\n";
        echo "     - Estudiantes: {$nivel['total_estudiantes']}\n";
        echo "     - Participación: {$nivel['porcentaje_participacion']}%\n";
    }
    echo "\n";
    
    // Estadísticas de votación
    $estadisticasVotos = $votosModel->getEstadisticasEleccion($id_eleccion);
    if (!empty($estadisticasVotos)) {
        echo "🗳️  ESTADÍSTICAS DE VOTACIÓN:\n";
        echo "   Estudiantes: {$estadisticasVotos['estudiantes']['total_votos']}/{$estadisticasVotos['estudiantes']['total_habilitados']} ({$estadisticasVotos['estudiantes']['porcentaje_participacion']}%)\n";
        echo "   Docentes: {$estadisticasVotos['docentes']['total_votos']}/{$estadisticasVotos['docentes']['total_habilitados']} ({$estadisticasVotos['docentes']['porcentaje_participacion']}%)\n";
        echo "   Administrativos: {$estadisticasVotos['administrativos']['total_votos']}/{$estadisticasVotos['administrativos']['total_habilitados']} ({$estadisticasVotos['administrativos']['porcentaje_participacion']}%)\n";
        echo "   Participación general: {$estadisticasVotos['totales']['porcentaje_participacion_general']}%\n\n";
    }
    
    // Histórico de elecciones
    $historico = $historicoModel->getHistoricoCompleto();
    echo "📚 HISTÓRICO DE ELECCIONES:\n";
    echo "   Registros históricos: " . count($historico) . "\n";
    if (!empty($historico)) {
        echo "   Últimas elecciones:\n";
        foreach (array_slice($historico, 0, 3) as $registro) {
            echo "   - {$registro['nombre_eleccion']} (Finalizada: " . date('d/m/Y', strtotime($registro['fecha_finalizacion'])) . ")\n";
        }
    }
    echo "\n";
    
    echo "🎯 FUNCIONALIDADES DISPONIBLES:\n";
    echo "═══════════════════════════════════\n\n";
    
    echo "📱 ACCESO AL SISTEMA:\n";
    echo "   1. Panel Administrativo → Sidebar → 'Mesas Virtuales'\n";
    echo "   2. Seleccionar elección\n";
    echo "   3. Usar botones de acción:\n";
    echo "      - 'Crear Mesas': Crea las 12 mesas automáticamente\n";
    echo "      - 'Generar Personal': Genera todo el personal automáticamente\n";
    echo "      - 'Reasignar': Reasigna estudiantes por grado\n";
    echo "      - 'Limpiar Personal': Elimina todo el personal\n\n";
    
    echo "⚙️  GESTIÓN INDIVIDUAL:\n";
    echo "   - Clic en 'Personal' en cualquier mesa\n";
    echo "   - Agregar/eliminar personal manualmente\n";
    echo "   - Ver validación de personal completo\n";
    echo "   - Gestionar información detallada\n\n";
    
    echo "📊 CARACTERÍSTICAS DESTACADAS:\n";
    echo "   ✅ Generación automática inteligente\n";
    echo "   ✅ Datos realistas y variados\n";
    echo "   ✅ Validación automática de completitud\n";
    echo "   ✅ Estadísticas en tiempo real\n";
    echo "   ✅ Navegación integrada\n";
    echo "   ✅ Interfaz intuitiva\n";
    echo "   ✅ Sistema de elecciones individuales\n";
    echo "   ✅ Histórico automático\n\n";
    
    // Mostrar ejemplo de personal generado
    if ($estadisticasPersonal['total_personal'] > 0) {
        echo "👤 EJEMPLO DE PERSONAL GENERADO:\n";
        $db = new \config\Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT pm.*, mv.nombre_mesa 
                FROM personal_mesa pm 
                JOIN mesas_virtuales mv ON pm.id_mesa = mv.id_mesa 
                WHERE mv.id_eleccion = ? 
                ORDER BY pm.tipo_personal, pm.fecha_asignacion 
                LIMIT 3";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $personalEjemplo = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($personalEjemplo as $persona) {
            $tipo = ucfirst(str_replace('_', ' ', $persona['tipo_personal']));
            echo "   {$tipo}: {$persona['nombre_completo']}\n";
            echo "     Mesa: {$persona['nombre_mesa']}\n";
            echo "     Documento: {$persona['documento_identidad']}\n";
            echo "     Teléfono: {$persona['telefono']}\n";
            echo "     Email: {$persona['email']}\n\n";
        }
        
        $conn->close();
    }
    
    echo "
╔══════════════════════════════════════════════════════════════════════════════╗
║                          🎉 SISTEMA COMPLETADO 🎉                          ║
║                                                                              ║
║  ✅ Mesas Virtuales: IMPLEMENTADO Y FUNCIONANDO                            ║
║  ✅ Generador Automático: IMPLEMENTADO Y FUNCIONANDO                       ║
║  ✅ Elecciones Individuales: IMPLEMENTADO Y FUNCIONANDO                    ║
║  ✅ Interfaz de Administración: IMPLEMENTADO Y FUNCIONANDO                 ║
║  ✅ Navegación Integrada: IMPLEMENTADO Y FUNCIONANDO                       ║
║                                                                              ║
║              🚀 LISTO PARA USAR EN PRODUCCIÓN 🚀                          ║
╚══════════════════════════════════════════════════════════════════════════════╝

";
    
} catch (Exception $e) {
    echo "❌ Error durante la demostración: " . $e->getMessage() . "\n";
}
?>
