<?php
// Script para ejecutar la actualización de la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Actualización de Base de Datos - Añadir Temporada a Equipos</h2>";

try {
    // USAR LA MISMA CONFIGURACIÓN QUE FUNCIONA
    $servername = "localhost";
    $db_username = "root";
    $db_password = "1nt3l3kSYS%";
    $dbname = "minibasket_pro";
    
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<h3>1. Verificando estructura actual de la tabla equipos:</h3>";
    
    // Verificar si ya existe la columna temporada
    $result = $conn->query("SHOW COLUMNS FROM equipos LIKE 'temporada'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ La columna 'temporada' ya existe en la tabla equipos</p>";
    } else {
        echo "<p style='color: blue;'>→ La columna 'temporada' no existe, procediendo a crearla...</p>";
        
        // Añadir la columna temporada
        $sql = "ALTER TABLE equipos ADD COLUMN temporada VARCHAR(20) NOT NULL DEFAULT '2024-2025' AFTER categoria";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna 'temporada' añadida correctamente</p>";
        } else {
            throw new Exception("Error al añadir la columna temporada: " . $conn->error);
        }
    }
    
    echo "<h3>2. Actualizando equipos existentes:</h3>";
    
    // Actualizar equipos que tengan temporada vacía
    $sql = "UPDATE equipos SET temporada = '2024-2025' WHERE temporada = '' OR temporada IS NULL";
    
    if ($conn->query($sql)) {
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: green;'>✓ $affected_rows equipos actualizados con la temporada 2024-2025</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al actualizar equipos: " . $conn->error . "</p>";
    }
    
    echo "<h3>3. Creando índice para mejorar rendimiento:</h3>";
    
    // Verificar si ya existe el índice
    $result = $conn->query("SHOW INDEX FROM equipos WHERE Key_name = 'idx_temporada'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ El índice 'idx_temporada' ya existe</p>";
    } else {
        $sql = "CREATE INDEX idx_temporada ON equipos(temporada)";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Índice 'idx_temporada' creado correctamente</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al crear índice: " . $conn->error . "</p>";
        }
    }
    
    echo "<h3>4. Verificación final:</h3>";
    
    // Mostrar estructura actualizada
    $result = $conn->query("DESCRIBE equipos");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f5f5f5;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar equipos actuales
    echo "<h3>5. Equipos actuales:</h3>";
    $result = $conn->query("SELECT id, nombre, categoria, temporada, activo FROM equipos ORDER BY temporada DESC, categoria, nombre");
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f5f5f5;'><th>ID</th><th>Nombre</th><th>Categoría</th><th>Temporada</th><th>Activo</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['categoria']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['temporada']) . "</strong></td>";
            echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No hay equipos registrados</p>";
    }
    
    $conn->close();
    
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32; margin: 0 0 10px 0;'>✓ Actualización completada exitosamente</h3>";
    echo "<p style='margin: 0;'>La tabla equipos ahora incluye el campo temporada y está lista para usar.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #c62828; margin: 0 0 10px 0;'>✗ Error durante la actualización</h3>";
    echo "<p style='margin: 0;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='gestionar_equipos.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
echo "🚀 Ir a Gestionar Equipos";
echo "</a>";
echo "</div>";
?>
