<?php
// Script completo para diagnosticar y solucionar el problema del login
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Solución Completa del Problema de Login</h1>";

// Paso 1: Verificar configuración actual
echo "<h2>PASO 1: Verificación de Configuración</h2>";

try {
    require_once 'config/db.php';
    
    echo "<div style='background: #e8f5e8; padding: 15px; margin: 10px 0; border-left: 4px solid #4caf50;'>";
    echo "<h3>✓ Conexión exitosa</h3>";
    echo "<p><strong>Servidor:</strong> " . htmlspecialchars($servername) . "</p>";
    echo "<p><strong>Base de datos:</strong> " . htmlspecialchars($dbname) . "</p>";
    echo "<p><strong>Usuario:</strong> " . htmlspecialchars($username) . "</p>";
    echo "</div>";
    
    // Verificar qué base de datos estamos usando realmente
    $result = $conn->query("SELECT DATABASE() as current_db");
    $current_db = $result->fetch_assoc()['current_db'];
    echo "<p><strong>Base de datos actual:</strong> " . htmlspecialchars($current_db) . "</p>";
    
    if ($current_db !== $dbname) {
        echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336;'>";
        echo "<h3>⚠ PROBLEMA DETECTADO</h3>";
        echo "<p>La base de datos configurada ($dbname) no coincide con la actual ($current_db)</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336;'>";
    echo "<h3>✗ Error de conexión</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    exit;
}

// Paso 2: Verificar estructura de tablas
echo "<h2>PASO 2: Verificación de Tablas</h2>";

$tablas_necesarias = ['administradores', 'entrenadores'];
$tablas_existentes = [];

foreach ($tablas_necesarias as $tabla) {
    $result = $conn->query("SHOW TABLES LIKE '$tabla'");
    if ($result->num_rows > 0) {
        $tablas_existentes[] = $tabla;
        echo "<p style='color: green;'>✓ Tabla '$tabla' existe</p>";
        
        // Contar registros
        $result = $conn->query("SELECT COUNT(*) as count FROM $tabla");
        $count = $result->fetch_assoc()['count'];
        echo "<p style='margin-left: 20px;'>→ $count registros</p>";
    } else {
        echo "<p style='color: red;'>✗ Tabla '$tabla' NO existe</p>";
    }
}

// Paso 3: Crear tablas si no existen
if (count($tablas_existentes) < count($tablas_necesarias)) {
    echo "<h2>PASO 3: Creando Tablas Faltantes</h2>";
    
    // Crear tabla administradores
    if (!in_array('administradores', $tablas_existentes)) {
        $sql = "CREATE TABLE `administradores` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `apellidos` varchar(100) NOT NULL,
            `email` varchar(255) NOT NULL UNIQUE,
            `usuario` varchar(50) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `activo` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Tabla 'administradores' creada</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando tabla 'administradores': " . $conn->error . "</p>";
        }
    }
    
    // Crear tabla entrenadores
    if (!in_array('entrenadores', $tablas_existentes)) {
        $sql = "CREATE TABLE `entrenadores` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `apellidos` varchar(100) NOT NULL,
            `email` varchar(255) NOT NULL UNIQUE,
            `telefono` varchar(20) DEFAULT NULL,
            `usuario` varchar(50) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL,
            `activo` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Tabla 'entrenadores' creada</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creando tabla 'entrenadores': " . $conn->error . "</p>";
        }
    }
}

// Paso 4: Insertar usuarios de prueba
echo "<h2>PASO 4: Insertando Usuarios de Prueba</h2>";

$password_hash = password_hash('password', PASSWORD_DEFAULT);

