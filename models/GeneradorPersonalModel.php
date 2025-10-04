<?php
namespace models;

use config\Database;

class GeneradorPersonalModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Generar personal automáticamente para todas las mesas de una elección
     * @param int $id_eleccion ID de la elección
     * @return array Resultado de la generación
     */
    public function generarPersonalCompleto($id_eleccion) {
        $resultado = [
            'success' => false,
            'mensaje' => '',
            'detalles' => [],
            'estadisticas' => [
                'jurados_generados' => 0,
                'testigos_docentes_generados' => 0,
                'testigos_estudiantes_generados' => 0,
                'mesas_completadas' => 0
            ]
        ];

        try {
            // Obtener todas las mesas de la elección
            $sql = "SELECT * FROM mesas_virtuales WHERE id_eleccion = ? ORDER BY grado_asignado";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_eleccion]);
            $mesas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if (empty($mesas)) {
                $resultado['mensaje'] = 'No se encontraron mesas para esta elección';
                return $resultado;
            }

            foreach ($mesas as $mesa) {
                $detallesMesa = $this->generarPersonalParaMesa($mesa['id_mesa'], $mesa['grado_asignado']);
                $resultado['detalles'][] = $detallesMesa;
                
                // Actualizar estadísticas
                $resultado['estadisticas']['jurados_generados'] += $detallesMesa['jurados_agregados'];
                $resultado['estadisticas']['testigos_docentes_generados'] += $detallesMesa['testigos_docentes_agregados'];
                $resultado['estadisticas']['testigos_estudiantes_generados'] += $detallesMesa['testigos_estudiantes_agregados'];
                
                if ($detallesMesa['mesa_completa']) {
                    $resultado['estadisticas']['mesas_completadas']++;
                }
            }

            $resultado['success'] = true;
            $resultado['mensaje'] = "Personal generado para " . count($mesas) . " mesas. " .
                                   "Mesas completadas: " . $resultado['estadisticas']['mesas_completadas'] . "/" . count($mesas);

        } catch (\Exception $e) {
            $resultado['mensaje'] = 'Error al generar personal: ' . $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Generar personal para una mesa específica
     * @param int $id_mesa ID de la mesa
     * @param string $grado Grado de la mesa
     * @return array Detalles de la generación
     */
    private function generarPersonalParaMesa($id_mesa, $grado) {
        $detalles = [
            'id_mesa' => $id_mesa,
            'grado' => $grado,
            'jurados_agregados' => 0,
            'testigos_docentes_agregados' => 0,
            'testigos_estudiantes_agregados' => 0,
            'mesa_completa' => false,
            'errores' => []
        ];

        try {
            // Verificar personal existente
            $personalExistente = $this->obtenerPersonalExistente($id_mesa);
            
            // Generar Jurado (Padre de familia) si no existe
            if (!isset($personalExistente['jurado'])) {
                $jurado = $this->generarJurado($grado);
                if ($this->agregarPersonalAMesa($id_mesa, 'jurado', $jurado)) {
                    $detalles['jurados_agregados'] = 1;
                } else {
                    $detalles['errores'][] = 'Error al agregar jurado';
                }
            }

            // Generar Testigo Docente si no existe
            if (!isset($personalExistente['testigo_docente'])) {
                $testigoDocente = $this->generarTestigoDocente($grado);
                if ($this->agregarPersonalAMesa($id_mesa, 'testigo_docente', $testigoDocente)) {
                    $detalles['testigos_docentes_agregados'] = 1;
                } else {
                    $detalles['errores'][] = 'Error al agregar testigo docente';
                }
            }

            // Generar Testigos Estudiantes si faltan
            $testigos_estudiantes_existentes = isset($personalExistente['testigo_estudiante']) ? 
                                             count($personalExistente['testigo_estudiante']) : 0;
            $testigos_necesarios = 2 - $testigos_estudiantes_existentes;

            for ($i = 0; $i < $testigos_necesarios; $i++) {
                $testigoEstudiante = $this->generarTestigoEstudiante($grado);
                if ($this->agregarPersonalAMesa($id_mesa, 'testigo_estudiante', $testigoEstudiante)) {
                    $detalles['testigos_estudiantes_agregados']++;
                } else {
                    $detalles['errores'][] = 'Error al agregar testigo estudiante ' . ($i + 1);
                }
            }

            // Verificar si la mesa está completa
            $personalFinal = $this->obtenerPersonalExistente($id_mesa);
            $detalles['mesa_completa'] = (
                isset($personalFinal['jurado']) &&
                isset($personalFinal['testigo_docente']) &&
                isset($personalFinal['testigo_estudiante']) &&
                count($personalFinal['testigo_estudiante']) >= 2
            );

        } catch (\Exception $e) {
            $detalles['errores'][] = 'Error general: ' . $e->getMessage();
        }

        return $detalles;
    }

    /**
     * Obtener personal existente de una mesa
     * @param int $id_mesa ID de la mesa
     * @return array Personal agrupado por tipo
     */
    private function obtenerPersonalExistente($id_mesa) {
        $sql = "SELECT * FROM personal_mesa WHERE id_mesa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mesa]);
        $personal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $agrupado = [];
        foreach ($personal as $persona) {
            $tipo = $persona['tipo_personal'];
            if (!isset($agrupado[$tipo])) {
                $agrupado[$tipo] = [];
            }
            $agrupado[$tipo][] = $persona;
        }

        return $agrupado;
    }

    /**
     * Generar datos de un jurado (padre de familia)
     * @param string $grado Grado de la mesa
     * @return array Datos del jurado
     */
    private function generarJurado($grado) {
        $nombres_padres = [
            'Carlos Andrés Rodríguez', 'María Elena Gómez', 'José Luis Martínez',
            'Ana Patricia López', 'Roberto Carlos Sánchez', 'Claudia Marcela Torres',
            'Fernando Javier Herrera', 'Luz Marina Castillo', 'Diego Alejandro Morales',
            'Sandra Milena Vargas', 'Héctor Fabián Ruiz', 'Gloria Esperanza Jiménez',
            'Mauricio Andrés Pérez', 'Yolanda Patricia Ramírez', 'Álvaro Enrique Silva',
            'Beatriz Elena Ortega', 'Gustavo Adolfo Mejía', 'Rocío del Carmen Aguilar'
        ];

        $apellidos = [
            'González', 'Rodríguez', 'García', 'López', 'Martínez', 'Sánchez',
            'Pérez', 'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández',
            'Díaz', 'Moreno', 'Álvarez', 'Muñoz', 'Romero', 'Navarro'
        ];

        $nombre = $nombres_padres[array_rand($nombres_padres)];
        $documento = '1' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT) . rand(10, 99);
        $telefono = '3' . rand(100000000, 199999999);

        return [
            'nombre_completo' => $nombre,
            'documento_identidad' => $documento,
            'telefono' => $telefono,
            'email' => strtolower(str_replace(' ', '.', $nombre)) . '@email.com',
            'observaciones' => "Padre de familia del grado $grado - Jurado de mesa virtual"
        ];
    }

    /**
     * Generar datos de un testigo docente
     * @param string $grado Grado de la mesa
     * @return array Datos del testigo docente
     */
    private function generarTestigoDocente($grado) {
        $nombres_docentes = [
            'Prof. María Alejandra Castañeda', 'Lic. Carlos Eduardo Morales',
            'Mg. Ana Sofía Herrera', 'Esp. Luis Fernando Gómez',
            'Prof. Diana Carolina Ruiz', 'Lic. Jorge Armando Torres',
            'Mg. Patricia Elena Vargas', 'Esp. Andrés Felipe Sánchez',
            'Prof. Claudia Marcela Jiménez', 'Lic. Ricardo Javier Pérez',
            'Mg. Liliana Esperanza Ortiz', 'Esp. Fabián Alejandro Ramírez',
            'Prof. Mónica Andrea Silva', 'Lic. Héctor Mauricio López',
            'Mg. Esperanza del Carmen Aguilar', 'Esp. Jairo Enrique Mejía'
        ];

        $areas = [
            'Matemáticas', 'Español y Literatura', 'Ciencias Naturales', 'Ciencias Sociales',
            'Inglés', 'Educación Física', 'Artes', 'Tecnología e Informática',
            'Ética y Valores', 'Religión', 'Filosofía', 'Química', 'Física', 'Biología'
        ];

        $nombre = $nombres_docentes[array_rand($nombres_docentes)];
        $area = $areas[array_rand($areas)];
        $documento = '1' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT) . rand(10, 99);
        $telefono = '3' . rand(100000000, 199999999);

        return [
            'nombre_completo' => $nombre,
            'documento_identidad' => $documento,
            'telefono' => $telefono,
            'email' => strtolower(str_replace([' ', '.'], ['', ''], $nombre)) . '@colegio.edu.co',
            'observaciones' => "Docente del área de $area - Testigo de mesa grado $grado"
        ];
    }

    /**
     * Generar datos de un testigo estudiante
     * @param string $grado Grado de la mesa
     * @return array Datos del testigo estudiante
     */
    private function generarTestigoEstudiante($grado) {
        $nombres_estudiantes = [
            'Alejandra Sofía Martínez', 'Sebastián Andrés García', 'Valentina María López',
            'Santiago José Rodríguez', 'Isabella Camila Sánchez', 'Mateo Alejandro Pérez',
            'Sofía Valentina Gómez', 'Daniel Eduardo Torres', 'Camila Andrea Herrera',
            'Nicolás David Morales', 'Mariana Alejandra Castillo', 'Juan Pablo Vargas',
            'Valeria Antonella Jiménez', 'Andrés Felipe Ruiz', 'Gabriela Estefanía Silva',
            'Diego Alejandro Ramírez', 'Antonella Sofía Ortega', 'Samuel David Mejía'
        ];

        // Los testigos estudiantes deben ser de grados superiores (10° o 11°)
        $grados_testigos = ['10', '11'];
        $grado_testigo = $grados_testigos[array_rand($grados_testigos)];

        $nombre = $nombres_estudiantes[array_rand($nombres_estudiantes)];
        $documento = '1' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT) . rand(10, 99);
        $telefono = '3' . rand(100000000, 199999999);

        return [
            'nombre_completo' => $nombre,
            'documento_identidad' => $documento,
            'telefono' => $telefono,
            'email' => strtolower(str_replace(' ', '.', $nombre)) . '@estudiante.edu.co',
            'observaciones' => "Estudiante de grado {$grado_testigo}° - Testigo para mesa de grado $grado"
        ];
    }

    /**
     * Agregar personal a una mesa
     * @param int $id_mesa ID de la mesa
     * @param string $tipo_personal Tipo de personal
     * @param array $datos Datos del personal
     * @return bool True si se agregó exitosamente
     */
    private function agregarPersonalAMesa($id_mesa, $tipo_personal, $datos) {
        $sql = "INSERT INTO personal_mesa 
                (id_mesa, tipo_personal, nombre_completo, documento_identidad, telefono, email, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $id_mesa,
            $tipo_personal,
            $datos['nombre_completo'],
            $datos['documento_identidad'],
            $datos['telefono'],
            $datos['email'],
            $datos['observaciones']
        ]);
    }

    /**
     * Limpiar todo el personal de una elección
     * @param int $id_eleccion ID de la elección
     * @return bool True si se limpió exitosamente
     */
    public function limpiarPersonalEleccion($id_eleccion) {
        $sql = "DELETE pm FROM personal_mesa pm 
                JOIN mesas_virtuales mv ON pm.id_mesa = mv.id_mesa 
                WHERE mv.id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_eleccion]);
    }

    /**
     * Obtener estadísticas de personal por elección
     * @param int $id_eleccion ID de la elección
     * @return array Estadísticas del personal
     */
    public function getEstadisticasPersonal($id_eleccion) {
        $sql = "SELECT 
                    COUNT(DISTINCT mv.id_mesa) as total_mesas,
                    COUNT(DISTINCT pm.id_personal) as total_personal,
                    SUM(CASE WHEN pm.tipo_personal = 'jurado' THEN 1 ELSE 0 END) as total_jurados,
                    SUM(CASE WHEN pm.tipo_personal = 'testigo_docente' THEN 1 ELSE 0 END) as total_testigos_docentes,
                    SUM(CASE WHEN pm.tipo_personal = 'testigo_estudiante' THEN 1 ELSE 0 END) as total_testigos_estudiantes,
                    COUNT(DISTINCT CASE 
                        WHEN (
                            SELECT COUNT(*) FROM personal_mesa pm2 
                            WHERE pm2.id_mesa = mv.id_mesa
                        ) = 4 THEN mv.id_mesa 
                    END) as mesas_completas
                FROM mesas_virtuales mv
                LEFT JOIN personal_mesa pm ON mv.id_mesa = pm.id_mesa
                WHERE mv.id_eleccion = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $resultado = $stmt->get_result()->fetch_assoc();
        
        return [
            'total_mesas' => (int)$resultado['total_mesas'],
            'total_personal' => (int)$resultado['total_personal'],
            'total_jurados' => (int)$resultado['total_jurados'],
            'total_testigos_docentes' => (int)$resultado['total_testigos_docentes'],
            'total_testigos_estudiantes' => (int)$resultado['total_testigos_estudiantes'],
            'mesas_completas' => (int)$resultado['mesas_completas'],
            'porcentaje_completado' => $resultado['total_mesas'] > 0 ? 
                round(($resultado['mesas_completas'] * 100) / $resultado['total_mesas'], 2) : 0
        ];
    }

    /**
     * Regenerar personal para mesas incompletas
     * @param int $id_eleccion ID de la elección
     * @return array Resultado de la regeneración
     */
    public function regenerarPersonalIncompleto($id_eleccion) {
        // Obtener mesas incompletas
        $sql = "SELECT mv.*, 
                       (SELECT COUNT(*) FROM personal_mesa WHERE id_mesa = mv.id_mesa) as personal_actual
                FROM mesas_virtuales mv
                WHERE mv.id_eleccion = ?
                HAVING personal_actual < 4";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_eleccion]);
        $mesasIncompletas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $resultado = [
            'success' => true,
            'mensaje' => '',
            'mesas_procesadas' => 0,
            'personal_agregado' => 0
        ];

        foreach ($mesasIncompletas as $mesa) {
            $detalles = $this->generarPersonalParaMesa($mesa['id_mesa'], $mesa['grado_asignado']);
            $resultado['mesas_procesadas']++;
            $resultado['personal_agregado'] += $detalles['jurados_agregados'] + 
                                             $detalles['testigos_docentes_agregados'] + 
                                             $detalles['testigos_estudiantes_agregados'];
        }

        $resultado['mensaje'] = "Se procesaron {$resultado['mesas_procesadas']} mesas incompletas y se agregaron {$resultado['personal_agregado']} personas.";

        return $resultado;
    }
}
?>
