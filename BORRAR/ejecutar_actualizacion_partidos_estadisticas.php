<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Leer y ejecutar el archivo SQL
    $sql = file_get_contents('actualizar_tabla_partidos_estadisticas.sql');
    
    // Dividir en consultas individuales
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            $conn->exec($query);
            echo "✅ Consulta ejecutada: " . substr($query, 0, 50) . "...\n";
        }
    }
    
    echo "\n🎉 ¡Actualización completada exitosamente!\n";
    echo "📊 Ahora puedes registrar tanteos y asignaciones de jugadores por período.\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
