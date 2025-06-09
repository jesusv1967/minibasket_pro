<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h2>🔧 Creando tablas de partidos...</h2>";
    
    // Crear tabla partidos
    $sql_partidos = "CREATE TABLE IF NOT EXISTS `partidos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `equipo_local_id` int(11) NOT NULL,
      `equipo_visitante` varchar(100) NOT NULL,
      `fecha` datetime NOT NULL,
      `periodos` int(11) NOT NULL DEFAULT 4,
      `reglas_pasarela` tinyint(1) DEFAULT 0,
      `estado` enum('programado','en_curso','finalizado','cancelado') DEFAULT 'programado',
      `resultado_local` int(11) DEFAULT NULL,
      `resultado_visitante` int(11) DEFAULT NULL,
      `observaciones` text,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `fk_equipo_local` (`equipo_local_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql_partidos);
    echo "<p>✅ Tabla 'partidos' creada exitosamente</p>";
    
    // Crear tabla partido_jugadores
    $sql_partido_jugadores = "CREATE TABLE IF NOT EXISTS `partido_jugadores` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `partido_id` int(11) NOT NULL,
      `jugador_id` int(11) NOT NULL,
      `presente` tinyint(1) DEFAULT 1,
      `minutos_jugados` int(11) DEFAULT 0,
      `puntos` int(11) DEFAULT 0,
      `rebotes` int(11) DEFAULT 0,
      `asistencias` int(11) DEFAULT 0,
      `faltas` int(11) DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `fk_partido` (`partido_id`),
      KEY `fk_jugador_partido` (`jugador_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $conn->exec($sql_partido_jugadores);
    echo "<p>✅ Tabla 'partido_jugadores' creada exitosamente</p>";
    
    // Intentar agregar las claves foráneas (si no existen)
    try {
        $conn->exec("ALTER TABLE `partidos` ADD CONSTRAINT `fk_equipo_local` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE");
        echo "<p>✅ Clave foránea para equipos agregada</p>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p>ℹ️ Clave foránea para equipos ya existe</p>";
        } else {
            echo "<p>⚠️ No se pudo agregar clave foránea para equipos: " . $e->getMessage() . "</p>";
        }
    }
    
    try {
        $conn->exec("ALTER TABLE `partido_jugadores` ADD CONSTRAINT `fk_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE");
        echo "<p>✅ Clave foránea para partidos agregada</p>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p>ℹ️ Clave foránea para partidos ya existe</p>";
        } else {
            echo "<p>⚠️ No se pudo agregar clave foránea para partidos: " . $e->getMessage() . "</p>";
        }
    }
    
    try {
        $conn->exec("ALTER TABLE `partido_jugadores` ADD CONSTRAINT `fk_jugador_partido` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE");
        echo "<p>✅ Clave foránea para jugadores agregada</p>";
    } catch(PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p>ℹ️ Clave foránea para jugadores ya existe</p>";
        } else {
            echo "<p>⚠️ No se pudo agregar clave foránea para jugadores: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>📊 Verificando estructura de tablas:</h3>";
    
    // Verificar tabla partidos
    $stmt = $conn->query("DESCRIBE partidos");
    echo "<h4>Tabla partidos:</h4>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar tabla partido_jugadores
    $stmt = $conn->query("DESCRIBE partido_jugadores");
    echo "<h4>Tabla partido_jugadores:</h4>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Por defecto</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎯 ¡Listo para usar!</h3>";
    echo "<p><a href='entrenador/gestionar_partidos.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>➡️ Ir a Gestionar Partidos</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>❌ Error al crear las tablas</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Código de error: " . $e->getCode() . "</p>";
    
    // Información adicional para depuración
    echo "<h3>🔍 Información de depuración:</h3>";
    echo "<p><strong>Driver:</strong> " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "</p>";
    echo "<p><strong>Versión del servidor:</strong> " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
}
?>
