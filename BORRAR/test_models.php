<?php
// Archivo para probar que los modelos funcionan correctamente
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Probando modelos...</h2>";

try {
    echo "<p>1. Probando conexión de base de datos...</p>";
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>✓ Conexión a base de datos exitosa</p>";
    
    echo "<p>2. Probando modelo Club...</p>";
    require_once 'models/Club.php';
    $club = new Club();
    echo "<p style='color: green;'>✓ Modelo Club cargado correctamente</p>";
    
    // Intentar obtener datos del club
    if ($club->obtenerDatos()) {
        echo "<p style='color: green;'>✓ Datos del club obtenidos: " . htmlspecialchars($club->nombre) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ No hay datos del club en la base de datos</p>";
    }
    
    echo "<p>3. Probando modelo Entrenador...</p>";
    require_once 'models/Entrenador.php';
    $entrenador = new Entrenador();
    echo "<p style='color: green;'>✓ Modelo Entrenador cargado correctamente</p>";
    
    echo "<p>4. Probando modelo Equipo...</p>";
    require_once 'models/Equipo.php';
    $equipo = new Equipo();
    echo "<p style='color: green;'>✓ Modelo Equipo cargado correctamente</p>";
    
    echo "<h3 style='color: green;'>¡Todos los modelos funcionan correctamente!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Detalles del error:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
