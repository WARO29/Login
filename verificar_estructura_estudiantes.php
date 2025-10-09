<?php
// Script para verificar la estructura de la tabla estudiantes

try {
    // Configuración de base de datos
    $host = 'localhost';
    $user = 'warg';
    $password = '123456';
    $database = 'elecciones_cosafa_2';
    
    $db = new mysqli($host, $user, $password, $database);
    
    if ($db->connect_error) {
        die("Error de conexión: " . $db->connect_error);
    }
    
    echo "<h2>Estructura de la tabla 'estudiantes':</h2>";
    
    // Verificar si la tabla existe
    $result = $db->query("SHOW TABLES LIKE 'estudiantes'");
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ La tabla 'estudiantes' NO existe.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ La tabla 'estudiantes' existe.</p>";
    
    // Mostrar estructura de la tabla
    $result = $db->query("DESCRIBE estudiantes");
    
    echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>Campo</th>";
    echo "<th style='padding: 8px;'>Tipo</th>";
    echo "<th style='padding: 8px;'>Nulo</th>";
    echo "<th style='padding: 8px;'>Clave</th>";
    echo "<th style='padding: 8px;'>Por defecto</th>";
    echo "<th style='padding: 8px;'>Extra</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='padding: 8px; font-weight: bold;'>" . $row['Field'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Type'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Null'] . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Key'] . "</td>";
        echo "<td style='padding: 8px;'>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td style='padding: 8px;'>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar algunos registros de ejemplo
    echo "<h3>Registros de ejemplo (primeros 5):</h3>";
    $result = $db->query("SELECT * FROM estudiantes LIMIT 5");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        
        // Encabezados
        $first_row = $result->fetch_assoc();
        echo "<tr style='background-color: #f0f0f0;'>";
        foreach (array_keys($first_row) as $column) {
            echo "<th style='padding: 8px;'>$column</th>";
        }
        echo "</tr>";
        
        // Primera fila
        echo "<tr>";
        foreach ($first_row as $value) {
            echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
        
        // Resto de filas
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay registros en la tabla.</p>";
    }
    
    // Contar total de registros
    $result = $db->query("SELECT COUNT(*) as total FROM estudiantes");
    $total = $result->fetch_assoc()['total'];
    echo "<p><strong>Total de estudiantes registrados:</strong> $total</p>";
    
    $db->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}

table {
    background-color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

h2, h3 {
    color: #333;
}
</style>
