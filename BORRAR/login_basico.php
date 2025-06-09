<?php
// Login básico sin dependencias para debugging
session_start();

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id'])) {
   if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
       header("Location: admin/dashboard.php");
   } else {
       header("Location: entrenador/dashboard.php");
   }
   exit();
}

$login_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $username = trim($_POST['username']);
   $password = trim($_POST['password']);

   if (empty($username) || empty($password)) {
       $login_error = "El nombre de usuario y la contraseña son requeridos.";
   } else {
       try {
           // Configuración de base de datos
           $servername = "localhost";
           $db_username = "root";
           $db_password = "1nt3l3kSYS%";
           $dbname = "minibasket_pro";
           
           $conn = new mysqli($servername, $db_username, $db_password, $dbname);
           
           if ($conn->connect_error) {
               throw new Exception("Error de conexión: " . $conn->connect_error);
           }
           
           $conn->set_charset("utf8mb4");
           
           // Intentar login como administrador primero
           $stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM administradores WHERE usuario = ? AND activo = 1");
           
           if (!$stmt) {
               throw new Exception("Error en la preparación de la consulta: " . $conn->error);
           }
           
           $stmt->bind_param("s", $username);
           $stmt->execute();
           $result = $stmt->get_result();
           
           if ($result->num_rows == 1) {
               $user = $result->fetch_assoc();
               
               if (password_verify($password, $user['password'])) {
                   // Login exitoso como administrador
                   $_SESSION['user_id'] = $user['id'];
                   $_SESSION['username'] = $user['usuario'];
                   $_SESSION['nombre_completo'] = $user['nombre'] . ' ' . $user['apellidos'];
                   $_SESSION['user_type'] = 'admin';
                   
                   $stmt->close();
                   $conn->close();
                   
                   header("Location: admin/dashboard.php");
                   exit();
               }
           }
           
           // Si no es admin, intentar como entrenador
           $stmt->close();
           $stmt = $conn->prepare("SELECT id, nombre, apellidos, usuario, password FROM entrenadores WHERE usuario = ? AND activo = 1");
           
           if (!$stmt) {
               throw new Exception("Error en la preparación de la consulta: " . $conn->error);
           }
           
           $stmt->bind_param("s", $username);
           $stmt->execute();
           $result = $stmt->get_result();
           
           if ($result->num_rows == 1) {
               $user = $result->fetch_assoc();
               
               if (password_verify($password, $user['password'])) {
                   // Login exitoso como entrenador
                   $_SESSION['user_id'] = $user['id'];
                   $_SESSION['username'] = $user['usuario'];
                   $_SESSION['nombre_completo'] = $user['nombre'] . ' ' . $user['apellidos'];
                   $_SESSION['user_type'] = 'entrenador';
                   
                   $stmt->close();
                   $conn->close();
                   
                   header("Location: entrenador/dashboard.php");
                   exit();
               }
           }
           
           // Si llegamos aquí, las credenciales son incorrectas
           $login_error = "Nombre de usuario o contraseña incorrectos.";
           
           if ($stmt) {
               $stmt->close();
           }
           $conn->close();
           
       } catch (Exception $e) {
           $login_error = "Error del sistema: " . $e->getMessage();
           error_log("Error en login_basico.php: " . $e->getMessage());
       }
   }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login - MiniBasket Pro</title>
   <style>
       body {
           font-family: Arial, sans-serif;
           background: #f0f0f0;
           margin: 0;
           padding: 20px;
           display: flex;
           justify-content: center;
           align-items: center;
           min-height: 100vh;
       }
       
       .login-container {
           background: white;
           padding: 30px;
           border-radius: 10px;
           box-shadow: 0 0 20px rgba(0,0,0,0.1);
           width: 100%;
           max-width: 400px;
       }
       
       .title {
           text-align: center;
           color: #333;
           margin-bottom: 30px;
       }
       
       .error {
           background: #ffebee;
           color: #c62828;
           padding: 10px;
           border-radius: 5px;
           margin-bottom: 20px;
           border-left: 4px solid #c62828;
       }
       
       .form-group {
           margin-bottom: 20px;
       }
       
       label {
           display: block;
           margin-bottom: 5px;
           font-weight: bold;
           color: #555;
       }
       
       input[type="text"], input[type="password"] {
           width: 100%;
           padding: 12px;
           border: 1px solid #ddd;
           border-radius: 5px;
           font-size: 16px;
           box-sizing: border-box;
       }
       
       input[type="text"]:focus, input[type="password"]:focus {
           outline: none;
           border-color: #4CAF50;
       }
       
       .submit-btn {
           width: 100%;
           background: #4CAF50;
           color: white;
           padding: 12px;
           border: none;
           border-radius: 5px;
           font-size: 16px;
           cursor: pointer;
       }
       
       .submit-btn:hover {
           background: #45a049;
       }
       
       .test-info {
           margin-top: 20px;
           padding: 15px;
           background: #f5f5f5;
           border-radius: 5px;
           font-size: 14px;
       }
       
       .test-info h4 {
           margin: 0 0 10px 0;
           color: #333;
       }
       
       .test-info p {
           margin: 5px 0;
           color: #666;
       }
       
       code {
           background: #e0e0e0;
           padding: 2px 4px;
           border-radius: 3px;
           font-family: monospace;
       }
   </style>
</head>
<body>
   <div class="login-container">
       <h1 class="title">MiniBasket Pro</h1>
       <p style="text-align: center; color: #666; margin-bottom: 30px;">Sistema de Gestión</p>
       
       <?php if (!empty($login_error)): ?>
           <div class="error">
               <?php echo htmlspecialchars($login_error); ?>
           </div>
       <?php endif; ?>

       <form action="login_basico.php" method="post">
           <div class="form-group">
               <label for="username">Usuario:</label>
               <input type="text" 
                      name="username" 
                      id="username" 
                      required 
                      value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                      placeholder="Ingresa tu usuario">
           </div>
           
           <div class="form-group">
               <label for="password">Contraseña:</label>
               <input type="password" 
                      name="password" 
                      id="password" 
                      required
                      placeholder="Ingresa tu contraseña">
           </div>
           
           <button type="submit" class="submit-btn">Iniciar Sesión</button>
       </form>
       
       <div class="test-info">
           <h4>Usuarios de prueba:</h4>
           <p><strong>Administrador:</strong> <code>admin</code> / <code>password</code></p>
           <p><strong>Entrenador:</strong> <code>entrenador1</code> / <code>password</code></p>
       </div>
   </div>
</body>
</html>
