<?php
// Archivo de diagnóstico para identificar problemas

echo "<h1>Diagnóstico del Sistema</h1>";

// 1. Verificar versión de PHP
echo "<h2>1. Información de PHP</h2>";
echo "<p><strong>Versión de PHP:</strong> " . phpversion() . "</p>";
echo "<p><strong>Errores mostrados:</strong> " . (ini_get('display_errors') ? 'Sí' : 'No') . "</p>";

// 2. Verificar archivos
echo "<h2>2. Verificación de Archivos</h2>";
$archivos_importantes = [
    'login.php',
    'utils/ClubConfig.php',
    'config/database.php',
    'admin/dashboard.php',
    'entrenador/dashboard.php'
];

foreach ($archivos_importantes as $archivo) {
    if (file_exists($archivo)) {
        echo "<p>✅ <strong>$archivo</strong> - Existe</p>";
    } else {
        echo "<p>❌ <strong>$archivo</strong> - NO existe</p>";
    }
}

// 3. Verificar conexión a base de datos
echo "<h2>3. Conexión a Base de Datos</h2>";
try {
    $servername = "localhost";
    $username = "root";
    $password = "1nt3l3kSYS%";
    $dbname = "minibasket_pro";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        echo "<p>❌ <strong>Error de conexión:</strong> " . $conn->connect_error . "</p>";
    } else {
        echo "<p>✅ <strong>Conexión exitosa</strong></p>";
        
        // Verificar tablas
        $tablas = ['administradores', 'entrenadores', 'club'];
        foreach ($tablas as $tabla) {
            $result = $conn->query("SHOW TABLES LIKE '$tabla'");
            if ($result->num_rows > 0) {
                echo "<p>✅ Tabla <strong>$tabla</strong> existe</p>";
            } else {
                echo "<p>❌ Tabla <strong>$tabla</strong> NO existe</p>";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p>❌ <strong>Error:</strong> " . $e->getMessage() . "</p>";
}

// 4. Verificar permisos de archivos
echo "<h2>4. Permisos de Archivos</h2>";
if (is_writable('.')) {
    echo "<p>✅ <strong>Directorio actual</strong> - Escribible</p>";
} else {
    echo "<p>❌ <strong>Directorio actual</strong> - NO escribible</p>";
}

if (is_dir('uploads')) {
    if (is_writable('uploads')) {
        echo "<p>✅ <strong>Directorio uploads</strong> - Escribible</p>";
    } else {
        echo "<p>❌ <strong>Directorio uploads</strong> - NO escribible</p>";
    }
} else {
    echo "<p>⚠️ <strong>Directorio uploads</strong> - NO existe</p>";
}

// 5. Verificar logs de error
echo "<h2>5. Logs de Error</h2>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<p><strong>Archivo de log:</strong> $error_log</p>";
    $lines = file($error_log);
    $recent_lines = array_slice($lines, -10);
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    foreach ($recent_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p>No se encontró archivo de log de errores</p>";
}

// 6. Verificar sintaxis de login.php
echo "<h2>6. Verificación de Sintaxis</h2>";
if (file_exists('login.php')) {
    $output = [];
    $return_var = 0;
    exec('php -l login.php 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "<p>✅ <strong>login.php</strong> - Sintaxis correcta</p>";
    } else {
        echo "<p>❌ <strong>login.php</strong> - Error de sintaxis:</p>";
        echo "<pre style='background: #ffebee; padding: 10px; border-radius: 5px; color: #c62828;'>";
        foreach ($output as $line) {
            echo htmlspecialchars($line) . "\n";
        }
        echo "</pre>";
    }
}

echo "<hr>";
echo "<p><strong>Recomendación:</strong> Usa <a href='login_basico.php'>login_basico.php</a> para probar el login sin dependencias.</p>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2 {
    color: #333;
}

p {
    margin: 5px 0;
}

pre {
    overflow-x: auto;
    max-height: 200px;
    overflow-y: auto;
}
</style>
