<?php
namespace config;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

use mysqli;

class Database {
    private $host = 'localhost'; // Cambia esto según tu configuración
    private $user = 'warg'; // Cambia esto según tu configuración
    private $password = '123456'; // Cambia esto según tu configuración
    private $database = 'elecciones_cosafa_2'; // Cambia esto según tu configuración
    private $conn;

    // Método para obtener la conexión
    public function getConnection() {
        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);

        // Verificar conexión
        if ($this->conn->connect_error) {
            die("Conexión fallida: " . $this->conn->connect_error);
        }

        return $this->conn;
    }
    // Método para cerrar la conexión
        public function closeConnection() {
            if ($this->conn) {
                $this->conn->close();
            }
        }
    
}


?>
