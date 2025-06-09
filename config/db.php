<?php
// Configuración unificada de base de datos
$servername = "localhost";
$username = "root";
$password = "1nt3l3kSYS%";
$dbname = "minibasket_pro"; // Asegúrate de que este sea el nombre correcto

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Verificar conexión
    if ($conn->connect_error) {
        error_log("Error de conexión MySQL: " . $conn->connect_error);
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    // Establecer charset
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error estableciendo charset: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Error en config/db.php: " . $e->getMessage());
    die("Error de conexión a la base de datos. Contacte al administrador.");
}
?>
