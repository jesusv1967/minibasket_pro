<?php
session_start();
// Verificar que es administrador
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
   header('Location: ../login.php');
   exit();
}

// Incluir el extractor de colores
require_once '../utils/ColorExtractor.php';

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$club_nombre = '';
$club_logotipo = '';
$club_id = null;
$colores_club = [
   'primario' => '#f97316',
   'secundario' => '#2563eb',
   'acento' => '#16a34a'
];

try {
   // USAR LA MISMA CONFIGURACIÓN QUE FUNCIONA
   $servername = "localhost";
   $db_username = "root";
   $db_password = "1nt3l3kSYS%";
   $dbname = "minibasket_pro";
   
   $conn = new mysqli($servername, $db_username, $db_password, $dbname);
   
   if ($conn->connect_error) {
       throw new Exception("Error de conexión: " . $conn->connect_error);
   }
   
   $conn->set_charset("utf8mb4");
   
   // Procesar formulario
   if($_SERVER["REQUEST_METHOD"] == "POST") {
       if(isset($_POST['actualizar_club'])) {
           $nombre = trim($_POST['nombre']);
           $logotipo_actual = '';
           
           // Verificar si existe el club
           $stmt = $conn->prepare("SELECT id, logotipo, color_primario, color_secundario, color_acento FROM club LIMIT 1");
           $stmt->execute();
           $result = $stmt->get_result();
           
           if ($result->num_rows > 0) {
               $club_data = $result->fetch_assoc();
               $club_id = $club_data['id'];
               $logotipo_actual = $club_data['logotipo'];
               $colores_club['primario'] = $club_data['color_primario'] ?? '#f97316';
               $colores_club['secundario'] = $club_data['color_secundario'] ?? '#2563eb';
               $colores_club['acento'] = $club_data['color_acento'] ?? '#16a34a';
           }
           
           // Manejar subida de logotipo
           $logotipo = $logotipo_actual; // Por defecto mantener el actual
           $colores_extraidos = false;
           
           if(isset($_FILES['logotipo']) && $_FILES['logotipo']['error'] === 0) {
               $upload_dir = '../uploads/';
               
               // Crear directorio si no existe
               if (!file_exists($upload_dir)) {
                   mkdir($upload_dir, 0755, true);
               }
               
               $file_extension = pathinfo($_FILES['logotipo']['name'], PATHINFO_EXTENSION);
               $new_filename = 'logo_club_' . time() . '.' . $file_extension;
               $upload_path = $upload_dir . $new_filename;
               
               // Validar tipo de archivo
               $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
               if(in_array(strtolower($file_extension), $allowed_types)) {
                   if(move_uploaded_file($_FILES['logotipo']['tmp_name'], $upload_path)) {
                       $logotipo = $new_filename;
                       
                       // Extraer colores del nuevo logotipo
                       try {
                           $colores_dominantes = ColorExtractor::extractColors($upload_path, 3);
                           $colores_seleccionados = ColorExtractor::selectBestColors($colores_dominantes);
                           
                           $colores_club['primario'] = $colores_seleccionados['primario'];
                           $colores_club['secundario'] = $colores_seleccionados['secundario'];
                           $colores_club['acento'] = $colores_seleccionados['acento'];
                           $colores_extraidos = true;
                           
                           $mensaje .= ' Colores extraídos automáticamente del logotipo.';
                       } catch (Exception $e) {
                           error_log("Error extrayendo colores: " . $e->getMessage());
                           // Mantener colores por defecto si falla la extracción
                       }
                   } else {
                       $mensaje = 'Error al subir el archivo';
                       $tipo_mensaje = 'error';
                   }
               } else {
                   $mensaje = 'Tipo de archivo no permitido';
                   $tipo_mensaje = 'error';
               }
           }
           
           if(empty($mensaje) || $tipo_mensaje !== 'error') {
               if($club_id) {
                   // Actualizar club existente
                   $stmt = $conn->prepare("UPDATE club SET nombre = ?, logotipo = ?, color_primario = ?, color_secundario = ?, color_acento = ?, colores_extraidos = ? WHERE id = ?");
                   $stmt->bind_param("sssssii", $nombre, $logotipo, $colores_club['primario'], $colores_club['secundario'], $colores_club['acento'], $colores_extraidos, $club_id);
                   
                   if($stmt->execute()) {
                       $mensaje = 'Datos del club actualizados correctamente.' . $mensaje;
                       $tipo_mensaje = 'success';
                   } else {
                       $mensaje = 'Error al actualizar los datos del club: ' . $stmt->error;
                       $tipo_mensaje = 'error';
                   }
               } else {
                   // Crear club si no existe
                   $stmt = $conn->prepare("INSERT INTO club (nombre, logotipo, color_primario, color_secundario, color_acento, colores_extraidos) VALUES (?, ?, ?, ?, ?, ?)");
                   $stmt->bind_param("sssssi", $nombre, $logotipo, $colores_club['primario'], $colores_club['secundario'], $colores_club['acento'], $colores_extraidos);
                   
                   if($stmt->execute()) {
                       $mensaje = 'Club creado correctamente.' . $mensaje;
                       $tipo_mensaje = 'success';
                       $club_id = $conn->insert_id;
                   } else {
                       $mensaje = 'Error al crear el club: ' . $stmt->error;
                       $tipo_mensaje = 'error';
                   }
               }
           }
       }
   }
   
   // Obtener datos actuales del club
   $stmt = $conn->prepare("SELECT id, nombre, logotipo, color_primario, color_secundario, color_acento, colores_extraidos FROM club LIMIT 1");
   $stmt->execute();
   $result = $stmt->get_result();
   
   if ($result->num_rows > 0) {
       $club_data = $result->fetch_assoc();
       $club_id = $club_data['id'];
       $club_nombre = $club_data['nombre'];
       $club_logotipo = $club_data['logotipo'];
       $colores_club['primario'] = $club_data['color_primario'] ?? '#f97316';
       $colores_club['secundario'] = $club_data['color_secundario'] ?? '#2563eb';
       $colores_club['acento'] = $club_data['color_acento'] ?? '#16a34a';
   }
   
   $conn->close();
   
} catch (Exception $e) {
   $mensaje = 'Error del sistema: ' . $e->getMessage();
   $tipo_mensaje = 'error';
   error_log("Error en gestionar_club.php: " . $e->getMessage());
}

