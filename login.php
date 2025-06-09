<?php
// Versión funcional del login con diseño restaurado
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

// Cargar configuración del club de forma segura
$clubNombre = 'MiniBasket Pro';
$logoUrl = '';
try {
    if (file_exists('utils/ClubConfig.php')) {
        require_once 'utils/ClubConfig.php';
        $clubConfig = ClubConfig::getInstance();
        $clubNombre = $clubConfig->getNombre();
        $logoUrl = $clubConfig->getLogotipo();
        if ($logoUrl && !empty($logoUrl)) {
            $logoUrl = 'uploads/' . $logoUrl;
        }
    }
} catch (Exception $e) {
    error_log("Error al cargar ClubConfig: " . $e->getMessage());
    // Usar valores por defecto
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
                   $_SESSION['nombre'] = $user['nombre'];
                   $_SESSION['apellidos'] = $user['apellidos'];
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
           $login_error = "Error del sistema. Por favor, contacte al administrador.";
           error_log("Error en login.php: " . $e->getMessage());
       }
   }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Iniciar Sesión - <?php echo htmlspecialchars($clubNombre); ?></title>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <style>
       :root {
           --color-primario: <?php echo $clubConfig->getColorPrimario(); ?>;
           --color-secundario: <?php echo $clubConfig->getColorSecundario(); ?>;
           --color-acento: <?php echo $clubConfig->getColorAcento(); ?>;
           
           /* Convertir HEX a RGB para usar en rgba() */
           --color-primario-rgb: <?php 
               $hex = $clubConfig->getColorPrimario();
               $hex = ltrim($hex, '#');
               list($r, $g, $b) = array_map(function($c) {
                   return hexdec(str_pad($c, 2, $c));
               }, str_split($hex, strlen($hex) > 3 ? 2 : 1));
               echo "$r, $g, $b";
           ?>;
           --color-secundario-rgb: <?php 
               $hex = $clubConfig->getColorSecundario();
               $hex = ltrim($hex, '#');
               list($r, $g, $b) = array_map(function($c) {
                   return hexdec(str_pad($c, 2, $c));
               }, str_split($hex, strlen($hex) > 3 ? 2 : 1));
               echo "$r, $g, $b";
           ?>;
           --color-acento-rgb: <?php 
               $hex = $clubConfig->getColorAcento();
               $hex = ltrim($hex, '#');
               list($r, $g, $b) = array_map(function($c) {
                   return hexdec(str_pad($c, 2, $c));
               }, str_split($hex, strlen($hex) > 3 ? 2 : 1));
               echo "$r, $g, $b";
           ?>;
       }
       
       * {
           margin: 0;
           padding: 0;
           box-sizing: border-box;
       }
       
       body {
           font-family: 'Inter', sans-serif;
           background: linear-gradient(135deg, color-mix(in srgb, var(--color-primario) 10%, white) 0%, color-mix(in srgb, var(--color-secundario) 10%, white) 100%);
           min-height: 100vh;
           display: flex;
           align-items: center;
           justify-content: center;
           padding: 16px;
           position: relative;
           background-image: url('assets/basketball-bg.svg');
           background-size: 150px 150px;
           background-repeat: repeat;
           background-opacity: 0.05;
       }
       
       body::before {
           content: '';
           position: fixed;
           top: 0;
           left: 0;
           width: 100%;
           height: 100%;
           background: rgba(255, 255, 255, 0.85);
           z-index: 1;
           pointer-events: none;
       }
       
       .login-container {
           background: white;
           padding: 32px;
           border-radius: 16px;
           box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
           width: 100%;
           max-width: 400px;
           border: 1px solid #f3f4f6;
           backdrop-filter: blur(10px);
           position: relative;
           z-index: 2;
       }
       
       .logo-container {
           text-align: center;
           margin-bottom: 32px;
       }
       
       .logo {
           width: 80px;
           height: 80px;
           background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
           border-radius: 50%;
           display: flex;
           align-items: center;
           justify-content: center;
           margin: 0 auto 16px;
           overflow: hidden;
           box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
       }
       
       .logo img {
           width: 100%;
           height: 100%;
           object-fit: cover;
           border-radius: 50%;
       }
       
       .logo svg {
           width: 40px;
           height: 40px;
           color: white;
       }
       
       .title {
           font-size: 1.875rem;
           font-weight: 700;
           color: #1f2937;
           margin-bottom: 8px;
           background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
           -webkit-background-clip: text;
           -webkit-text-fill-color: transparent;
           background-clip: text;
       }
       
       .subtitle {
           color: #6b7280;
           font-weight: 500;
       }
       
       .error-alert {
           background: linear-gradient(135deg, #fef2f2, #fee2e2);
           border-left: 4px solid #ef4444;
           color: #b91c1c;
           padding: 16px;
           border-radius: 12px;
           margin-bottom: 24px;
           display: flex;
           align-items: flex-start;
           box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);
       }
       
       .error-alert svg {
           width: 20px;
           height: 20px;
           margin-right: 12px;
           margin-top: 2px;
           flex-shrink: 0;
       }
       
       .form-group {
           margin-bottom: 24px;
       }
       
       .form-label {
           display: block;
           font-size: 0.875rem;
           font-weight: 600;
           color: #374151;
           margin-bottom: 8px;
       }
       
       .input-container {
           position: relative;
       }
       
       .form-input {
           width: 100%;
           padding: 14px 16px 14px 48px;
           border: 2px solid #e5e7eb;
           border-radius: 12px;
           box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
           font-size: 16px;
           transition: all 0.3s ease;
           background: #fafafa;
       }
       
       .form-input:focus {
           outline: none;
           border-color: var(--color-primario);
           box-shadow: 0 0 0 3px rgba(var(--color-primario-rgb), 0.1);
           background: white;
           transform: translateY(-1px);
       }
       
       .input-icon {
           position: absolute;
           left: 16px;
           top: 50%;
           transform: translateY(-50%);
           width: 20px;
           height: 20px;
           color: #9ca3af;
           transition: color 0.3s ease;
       }
       
       .form-input:focus ~ .input-icon {
           color: var(--color-primario);
       }
       
       .submit-btn {
           width: 100%;
           background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
           color: white;
           font-weight: 600;
           padding: 14px 16px;
           border: none;
           border-radius: 12px;
           box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
           cursor: pointer;
           transition: all 0.3s ease;
           display: flex;
           align-items: center;
           justify-content: center;
           font-size: 16px;
           position: relative;
           overflow: hidden;
       }
       
       .submit-btn::before {
           content: '';
           position: absolute;
           top: 0;
           left: -100%;
           width: 100%;
           height: 100%;
           background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
           transition: left 0.5s;
       }
       
       .submit-btn:hover::before {
           left: 100%;
       }
       
       .submit-btn:hover {
           transform: translateY(-2px);
           box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);
       }
       
       .submit-btn:active {
           transform: translateY(0);
       }
       
       .submit-btn svg {
           width: 20px;
           height: 20px;
           margin-right: 8px;
       }
       
       .footer {
           position: absolute;
           bottom: 16px;
           left: 50%;
           transform: translateX(-50%);
           font-size: 0.875rem;
           color: #6b7280;
           text-align: center;
           background: rgba(255, 255, 255, 0.8);
           padding: 8px 16px;
           border-radius: 20px;
           backdrop-filter: blur(10px);
           z-index: 2;
       }
       
       /* Animaciones */
       @keyframes fadeInUp {
           from {
               opacity: 0;
               transform: translateY(30px);
           }
           to {
               opacity: 1;
               transform: translateY(0);
           }
       }
       
       .login-container {
           animation: fadeInUp 0.6s ease-out;
       }
       
       @media (max-width: 640px) {
           .login-container {
               padding: 24px;
               margin: 16px;
           }
           
           .title {
               font-size: 1.5rem;
           }
           
           .logo {
               width: 70px;
               height: 70px;
           }
           
           .footer {
               position: relative;
               margin-top: 20px;
               bottom: auto;
               left: auto;
               transform: none;
           }
       }
       
       /* Efectos hover para inputs */
       .input-container:hover .form-input {
           border-color: #d1d5db;
       }
       
       .input-container:hover .input-icon {
           color: #6b7280;
       }
   </style>
