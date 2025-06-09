<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>Actualizando tabla equipos - Añadiendo campo temporada</h2>";
    
    // Leer el archivo SQL
    $sql = file_get_contents('actualizar_tabla_equipos_temporada.sql');
    
    // Dividir en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query) && !preg_match('/^--/', $query)) {
            try {
                $conn->exec($query);
                echo "<p style='color: green;'>✓ Ejecutado: " . substr($query, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "<p style='color: orange;'>⚠ Campo 'temporada' ya existe</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<h3 style='color: green;'>Actualización completada</h3>";
    echo "<p><a href='entrenador/dashboard.php'>Ir al Dashboard del Entrenador</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Error de conexión: " . $e->getMessage() . "</h3>";
}
?>
