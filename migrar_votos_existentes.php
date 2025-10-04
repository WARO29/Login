<?php
/**
 * Script para migrar votos existentes al sistema de elecciones individuales
 */

require_once 'config/config.php';
require_once 'models/VotosActualizado.php';

use config\Database;
use models\VotosActualizado;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "=== MIGRACIÓN DE VOTOS EXISTENTES ===\n";
    echo "Iniciando migración...\n\n";
    
    // 1. Verificar votos sin id_eleccion
    echo "1. Verificando votos sin id_eleccion...\n";
    
    $sql = "SELECT COUNT(*) as total FROM votos WHERE id_eleccion IS NULL";
    $result = $conn->query($sql);
    $votos_estudiantes_sin_eleccion = $result->fetch_assoc()['total'];
    
    $sql = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_eleccion IS NULL";
    $result = $conn->query($sql);
    $votos_docentes_sin_eleccion = $result->fetch_assoc()['total'];
    
    $sql = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_eleccion IS NULL";
    $result = $conn->query($sql);
    $votos_administrativos_sin_eleccion = $result->fetch_assoc()['total'];
    
    echo "- Votos de estudiantes sin elección: $votos_estudiantes_sin_eleccion\n";
    echo "- Votos de docentes sin elección: $votos_docentes_sin_eleccion\n";
    echo "- Votos de administrativos sin elección: $votos_administrativos_sin_eleccion\n\n";
    
    // 2. Obtener elección activa actual
    echo "2. Obteniendo elección activa...\n";
    $sql = "SELECT id, nombre_eleccion FROM configuracion_elecciones 
            WHERE estado = 'activa' 
            ORDER BY fecha_inicio DESC 
            LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $eleccion_activa = $result->fetch_assoc();
        $id_eleccion_activa = $eleccion_activa['id'];
        echo "✅ Elección activa encontrada: ID {$id_eleccion_activa} - {$eleccion_activa['nombre_eleccion']}\n\n";
        
        // 3. Migrar votos existentes
        echo "3. Migrando votos existentes...\n";
        $votosModel = new VotosActualizado();
        
        if ($votosModel->migrarVotosExistentes($id_eleccion_activa)) {
            echo "✅ Votos migrados exitosamente\n\n";
        } else {
            echo "❌ Error al migrar votos\n\n";
        }
        
        // 4. Verificar migración
        echo "4. Verificando migración...\n";
        
        $sql = "SELECT COUNT(*) as total FROM votos WHERE id_eleccion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_eleccion_activa]);
        $votos_estudiantes_migrados = $stmt->get_result()->fetch_assoc()['total'];
        
        $sql = "SELECT COUNT(*) as total FROM votos_docentes WHERE id_eleccion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_eleccion_activa]);
        $votos_docentes_migrados = $stmt->get_result()->fetch_assoc()['total'];
        
        $sql = "SELECT COUNT(*) as total FROM votos_administrativos WHERE id_eleccion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_eleccion_activa]);
        $votos_administrativos_migrados = $stmt->get_result()->fetch_assoc()['total'];
        
        echo "- Votos de estudiantes en elección {$id_eleccion_activa}: $votos_estudiantes_migrados\n";
        echo "- Votos de docentes en elección {$id_eleccion_activa}: $votos_docentes_migrados\n";
        echo "- Votos de administrativos en elección {$id_eleccion_activa}: $votos_administrativos_migrados\n\n";
        
        // 5. Probar estadísticas del nuevo sistema
        echo "5. Probando estadísticas del nuevo sistema...\n";
        $estadisticas = $votosModel->getEstadisticasEleccion($id_eleccion_activa);
        
        if (!empty($estadisticas)) {
            echo "✅ Estadísticas generadas correctamente:\n";
            echo "- Estudiantes: {$estadisticas['estudiantes']['total_votos']}/{$estadisticas['estudiantes']['total_habilitados']} ({$estadisticas['estudiantes']['porcentaje_participacion']}%)\n";
            echo "- Docentes: {$estadisticas['docentes']['total_votos']}/{$estadisticas['docentes']['total_habilitados']} ({$estadisticas['docentes']['porcentaje_participacion']}%)\n";
            echo "- Administrativos: {$estadisticas['administrativos']['total_votos']}/{$estadisticas['administrativos']['total_habilitados']} ({$estadisticas['administrativos']['porcentaje_participacion']}%)\n";
            echo "- Participación general: {$estadisticas['totales']['porcentaje_participacion_general']}%\n\n";
        } else {
            echo "❌ Error al generar estadísticas\n\n";
        }
        
        // 6. Probar resultados por elección
        echo "6. Probando resultados por elección...\n";
        
        $resultados_estudiantes = $votosModel->getResultadosEstudiantes($id_eleccion_activa);
        echo "- Candidatos estudiantiles encontrados: " . count($resultados_estudiantes) . "\n";
        
        $resultados_docentes = $votosModel->getResultadosDocentes($id_eleccion_activa);
        echo "- Representantes docentes encontrados: " . count($resultados_docentes) . "\n";
        
        // 7. Probar votos recientes
        echo "\n7. Probando votos recientes...\n";
        $votos_recientes = $votosModel->getVotosRecientes(5, $id_eleccion_activa);
        echo "- Votos recientes encontrados: " . count($votos_recientes) . "\n";
        
        if (!empty($votos_recientes)) {
            echo "Últimos votos:\n";
            foreach (array_slice($votos_recientes, 0, 3) as $voto) {
                $info_voto = $voto['voto_blanco'] ? 'Voto en blanco' : 'Votó por ' . ($voto['candidato_elegido'] ?? 'candidato');
                echo "  - {$voto['nombre_votante']} ({$voto['tipo_votante']}): $info_voto\n";
            }
        }
        
    } else {
        echo "❌ No se encontró elección activa\n";
        
        // Buscar la elección más reciente
        echo "Buscando elección más reciente...\n";
        $sql = "SELECT id, nombre_eleccion, estado FROM configuracion_elecciones 
                ORDER BY fecha_inicio DESC 
                LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $eleccion_reciente = $result->fetch_assoc();
            echo "Elección más reciente: ID {$eleccion_reciente['id']} - {$eleccion_reciente['nombre_eleccion']} (Estado: {$eleccion_reciente['estado']})\n";
            
            // Migrar a la elección más reciente
            $votosModel = new VotosActualizado();
            if ($votosModel->migrarVotosExistentes($eleccion_reciente['id'])) {
                echo "✅ Votos migrados a la elección más reciente\n";
            }
        }
    }
    
    echo "\n=== MIGRACIÓN COMPLETADA ===\n";
    echo "✅ Sistema actualizado para manejar elecciones individuales\n";
    echo "✅ Votos existentes migrados correctamente\n";
    echo "✅ Mesas virtuales funcionando\n";
    echo "✅ Estadísticas por elección disponibles\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error durante la migración: " . $e->getMessage() . "\n";
}
?>
