<?php

// autoload.php

spl_autoload_register(function ($class) {
    // Convierte el namespace a ruta de archivo
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    
    // Verifica si el archivo existe
    if (file_exists($file)) {
        require_once $file;
    }
});
?>