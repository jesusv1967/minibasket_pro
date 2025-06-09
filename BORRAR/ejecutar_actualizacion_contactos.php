<?php
// Script para fusionar campos de contacto y simplificar estructura
try {
    $servername = "localhost";
    $username = "root";
    $password = "1nt3l3kSYS%";
    $dbname = "minibasket_pro";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<h2>Actualizando tabla jugadores - Fusión de campos de contacto...</h2>";
    
    // Verificar si existe la columna contacto_nombre_padre
    $result = $conn->query("SHOW COLUMNS FROM jugadores LIKE 'contacto_nombre_padre'");
    if ($result->num_rows > 0) {
        // Fusionar datos si existen ambos campos
        $sql = "UPDATE jugadores SET 
                contacto_nombre = CASE 
                    WHEN contacto_nombre IS NOT NULL AND contacto_nombre_padre IS NOT NULL 
                        THEN CONCAT(contacto_nombre, ' / ', contacto_nombre_padre)
                    WHEN contacto_nombre_padre IS NOT NULL 
                        THEN contacto_nombre_padre
                    ELSE contacto_nombre
                END
                WHERE contacto_nombre_padre IS NOT NULL";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Datos de contacto fusionados correctamente</p>";
        }
        
        // Eliminar la columna contacto_nombre_padre
        $sql = "ALTER TABLE jugadores DROP COLUMN contacto_nombre_padre";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna contacto_nombre_padre eliminada</p>";
        } else {
            throw new Exception("Error eliminando columna contacto_nombre_padre: " . $conn->error);
        }
    } else {
        echo "<p style='color: orange;'>✓ La columna contacto_nombre_padre ya no existe</p>";
    }
    
    // Verificar y añadir columnas necesarias si no existen
    $result = $conn->query("SHOW COLUMNS FROM jugadores LIKE 'contacto_nombre'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE jugadores ADD COLUMN contacto_nombre VARCHAR(100) DEFAULT NULL COMMENT 'Nombre del contacto principal' AFTER posicion";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna contacto_nombre añadida</p>";
        } else {
            throw new Exception("Error añadiendo columna contacto_nombre: " . $conn->error);
        }
    }
    
    $result = $conn->query("SHOW COLUMNS FROM jugadores LIKE 'contacto_relacion'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE jugadores ADD COLUMN contacto_relacion ENUM('Padre', 'Madre', 'Tutor', 'Otro') DEFAULT NULL COMMENT 'Relación del contacto con el jugador' AFTER contacto_nombre";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna contacto_relacion añadida</p>";
        } else {
            throw new Exception("Error añadiendo columna contacto_relacion: " . $conn->error);
        }
    }
    
    $result = $conn->query("SHOW COLUMNS FROM jugadores LIKE 'contacto_telefono'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE jugadores ADD COLUMN contacto_telefono VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono del contacto' AFTER contacto_relacion";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Columna contacto_telefono añadida</p>";
        } else {
            throw new Exception("Error añadiendo columna contacto_telefono: " . $conn->error);
        }
    }
    
    // Asegurar que numero_camiseta permite 00-99
    $sql = "ALTER TABLE jugadores MODIFY COLUMN numero_camiseta VARCHAR(2) DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✓ Campo numero_camiseta configurado para 00-99</p>";
    } else {
        throw new Exception("Error modificando campo numero_camiseta: " . $conn->error);
    }
    
    // Mostrar estructura actualizada
    echo "<h3>Estructura actualizada de la tabla jugadores:</h3>";
    $result = $conn->query("DESCRIBE jugadores");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>✓ Actualización completada exitosamente</p>";
    echo "<p><a href='admin/gestionar_jugadores.php'>Ir a gestionar jugadores (Admin)</a></p>";
    echo "<p><a href='entrenador/gestionar_jugadores.php'>Ir a gestionar jugadores (Entrenador)</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
