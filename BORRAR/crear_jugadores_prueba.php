<?php
// Script para crear 12 jugadores de prueba para el equipo ID 1
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Crear Jugadores de Prueba</h2>";

try {
    require_once 'config/db.php';
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Conectado a la base de datos: " . htmlspecialchars($dbname) . "</p>";
    
    // Verificar que existe el equipo ID 1
    $stmt = $conn->prepare("SELECT id, nombre, categoria FROM equipos WHERE id = 1");
    $stmt->execute();
    $equipo = $stmt->get_result()->fetch_assoc();
    
    if (!$equipo) {
        echo "<p style='color: red;'>✗ No existe el equipo con ID 1</p>";
        exit;
    }
    
    echo "<p><strong>Equipo encontrado:</strong> " . htmlspecialchars($equipo['nombre']) . " - " . htmlspecialchars($equipo['categoria']) . "</p>";
    
    // Datos de jugadores de prueba
    $jugadores = [
        ['nombre' => 'Carlos', 'apellidos' => 'García López', 'fecha_nacimiento' => '2010-03-15', 'numero_camiseta' => 4, 'posicion' => 'Base'],
        ['nombre' => 'Miguel', 'apellidos' => 'Rodríguez Sánchez', 'fecha_nacimiento' => '2010-07-22', 'numero_camiseta' => 7, 'posicion' => 'Escolta'],
        ['nombre' => 'David', 'apellidos' => 'Martín Fernández', 'fecha_nacimiento' => '2010-01-08', 'numero_camiseta' => 10, 'posicion' => 'Alero'],
        ['nombre' => 'Alejandro', 'apellidos' => 'López Ruiz', 'fecha_nacimiento' => '2010-11-30', 'numero_camiseta' => 12, 'posicion' => 'Ala-Pívot'],
        ['nombre' => 'Pablo', 'apellidos' => 'González Moreno', 'fecha_nacimiento' => '2010-05-14', 'numero_camiseta' => 15, 'posicion' => 'Pívot'],
        ['nombre' => 'Adrián', 'apellidos' => 'Jiménez Castro', 'fecha_nacimiento' => '2010-09-03', 'numero_camiseta' => 8, 'posicion' => 'Base'],
        ['nombre' => 'Sergio', 'apellidos' => 'Muñoz Herrera', 'fecha_nacimiento' => '2010-12-18', 'numero_camiseta' => 6, 'posicion' => 'Escolta'],
        ['nombre' => 'Javier', 'apellidos' => 'Álvarez Peña', 'fecha_nacimiento' => '2010-04-25', 'numero_camiseta' => 11, 'posicion' => 'Alero'],
        ['nombre' => 'Daniel', 'apellidos' => 'Romero Vega', 'fecha_nacimiento' => '2010-08-12', 'numero_camiseta' => 9, 'posicion' => 'Ala-Pívot'],
        ['nombre' => 'Mario', 'apellidos' => 'Torres Blanco', 'fecha_nacimiento' => '2010-02-07', 'numero_camiseta' => 14, 'posicion' => 'Pívot'],
        ['nombre' => 'Hugo', 'apellidos' => 'Ramos Silva', 'fecha_nacimiento' => '2010-06-19', 'numero_camiseta' => 5, 'posicion' => 'Base'],
        ['nombre' => 'Iker', 'apellidos' => 'Vargas Mendoza', 'fecha_nacimiento' => '2010-10-11', 'numero_camiseta' => 13, 'posicion' => 'Escolta']
    ];
    
    echo "<h3>Insertando jugadores...</h3>";
    
    $insertados = 0;
    $actualizados = 0;
    
    foreach ($jugadores as $jugador) {
        // Verificar si ya existe un jugador con ese número de camiseta en el equipo
        $stmt = $conn->prepare("SELECT id FROM jugadores WHERE equipo_id = 1 AND numero_camiseta = ?");
        $stmt->bind_param("i", $jugador['numero_camiseta']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Actualizar jugador existente
            $stmt = $conn->prepare("UPDATE jugadores SET nombre = ?, apellidos = ?, fecha_nacimiento = ?, posicion = ?, activo = 1, updated_at = NOW() WHERE equipo_id = 1 AND numero_camiseta = ?");
            $stmt->bind_param("ssssi", $jugador['nombre'], $jugador['apellidos'], $jugador['fecha_nacimiento'], $jugador['posicion'], $jugador['numero_camiseta']);
            
            if ($stmt->execute()) {
                echo "<p style='color: orange;'>⚠ Actualizado: " . htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']) . " (#" . $jugador['numero_camiseta'] . ")</p>";
                $actualizados++;
            } else {
                echo "<p style='color: red;'>✗ Error actualizando: " . htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']) . " - " . $stmt->error . "</p>";
            }
        } else {
            // Insertar nuevo jugador
            $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellidos, fecha_nacimiento, equipo_id, numero_camiseta, posicion, activo, created_at) VALUES (?, ?, ?, 1, ?, ?, 1, NOW())");
            $stmt->bind_param("sssis", $jugador['nombre'], $jugador['apellidos'], $jugador['fecha_nacimiento'], $jugador['numero_camiseta'], $jugador['posicion']);
            
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Insertado: " . htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']) . " (#" . $jugador['numero_camiseta'] . ")</p>";
                $insertados++;
            } else {
                echo "<p style='color: red;'>✗ Error insertando: " . htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']) . " - " . $stmt->error . "</p>";
            }
        }
    }
    
    echo "<h3>Verificación final:</h3>";
    
    // Mostrar todos los jugadores del equipo
    $result = $conn->query("SELECT id, nombre, apellidos, numero_camiseta, posicion, fecha_nacimiento FROM jugadores WHERE equipo_id = 1 AND activo = 1 ORDER BY numero_camiseta");
    echo "<p><strong>Jugadores en el equipo:</strong></p>";
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nombre</th><th>Apellidos</th><th>Número</th><th>Posición</th><th>Fecha Nacimiento</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
        echo "<td>" . htmlspecialchars($row['apellidos']) . "</td>";
        echo "<td style='text-align: center; font-weight: bold;'>" . $row['numero_camiseta'] . "</td>";
        echo "<td>" . htmlspecialchars($row['posicion']) . "</td>";
        echo "<td>" . $row['fecha_nacimiento'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
    
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32; margin: 0 0 10px 0;'>✓ Proceso completado</h3>";
    echo "<p style='margin: 0;'>Resumen:</p>";
    echo "<ul style='margin: 10px 0 0 0;'>";
    echo "<li><strong>Jugadores insertados:</strong> $insertados</li>";
    echo "<li><strong>Jugadores actualizados:</strong> $actualizados</li>";
    echo "<li><strong>Total de jugadores en el equipo:</strong> " . ($insertados + $actualizados) . "</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="entrenador/dashboard.php" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🏀 Ir al Dashboard Entrenador
    </a>
    <a href="shared/gestionar_jugadores.php?equipo_id=1" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        👥 Ver Jugadores del Equipo
    </a>
</div>