// Insertar administrador
$stmt = $conn->prepare("SELECT id FROM administradores WHERE usuario = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO administradores (nombre, apellidos, email, usuario, password, activo) VALUES (?, ?, ?, ?, ?, ?)");
    $nombre = 'Admin';
    $apellidos = 'Sistema';
    $email = 'admin@minibasket.com';
    $usuario = 'admin';
    $activo = 1;
    
    $stmt->bind_param("sssssi", $nombre, $apellidos, $email, $usuario, $password_hash, $activo);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Administrador 'admin' insertado</p>";
    } else {
        echo "<p style='color: red;'>✗ Error insertando administrador: " . $stmt->error . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Administrador 'admin' ya existe</p>";
    
    // Actualizar contraseña
    $stmt = $conn->prepare("UPDATE administradores SET password = ? WHERE usuario = 'admin'");
    $stmt->bind_param("s", $password_hash);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Contraseña del administrador actualizada</p>";
    }
}

// Insertar entrenador
$stmt = $conn->prepare("SELECT id FROM entrenadores WHERE usuario = 'entrenador1'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO entrenadores (nombre, apellidos, email, telefono, usuario, password, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $nombre = 'Juan';
    $apellidos = 'Pérez García';
    $email = 'juan.perez@minibasket.com';
    $telefono = '666123456';
    $usuario = 'entrenador1';
    $activo = 1;
    
    $stmt->bind_param("ssssssi", $nombre, $apellidos, $email, $telefono, $usuario, $password_hash, $activo);
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Entrenador 'entrenador1' insertado</p>";
    } else {
        echo "<p style='color: red;'>✗ Error insertando entrenador: " . $stmt->error . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Entrenador 'entrenador1' ya existe</p>";
    
    // Actualizar contraseña
    $stmt = $conn->prepare("UPDATE entrenadores SET password = ? WHERE usuario = 'entrenador1'");
    $stmt->bind_param("s", $password_hash);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Contraseña del entrenador actualizada</p>";
    }
}

// Paso 5: Verificación final
echo "<h2>PASO 5: Verificación Final</h2>";

// Verificar administradores
$result = $conn->query("SELECT id, usuario, nombre, apellidos, activo FROM administradores");
echo "<h3>Administradores:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f5f5f5;'><th>ID</th><th>Usuario</th><th>Nombre</th><th>Activo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) . "</td>";
        echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No hay administradores</p>";
}

// Verificar entrenadores
$result = $conn->query("SELECT id, usuario, nombre, apellidos, activo FROM entrenadores");
echo "<h3>Entrenadores:</h3>";
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f5f5f5;'><th>ID</th><th>Usuario</th><th>Nombre</th><th>Activo</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nombre'] . ' ' . $row['apellidos']) . "</td>";
        echo "<td>" . ($row['activo'] ? 'Sí' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>✗ No hay entrenadores</p>";
}

// Paso 6: Probar login
echo "<h2>PASO 6: Prueba de Login</h2>";

echo "<h3>Probando login de administrador:</h3>";
$stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM administradores WHERE usuario = ? AND activo = 1");
$test_user = 'admin';
$stmt->bind_param("s", $test_user);
$stmt->execute();
$result = $stmt->get_result();

echo "<p>Filas encontradas: " . $result->num_rows . "</p>";

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ Usuario encontrado: " . htmlspecialchars($user['usuario']) . "</p>";
    
    $test_password = 'password';
    if (password_verify($test_password, $user['password'])) {
        echo "<p style='color: green;'>✓ Contraseña verificada correctamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error en verificación de contraseña</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Usuario no encontrado</p>";
}

$conn->close();

echo "<div style='background: #e3f2fd; padding: 20px; margin: 20px 0; border-left: 4px solid #2196f3;'>";
echo "<h2 style='margin: 0 0 15px 0; color: #1976d2;'>🎉 Configuración Completada</h2>";
echo "<p style='margin: 0 0 10px 0;'>El sistema debería estar funcionando ahora. Puedes probar:</p>";
echo "<div style='margin: 15px 0;'>";
echo "<a href='login_debug.php' style='background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔧 Probar Debug</a>";
echo "<a href='login.php' style='background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Ir al Login</a>";
echo "</div>";
echo "<p style='margin: 15px 0 0 0; font-size: 14px;'><strong>Credenciales:</strong><br>";
echo "Admin: usuario = <code>admin</code>, contraseña = <code>password</code><br>";
echo "Entrenador: usuario = <code>entrenador1</code>, contraseña = <code>password</code></p>";
echo "</div>";
?>
