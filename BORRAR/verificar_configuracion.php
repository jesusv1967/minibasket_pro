<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Verificación de Configuración de Base de Datos</h2>";

echo "<h3>1. Verificando config/db.php:</h3>";
try {
    require_once 'config/db.php';
    echo "<p style='color: green;'>✓ Archivo config/db.php cargado correctamente</p>";
    
    // Mostrar información de conexión (sin contraseña)
    echo "<p><strong>Información de conexión:</strong></p>";
    echo "<ul>";
    echo "<li>Servidor: " . htmlspecialchars($servername ?? 'NO DEFINIDO') . "</li>";
    echo "<li>Usuario: " . htmlspecialchars($username ?? 'NO DEFINIDO') . "</li>";
    echo "<li>Base de datos: " . htmlspecialchars($dbname ?? 'NO DEFINIDO') . "</li>";
    echo "<li>Estado de conexión: " . ($conn->connect_error ? "ERROR: " . $conn->connect_error : "CONECTADO") . "</li>";
    echo "</ul>";
    
    if (!$conn->connect_error) {
        // Probar consulta simple
        $result = $conn->query("SELECT DATABASE() as current_db");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p><strong>Base de datos actual:</strong> " . htmlspecialchars($row['current_db']) . "</p>";
        }
        
        // Listar todas las tablas
        echo "<h4>Tablas en la base de datos:</h4>";
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . htmlspecialchars($row[0]) . "</li>";
            }
            echo "</ul>";
        }
        
        // Verificar contenido de tabla administradores
        echo "<h4>Contenido de tabla administradores:</h4>";
        $result = $conn->query("SELECT id, usuario, nombre, apellidos, activo FROM administradores");
        if ($result) {
            if ($result->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Activo</th></tr>";
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
                echo "<p style='color: red;'>⚠ La tabla administradores está vacía</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Error consultando administradores: " . $conn->error . "</p>";
        }
        
        // Verificar contenido de tabla entrenadores
        echo "<h4>Contenido de tabla entrenadores:</h4>";
        $result = $conn->query("SELECT id, usuario, nombre, apellidos, activo FROM entrenadores");
        if ($result) {
            if ($result->num_rows > 0) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>ID</th><th>Usuario</th><th>Nombre</th><th>Activo</th></tr>";
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
                echo "<p style='color: red;'>⚠ La tabla entrenadores está vacía</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Error consultando entrenadores: " . $conn->error . "</p>";
        }
        
        // Probar la consulta exacta que usa el login
        echo "<h4>Probando consulta de login para admin:</h4>";
        $stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM administradores WHERE usuario = ? AND activo = 1");
        if ($stmt) {
            $test_user = 'admin';
            $stmt->bind_param("s", $test_user);
            $stmt->execute();
            $result = $stmt->get_result();
            echo "<p>Filas encontradas con usuario 'admin': " . $result->num_rows . "</p>";
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "<p>Usuario encontrado: " . htmlspecialchars($user['usuario']) . "</p>";
                echo "<p>Hash de contraseña: " . substr($user['password'], 0, 30) . "...</p>";
            }
            $stmt->close();
        } else {
            echo "<p style='color: red;'>Error preparando consulta: " . $conn->error . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";

echo "<h3>2. Verificando config/database.php (si existe):</h3>";
if (file_exists('config/database.php')) {
    try {
        // Resetear variables para evitar conflictos
        unset($conn);
        require_once 'config/database.php';
        
        $database = new Database();
        $pdo_conn = $database->getConnection();
        
        echo "<p style='color: green;'>✓ Archivo config/database.php cargado correctamente</p>";
        echo "<p><strong>Configuración PDO:</strong></p>";
        echo "<ul>";
        echo "<li>Host: " . htmlspecialchars($database->getHost()) . "</li>";
        echo "<li>Base de datos: " . htmlspecialchars($database->getDbName()) . "</li>";
        echo "<li>Usuario: " . htmlspecialchars($database->getUsername()) . "</li>";
        echo "</ul>";
        
        // Probar consulta con PDO
        $stmt = $pdo_conn->query("SELECT DATABASE() as current_db");
        $row = $stmt->fetch();
        echo "<p><strong>Base de datos actual (PDO):</strong> " . htmlspecialchars($row['current_db']) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error con config/database.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Archivo config/database.php no existe</p>";
}

echo "<hr>";
echo "<h3>3. Información del sistema:</h3>";
echo "<ul>";
echo "<li><strong>Directorio actual:</strong> " . getcwd() . "</li>";
echo "<li><strong>Archivos en config/:</strong> " . (is_dir('config') ? implode(', ', scandir('config')) : 'Directorio no existe') . "</li>";
echo "</ul>";
?>
