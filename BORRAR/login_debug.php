<?php
// Versión de debug del login para identificar el problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h2>Debug del Login</h2>";

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
    echo "<p>Usuario ya logueado. Redirigiendo...</p>";
    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: entrenador/dashboard.php");
    }
    exit();
}

$login_error = '';
$debug_info = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $debug_info[] = "Usuario ingresado: " . htmlspecialchars($username);
    $debug_info[] = "Contraseña ingresada: " . (empty($password) ? "VACÍA" : "****** (longitud: " . strlen($password) . ")");

    if (empty($username) || empty($password)) {
        $login_error = "El nombre de usuario y la contraseña son requeridos.";
        $debug_info[] = "ERROR: Campos vacíos";
    } else {
        try {
            $debug_info[] = "Intentando conectar a la base de datos...";
            require_once 'config/db.php';
            
            if ($conn->connect_error) {
                throw new Exception("Error de conexión: " . $conn->connect_error);
            }
            
            $debug_info[] = "✓ Conexión exitosa";
            
            // Intentar login como administrador primero
            $debug_info[] = "Buscando en tabla administradores...";
            $stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM administradores WHERE usuario = ? AND activo = 1");
            
            if (!$stmt) {
                throw new Exception("Error en la preparación de la consulta: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $debug_info[] = "Filas encontradas en administradores: " . $result->num_rows;
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                $debug_info[] = "Usuario encontrado: " . htmlspecialchars($user['usuario']);
                $debug_info[] = "Hash almacenado: " . substr($user['password'], 0, 20) . "...";
                
                // Verificar contraseña
                $password_check = password_verify($password, $user['password']);
                $debug_info[] = "Verificación de contraseña: " . ($password_check ? "✓ CORRECTA" : "✗ INCORRECTA");
                
                if ($password_check) {
                    // Login exitoso como administrador
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['usuario'];
                    $_SESSION['nombre_completo'] = $user['nombre'] . ' ' . $user['apellidos'];
                    $_SESSION['user_type'] = 'admin';
                    
                    $debug_info[] = "✓ Login exitoso como administrador";
                    $debug_info[] = "Sesión creada - ID: " . $_SESSION['user_id'];
                    
                    $stmt->close();
                    $conn->close();
                    
                    echo "<div style='background: green; color: white; padding: 10px; margin: 10px 0;'>";
                    echo "LOGIN EXITOSO - Redirigiendo a admin/dashboard.php";
                    echo "</div>";
                    
                    echo "<script>setTimeout(function(){ window.location.href = 'admin/dashboard.php'; }, 2000);</script>";
                    
                } else {
                    $login_error = "Nombre de usuario o contraseña incorrectos.";
                    $debug_info[] = "ERROR: Contraseña incorrecta para administrador";
                }
            } else {
                // Si no es admin, intentar como entrenador
                $debug_info[] = "No encontrado en administradores, buscando en entrenadores...";
                $stmt->close();
                
                $stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM entrenadores WHERE usuario = ? AND activo = 1");
                
                if (!$stmt) {
                    throw new Exception("Error en la preparación de la consulta: " . $conn->error);
                }
                
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $debug_info[] = "Filas encontradas en entrenadores: " . $result->num_rows;
                
                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    $debug_info[] = "Entrenador encontrado: " . htmlspecialchars($user['usuario']);
                    $debug_info[] = "Hash almacenado: " . substr($user['password'], 0, 20) . "...";
                    
                    // Verificar contraseña
                    $password_check = password_verify($password, $user['password']);
                    $debug_info[] = "Verificación de contraseña: " . ($password_check ? "✓ CORRECTA" : "✗ INCORRECTA");
                    
                    if ($password_check) {
                        // Login exitoso como entrenador
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['usuario'];
                        $_SESSION['nombre_completo'] = $user['nombre'] . ' ' . $user['apellidos'];
                        $_SESSION['user_type'] = 'entrenador';
                        
                        $debug_info[] = "✓ Login exitoso como entrenador";
                        $debug_info[] = "Sesión creada - ID: " . $_SESSION['user_id'];
                        
                        $stmt->close();
                        $conn->close();
                        
                        echo "<div style='background: green; color: white; padding: 10px; margin: 10px 0;'>";
                        echo "LOGIN EXITOSO - Redirigiendo a entrenador/dashboard.php";
                        echo "</div>";
                        
                        echo "<script>setTimeout(function(){ window.location.href = 'entrenador/dashboard.php'; }, 2000);</script>";
                        
                    } else {
                        $login_error = "Nombre de usuario o contraseña incorrectos.";
                        $debug_info[] = "ERROR: Contraseña incorrecta para entrenador";
                    }
                } else {
                    $login_error = "Nombre de usuario o contraseña incorrectos.";
                    $debug_info[] = "ERROR: Usuario no encontrado en ninguna tabla";
                }
            }
            
            if ($stmt) {
                $stmt->close();
            }
            $conn->close();
            
        } catch (Exception $e) {
            $login_error = "Error del sistema. Por favor, contacte al administrador.";
            $debug_info[] = "EXCEPCIÓN: " . $e->getMessage();
            error_log("Error en login_debug.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Login - MiniBasket Pro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-box { background: #f0f0f0; border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .error { background: #ffebee; border-color: #f44336; color: #c62828; }
        .success { background: #e8f5e8; border-color: #4caf50; color: #2e7d32; }
        .form-container { background: white; padding: 20px; border: 1px solid #ddd; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Debug del Sistema de Login</h1>
    
    <?php if (!empty($debug_info)): ?>
        <div class="debug-box">
            <h3>Información de Debug:</h3>
            <ol>
                <?php foreach ($debug_info as $info): ?>
                    <li><?php echo htmlspecialchars($info); ?></li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($login_error)): ?>
        <div class="debug-box error">
            <strong>Error:</strong> <?php echo htmlspecialchars($login_error); ?>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <h2>Formulario de Login</h2>
        <form action="login_debug.php" method="post">
            <div style="margin-bottom: 15px;">
                <label for="username">Usuario:</label><br>
                <input type="text" name="username" id="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       style="width: 200px; padding: 5px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="password">Contraseña:</label><br>
                <input type="password" name="password" id="password" required
                       style="width: 200px; padding: 5px;">
            </div>
            
            <button type="submit" style="padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer;">
                Iniciar Sesión
            </button>
        </form>
        
        <div style="margin-top: 20px; padding: 10px; background: #e3f2fd; border: 1px solid #2196f3;">
            <strong>Usuarios de prueba:</strong><br>
            Admin: usuario = <code>admin</code>, contraseña = <code>password</code><br>
            Entrenador: usuario = <code>entrenador1</code>, contraseña = <code>password</code>
        </div>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="login.php" style="color: #007cba;">← Volver al login normal</a>
    </div>
</body>
</html>
