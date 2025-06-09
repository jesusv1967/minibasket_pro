<?php
session_start();
// Cargar configuración del club
require_once '../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();
$page_title = 'Gestionar Entrenadores - ' . $clubConfig->getNombre();
// Verificar que es administrador
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$entrenadores = [];

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
        // Crear entrenador
        if(isset($_POST['crear_entrenador'])) {
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            $usuario = trim($_POST['usuario']);
            $password = trim($_POST['password']);
            $activo = 1;
            
            // Validar que no exista el usuario
            $stmt = $conn->prepare("SELECT id FROM entrenadores WHERE usuario = ? OR email = ?");
            $stmt->bind_param("ss", $usuario, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensaje = 'El usuario o email ya existe';
                $tipo_mensaje = 'error';
            } else {
                // Hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar entrenador
                $stmt = $conn->prepare("INSERT INTO entrenadores (nombre, apellidos, email, telefono, usuario, password, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssi", $nombre, $apellidos, $email, $telefono, $usuario, $password_hash, $activo);
                
                if($stmt->execute()) {
                    $mensaje = 'Entrenador creado correctamente';
                    $tipo_mensaje = 'success';
                    // Limpiar formulario
                    $_POST = array();
                } else {
                    $mensaje = 'Error al crear el entrenador: ' . $stmt->error;
                    $tipo_mensaje = 'error';
                }
            }
        }
        
        // Cambiar estado de entrenador
        if(isset($_POST['cambiar_estado'])) {
            $entrenador_id = $_POST['entrenador_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $stmt = $conn->prepare("UPDATE entrenadores SET activo = ? WHERE id = ?");
            $stmt->bind_param("ii", $nuevo_estado, $entrenador_id);
            
            if($stmt->execute()) {
                $mensaje = 'Estado del entrenador actualizado';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al cambiar el estado: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
        // Editar entrenador
        if(isset($_POST['editar_entrenador'])) {
            $entrenador_id = $_POST['entrenador_id'];
            $nombre = trim($_POST['nombre']);
            $apellidos = trim($_POST['apellidos']);
            $email = trim($_POST['email']);
            $telefono = trim($_POST['telefono']);
            $usuario = trim($_POST['usuario']);
            $password = trim($_POST['password']);
            
            // Verificar que no exista el usuario/email en otros entrenadores
            $stmt = $conn->prepare("SELECT id FROM entrenadores WHERE (usuario = ? OR email = ?) AND id != ?");
            $stmt->bind_param("ssi", $usuario, $email, $entrenador_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensaje = 'El usuario o email ya existe en otro entrenador';
                $tipo_mensaje = 'error';
            } else {
                // Actualizar entrenador
                if (!empty($password)) {
                    // Si se proporciona nueva contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE entrenadores SET nombre = ?, apellidos = ?, email = ?, telefono = ?, usuario = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("ssssssi", $nombre, $apellidos, $email, $telefono, $usuario, $password_hash, $entrenador_id);
                } else {
                    // Sin cambiar contraseña
                    $stmt = $conn->prepare("UPDATE entrenadores SET nombre = ?, apellidos = ?, email = ?, telefono = ?, usuario = ? WHERE id = ?");
                    $stmt->bind_param("sssssi", $nombre, $apellidos, $email, $telefono, $usuario, $entrenador_id);
                }
                
                if($stmt->execute()) {
                    $mensaje = 'Entrenador actualizado correctamente';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al actualizar el entrenador: ' . $stmt->error;
                    $tipo_mensaje = 'error';
                }
            }
        }
    }
    
    // Obtener todos los entrenadores
    $stmt = $conn->prepare("SELECT id, nombre, apellidos, email, telefono, usuario, activo FROM entrenadores ORDER BY nombre, apellidos");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $entrenadores[] = $row;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $mensaje = 'Error del sistema: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    error_log("Error en gestionar_entrenadores.php: " . $e->getMessage());
}
?>
<?php include '../includes/club_header.php'; ?>

<?php
// Renderizar header
$clubConfig->renderHeader(
    'Gestionar Entrenadores',
    'Administración de entrenadores del club',
    'Administrador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

    <div class="container mx-auto mt-8 px-4 fallback-container">
        <?php if($mensaje): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800 fallback-alert-success' : 'bg-red-50 border-l-4 border-red-500 text-red-800 fallback-alert-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario para crear entrenador -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">Nuevo Entrenador</h2>
            
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px;">
                    <div class="fallback-form-group">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Nombre
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Apellidos
                        </label>
                        <input type="text" 
                               id="apellidos" 
                               name="apellidos" 
                               value="<?php echo isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Teléfono
                        </label>
                        <input type="tel" 
                               id="telefono" 
                               name="telefono" 
                               value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input">
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="usuario" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Usuario
                        </label>
                        <input type="text" 
                               id="usuario" 
                               name="usuario" 
                               value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Contraseña
                        </label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               required>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" 
                            name="crear_entrenador" 
                            class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-club-primario focus:ring-offset-2 transition-all fallback-btn">
                        Crear Entrenador
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Lista de entrenadores -->
        <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">Entrenadores Registrados</h2>
            
            <div class="overflow-x-auto" style="overflow-x: auto;">
                <table class="w-full fallback-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Teléfono
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usuario
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(count($entrenadores) > 0): ?>
                            <?php foreach($entrenadores as $entrenador): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($entrenador['nombre'] . ' ' . $entrenador['apellidos']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($entrenador['email']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($entrenador['telefono']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($entrenador['usuario']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $entrenador['activo'] ? 'bg-green-100 text-green-800 fallback-badge-success' : 'bg-red-100 text-red-800 fallback-badge-error'; ?>">
                                            <?php echo $entrenador['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editarEntrenador(<?php echo htmlspecialchars(json_encode($entrenador)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="entrenador_id" value="<?php echo $entrenador['id']; ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?php echo $entrenador['activo'] ? 0 : 1; ?>">
                                            <button type="submit" name="cambiar_estado" 
                                                    class="text-white px-3 py-1 rounded-lg text-xs transition-all hover:opacity-90 <?php echo $entrenador['activo'] ? 'bg-club-secundario fallback-btn-warning' : 'bg-club-acento fallback-btn-success'; ?>">
                                                <?php echo $entrenador['activo'] ? 'Desactivar' : 'Activar'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                    No hay entrenadores registrados
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6">
                <a href="dashboard.php" 
                   class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all fallback-btn-secondary">
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
    <!-- Modal de edición -->
    <div id="modalEditar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; z-index: 1000;">
        <div class="flex items-center justify-center min-h-screen p-4" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 16px;">
            <div class="bg-white rounded-lg p-6 w-full max-w-md" style="background: white; border-radius: 8px; padding: 24px; width: 100%; max-width: 500px;">
                <h3 class="text-lg font-semibold mb-4" style="font-size: 1.125rem; font-weight: 600; margin-bottom: 16px;">Editar Entrenador</h3>
                
                <form method="POST" id="formEditar">
                    <input type="hidden" name="entrenador_id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                        <input type="text" name="nombre" id="edit_nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Apellidos</label>
                        <input type="text" name="apellidos" id="edit_apellidos" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                        <input type="tel" name="telefono" id="edit_telefono" class="w-full px-3 py-2 border border-gray-300 rounded-md" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Usuario</label>
                        <input type="text" name="usuario" id="edit_usuario" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" name="password" id="edit_password" class="w-full px-3 py-2 border border-gray-300 rounded-md" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    
                    <div class="flex justify-end space-x-3" style="display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:opacity-90 transition-all" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 8px;">
                            Cancelar
                        </button>
                        <button type="submit" name="editar_entrenador" class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 transition-all" style="padding: 8px 16px; background: var(--color-primario); color: white; border: none; border-radius: 8px;">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editarEntrenador(entrenador) {
            document.getElementById('edit_id').value = entrenador.id;
            document.getElementById('edit_nombre').value = entrenador.nombre;
            document.getElementById('edit_apellidos').value = entrenador.apellidos;
            document.getElementById('edit_email').value = entrenador.email;
            document.getElementById('edit_telefono').value = entrenador.telefono || '';
            document.getElementById('edit_usuario').value = entrenador.usuario;
            document.getElementById('edit_password').value = '';
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEditar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
<style>
    <?php echo $clubConfig->generateCSS(); ?>
    body { font-family: 'Inter', sans-serif; }
        
        /* Fallback CSS en caso de que Tailwind no cargue */
        .fallback-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .fallback-header { background: linear-gradient(to right, #f97316, #2563eb); color: white; padding: 20px; margin-bottom: 20px; }
        .fallback-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .fallback-btn { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 4px; text-decoration: none; display: inline-block; margin-right: 10px; }
        .fallback-btn-secondary { background: #6b7280; color: white; padding: 10px 20px; border: none; border-radius: 4px; text-decoration: none; display: inline-block; }
        .fallback-btn-success { background: #16a34a; color: white; padding: 5px 10px; border: none; border-radius: 4px; font-size: 0.875rem; }
        .fallback-btn-warning { background: #f59e0b; color: white; padding: 5px 10px; border: none; border-radius: 4px; font-size: 0.875rem; }
        .fallback-alert-success { background: #dcfce7; border-left: 4px solid #16a34a; color: #166534; padding: 15px; margin-bottom: 20px; }
        .fallback-alert-error { background: #fee2e2; border-left: 4px solid #dc2626; color: #991b1b; padding: 15px; margin-bottom: 20px; }
        .fallback-form-group { margin-bottom: 20px; }
        .fallback-label { display: block; font-weight: 500; margin-bottom: 5px; }
        .fallback-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .fallback-table { width: 100%; border-collapse: collapse; }
        .fallback-table th { background: #f3f4f6; padding: 10px; text-align: left; font-weight: 600; }
        .fallback-table td { padding: 10px; border-top: 1px solid #e5e7eb; }
        .fallback-badge-success { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; }
        .fallback-badge-error { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; }
    /* resto del CSS existente */
</style>