</head>
<body>
   <div class="login-container">
       <!-- Logo y título -->
       <div class="logo-container">
           <div class="logo">
               <?php if ($logoUrl && file_exists($logoUrl)): ?>
                   <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="Logo del Club">
               <?php else: ?>
                   <svg fill="currentColor" viewBox="0 0 24 24">
                       <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                   </svg>
               <?php endif; ?>
           </div>
           <h1 class="title"><?php echo htmlspecialchars($clubNombre); ?></h1>
           <p class="subtitle">Gestión de equipos de baloncesto</p>
       </div>
       
       <?php if (!empty($login_error)): ?>
           <div class="error-alert">
               <svg fill="currentColor" viewBox="0 0 20 20">
                   <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
               </svg>
               <span><?php echo htmlspecialchars($login_error); ?></span>
           </div>
       <?php endif; ?>

       <form action="login.php" method="post">
           <div class="form-group">
               <label for="username" class="form-label">Usuario</label>
               <div class="input-container">
                   <input type="text" 
                          name="username" 
                          id="username" 
                          required 
                          value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                          class="form-input"
                          placeholder="Ingresa tu usuario">
                   <svg class="input-icon" fill="currentColor" viewBox="0 0 20 20">
                       <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                   </svg>
               </div>
           </div>
           
           <div class="form-group">
               <label for="password" class="form-label">Contraseña</label>
               <div class="input-container">
                   <input type="password" 
                          name="password" 
                          id="password" 
                          required
                          class="form-input"
                          placeholder="Ingresa tu contraseña">
                   <svg class="input-icon" fill="currentColor" viewBox="0 0 20 20">
                       <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2-2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                   </svg>
               </div>
           </div>
           
           <button type="submit" class="submit-btn">
               <svg fill="currentColor" viewBox="0 0 20 20">
                   <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd"/>
               </svg>
               Iniciar Sesión
           </button>
       </form>
       
   </div>
   
   <!-- Footer -->
   <div class="footer">
       <p>© 2024 <?php echo htmlspecialchars($clubNombre); ?>. Todos los derechos reservados.</p>
   </div>
</body>
</html>
