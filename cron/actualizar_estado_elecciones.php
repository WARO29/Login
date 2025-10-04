<?php
// Script para actualizar automáticamente el estado de las elecciones
// Este script debe ser ejecutado por un cron job cada minuto

// Cargar el autoloader
require_once dirname(__DIR__) . '/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use models\EleccionConfigModel;

// Inicializar el modelo de elecciones
$eleccionModel = new EleccionConfigModel();

// Activar elecciones programadas que llegaron a su hora
$eleccionesActivadas = $eleccionModel->activarEleccionesProgramadas();

// Cerrar elecciones que llegaron a su hora de cierre
$eleccionesCerradas = $eleccionModel->cerrarEleccionesVencidas();

// Registrar en log
$mensaje = date('Y-m-d H:i:s') . " - Cron ejecutado: ";
$mensaje .= "Elecciones activadas: " . $eleccionesActivadas . ", ";
$mensaje .= "Elecciones cerradas: " . $eleccionesCerradas . "\n";

// Guardar en archivo de log
$logFile = dirname(__DIR__) . '/logs/elecciones_cron.log';
file_put_contents($logFile, $mensaje, FILE_APPEND);

// Salida para verificación
echo $mensaje;