// Cargar configuración del club
require_once '../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();

$page_title = 'Gestionar Club - ' . $clubConfig->getNombre();
?>
<?php include '../includes/club_header.php'; ?>

<?php
// Renderizar header
$clubConfig->renderHeader(
    $clubConfig->getNombre(),
    'Gestionar Club',
    'Administrador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8 fallback-container">
   <?php if($mensaje): ?>
       <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800 fallback-alert-success' : 'bg-red-50 border-l-4 border-red-500 text-red-800 fallback-alert-error'; ?>">
           <?php echo htmlspecialchars($mensaje); ?>
       </div>
   <?php endif; ?>
   
   <!-- Previsualización de colores actuales -->
   <div class="bg-white rounded-xl shadow-lg p-6 mb-6 fallback-card">
       <h2 class="text-xl font-semibold mb-4" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 16px;">Colores Actuales del Club</h2>
       <div class="flex flex-wrap items-center gap-6" style="display: flex; flex-wrap: wrap; align-items: center; gap: 24px;">
           <div class="flex items-center" style="display: flex; align-items: center;">
               <div class="color-preview" style="background-color: <?php echo $colores_club['primario']; ?>; width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-right: 10px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
               <div>
                   <p class="font-medium">Color Primario</p>
                   <p class="text-sm text-gray-600"><?php echo $colores_club['primario']; ?></p>
               </div>
           </div>
           <div class="flex items-center" style="display: flex; align-items: center;">
               <div class="color-preview" style="background-color: <?php echo $colores_club['secundario']; ?>; width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-right: 10px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
               <div>
                   <p class="font-medium">Color Secundario</p>
                   <p class="text-sm text-gray-600"><?php echo $colores_club['secundario']; ?></p>
               </div>
           </div>
           <div class="flex items-center" style="display: flex; align-items: center;">
               <div class="color-preview" style="background-color: <?php echo $colores_club['acento']; ?>; width: 30px; height: 30px; border-radius: 50%; display: inline-block; margin-right: 10px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
               <div>
                   <p class="font-medium">Color Acento</p>
                   <p class="text-sm text-gray-600"><?php echo $colores_club['acento']; ?></p>
               </div>
           </div>
       </div>
       <p class="text-sm text-gray-500 mt-4">
           💡 Los colores se extraen automáticamente del logotipo cuando lo subes. Esto personaliza toda la interfaz según la identidad visual de tu club.
       </p>
   </div>
   
   <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
       <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">Datos del Club</h2>
       
       <form method="POST" enctype="multipart/form-data">
           <div class="mb-6 fallback-form-group">
               <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                   Nombre del Club
               </label>
               <input type="text" 
                      id="nombre" 
                      name="nombre" 
                      value="<?php echo htmlspecialchars($club_nombre); ?>" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input" 
                      required>
           </div>
           
           <div class="mb-6 fallback-form-group">
               <label for="logotipo" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                   Logotipo del Club
               </label>
               <input type="file" 
                      id="logotipo" 
                      name="logotipo" 
                      accept="image/*" 
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-file-input">
               <p class="mt-1 text-sm text-gray-500 fallback-help-text">
                   Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB.
                   <br><strong>🎨 Los colores de la interfaz se actualizarán automáticamente según el logotipo.</strong>
               </p>
               
               <?php if($club_logotipo): ?>
                   <div class="mt-4">
                       <p class="text-sm font-medium text-gray-700 mb-2">Logotipo actual:</p>
                       <img src="../uploads/<?php echo htmlspecialchars($club_logotipo); ?>" 
                            alt="Logotipo actual" 
                            class="max-w-xs border border-gray-200 rounded p-1 fallback-img-preview">
                   </div>
               <?php endif; ?>
           </div>
           
           <div class="flex flex-wrap gap-4">
               <button type="submit" 
                       name="actualizar_club" 
                       class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 transition-all fallback-btn" style="background: var(--color-primario); color: white; padding: 8px 16px; border-radius: 8px;">
                   <?php echo $club_id ? 'Actualizar Datos' : 'Crear Club'; ?>
               </button>
               <a href="dashboard.php" 
                  class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:opacity-90 transition-all fallback-btn-secondary" style="background: #4b5563; color: white; padding: 8px 16px; border-radius: 8px; display: inline-block; text-decoration: none;">
                   Volver al Dashboard
               </a>
           </div>
       </form>
   </div>
</main>

<style>
/* Estilos adicionales para mejorar la responsividad */
@media (max-width: 640px) {
    .color-preview {
        width: 24px !important;
        height: 24px !important;
    }
    
    .flex-wrap {
        justify-content: center;
    }
    
    .fallback-img-preview {
        max-width: 100% !important;
    }
}
</style>
</body>
</html>
