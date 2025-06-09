<?php
// Script para insertar usuarios de prueba en la base de datos correcta
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Insertar Usuarios de Prueba</h2>";

try {
    require_once 'config/db.php';
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Conectado a la base de datos: " . htmlspecialchars($dbname) . "</p>";
    
    // Hash de la contraseña "password"
    $password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "<h3>Insertando administrador...</h3>";
    
    // Verificar si ya existe el admin
    $stmt = $conn->prepare("SELECT id FROM administradores WHERE usuario = ?");
    $admin_user = 'admin';
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ El administrador 'admin' ya existe</p>";
        
        // Actualizar la contraseña por si acaso
        $stmt = $conn->prepare("UPDATE administradores SET password = ? WHERE usuario = ?");
        $stmt->bind_param("ss", $password_hash, $admin_user);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Contraseña del administrador actualizada</p>";
        }
    } else {
        // Insertar nuevo administrador
        $stmt = $conn->prepare("INSERT INTO administradores (nombre, apellidos, email, usuario, password, activo) VALUES (?, ?, ?, ?, ?, ?)");
        $nombre = 'Admin';
        $apellidos = 'Sistema';
        $email = 'admin@minibasket.com';
        $activo = 1;
        
        $stmt->bind_param("sssssi", $nombre, $apellidos, $email, $admin_user, $password_hash, $activo);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Administrador 'admin' insertado correctamente</p>";
        } else {
            echo "<p style='color: red;'>✗ Error insertando administrador: " . $stmt->error . "</p>";
        }
    }
    
    echo "<h3>Insertando entrenador...</h3>";
    
    // Verificar si ya existe el entrenador
    $stmt = $conn->prepare("SELECT id FROM entrenadores WHERE usuario = ?");
    $entrenador_user = 'entrenador1';
    $stmt->bind_param("s", $entrenador_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ El entrenador 'entrenador1' ya existe</p>";
        
        // Actualizar la contraseña por si acaso
        $stmt = $conn->prepare("UPDATE entrenadores SET password = ? WHERE usuario = ?");
        $stmt->bind_param("ss", $password_hash, $entrenador_user);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Contraseña del entrenador actualizada</p>";
        }
    } else {
        // Insertar nuevo entrenador
        $stmt = $conn->prepare("INSERT INTO entrenadores (nombre, apellidos, email, telefono, usuario, password, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $nombre = 'Juan';
        $apellidos = 'Pérez García';
        $email = 'juan.perez@minibasket.com';
        $telefono = '666123456';
        $activo = 1;
        
        $stmt->bind_param("ssssssi", $nombre, $apellidos, $email, $telefono, $entrenador_user, $password_hash, $activo);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Entrenador 'entrenador1' insertado correctamente</p>";
        } else {
            echo "<p style='color: red;'>✗ Error insertando entrenador: " . $stmt->error . "</p>";
        }
    }
    
    echo "<h3>Verificación final:</h3>";
    
    // Verificar administradores
    $result = $conn->query("SELECT id, usuario, nombre, apellidos FROM administradores");
    echo "<p><strong>Administradores en la base de datos:</strong></p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: " . $row['id'] . " - Usuario: " . htmlspecialchars($row['usuario']) . " - Nombre: " . htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) . "</li>";
    }
    echo "</ul>";
    
    // Verificar entrenadores
    $result = $conn->query("SELECT id, usuario, nombre, apellidos FROM entrenadores");
    echo "<p><strong>Entrenadores en la base de datos:</strong></p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: " . $row['id'] . " - Usuario: " . htmlspecialchars($row['usuario']) . " - Nombre: " . htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) . "</li>";
    }
    echo "</ul>";
    
    $conn->close();
    
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #2e7d32; margin: 0 0 10px 0;'>✓ Proceso completado</h3>";
    echo "<p style='margin: 0;'>Ahora puedes probar el login con:</p>";
    echo "<ul style='margin: 10px 0 0 0;'>";
    echo "<li><strong>Admin:</strong> usuario = admin, contraseña = password</li>";
    echo "<li><strong>Entrenador:</strong> usuario = entrenador1, contraseña = password</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<div style="margin-top: 30px;">
    <a href="login_debug.php" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
        🔧 Probar Login Debug
    </a>
    <a href="login.php" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">
        🚀 Ir al Login
    </a>
</div>
