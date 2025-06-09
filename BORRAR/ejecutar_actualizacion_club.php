<?php
// Script para ejecutar la actualización de la tabla club con campos de colores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Actualización de Base de Datos - Añadir Colores al Club</h2>";

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
    
    echo "<h3>1. Verificando estructura actual de la tabla club:</h3>";
    
    // Verificar si ya existen las columnas de colores
    $result = $conn->query("SHOW COLUMNS FROM club LIKE 'color_primario'");
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Las columnas de colores ya existen en la tabla club</p>";
    } else {
        echo "<p style='color: blue;'>→ Las columnas de colores no existen, procediendo a crearlas...</p>";
        
        // Añadir las columnas de colores
        $queries = [
            "ALTER TABLE club ADD COLUMN color_primario VARCHAR(7) DEFAULT '#f97316' AFTER logotipo",
            "ALTER TABLE club ADD COLUMN color_secundario VARCHAR(7) DEFAULT '#2563eb' AFTER color_primario",
            "ALTER TABLE club ADD COLUMN color_acento VARCHAR(7) DEFAULT '#16a34a' AFTER color_secundario",
            "ALTER TABLE club ADD COLUMN colores_extraidos TINYINT(1) DEFAULT 0 AFTER color_acento"
        ];
        
        foreach ($queries as $query) {
            if ($conn->query($query)) {
                echo "<p style='color: green;'>✓ Columna añadida correctamente</p>";
            } else {
                throw new Exception("Error al ejecutar query: " . $conn->error);
            }
        }
    }
    
    echo "<h3>2. Actualizando clubs existentes:</h3>";
    
    // Actualizar clubs que no tengan colores asignados
    $sql = "UPDATE club SET 
                color_primario = '#f97316',
                color_secundario = '#2563eb', 
                color_acento = '#16a34a',
                colores_extraidos = 0
            WHERE color_primario IS NULL OR color_primario = ''";
    
    if ($conn->query($sql)) {
        $affected_rows = $conn->affected_rows;
        echo "<p style='color: green;'>✓ $affected_rows clubs actualizados con colores por defecto</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al actualizar clubs: " . $conn->error . "</p>";
    }
    
    echo "<h3>3. Verificación final:</h3>";
    
    // Mostrar estructura actualizada
    $result = $conn->query("DESCRIBE club");
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
    
    // Mostrar datos actuales del club
    echo "<h3>4. Datos actuales del club:</h3>";
    $result = $conn->query("SELECT * FROM club LIMIT 1");
    
    if ($result->num_rows > 0) {
        $club = $result->fetch_assoc();
        echo "<div style='background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;'>";
        echo "<h4>Información del Club:</h4>";
        echo "<p><strong>Nombre:</strong> " . htmlspecialchars($club['nombre']) . "</p>";
        echo "<p><strong>Logotipo:</strong> " . ($club['logotipo'] ? htmlspecialchars($club['logotipo']) : 'No definido') . "</p>";
        echo "<p><strong>Color Primario:</strong> <span style='background: " . htmlspecialchars($club['color_primario']) . "; color: white; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($club['color_primario']) . "</span></p>";
        echo "<p><strong>Color Secundario:</strong> <span style='background: " . htmlspecialchars($club['color_secundario']) . "; color: white; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($club['color_secundario']) . "</span></p>";
        echo "<p><strong>Color Acento:</strong> <span style='background: " . htmlspecialchars($club['color_acento']) . "; color: white; padding: 2px 8px; border-radius: 4px;'>" . htmlspecialchars($club['color_acento']) . "</span></p>";
        echo "<p><strong>Colores Extraídos:</strong> " . ($club['colores_extraidos'] ? 'Sí' : 'No') . "</p>";
        echo "</div>";
    } else {
        echo "<p>No hay datos del club</p>";
    }
    
    $conn->close();
    
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32; margin: 0 0 10px 0;'>✓ Actualización completada exitosamente</h3>";
    echo "<p style='margin: 0;'>La tabla club ahora incluye los campos de colores y está lista para la extracción automática de colores del logotipo.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #c62828; margin: 0 0 10px 0;'>✗ Error durante la actualización</h3>";
    echo "<p style='margin: 0;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='margin-top: 30px;'>";
echo "<a href='admin/gestionar_club.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>";
echo "🎨 Ir a Gestionar Club";
echo "</a>";
echo "<a href='test_color_extraction.php' style='background: #16a34a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
echo "🧪 Probar Extracción de Colores";
echo "</a>";
echo "</div>";
?>
