<?php
session_start();
// Cargar configuración del club
require_once '../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();
$page_title = 'Gestionar Equipos - ' . $clubConfig->getNombre();

// Verificar que es administrador
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$equipos = [];
$entrenadores = [];
$temporadas = [];

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
        // Crear equipo
        if(isset($_POST['crear_equipo'])) {
            $nombre = strtoupper(trim($_POST['nombre'])); // Convertir a mayúsculas
            $categoria = trim($_POST['categoria']);
            $temporada = trim($_POST['temporada']);
            $entrenador_id = !empty($_POST['entrenador_id']) ? $_POST['entrenador_id'] : null;
            $activo = 1;
            
            // Verificar que no exista el equipo en la misma temporada
            $stmt = $conn->prepare("SELECT id FROM equipos WHERE nombre = ? AND categoria = ? AND temporada = ?");
            $stmt->bind_param("sss", $nombre, $categoria, $temporada);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensaje = 'Ya existe un equipo con ese nombre en esa categoría para la temporada ' . $temporada;
                $tipo_mensaje = 'error';
            } else {
                // Insertar equipo
                $stmt = $conn->prepare("INSERT INTO equipos (nombre, categoria, temporada, entrenador_id, activo) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $nombre, $categoria, $temporada, $entrenador_id, $activo);
                
                if($stmt->execute()) {
                    $mensaje = 'Equipo creado correctamente';
                    $tipo_mensaje = 'success';
                    // Limpiar formulario
                    $_POST = array();
                } else {
                    $mensaje = 'Error al crear el equipo: ' . $stmt->error;
                    $tipo_mensaje = 'error';
                }
            }
        }
        
        // Editar equipo
        if(isset($_POST['editar_equipo'])) {
            $equipo_id = $_POST['equipo_id'];
            $nombre = strtoupper(trim($_POST['nombre'])); // Convertir a mayúsculas
            $categoria = trim($_POST['categoria']);
            $temporada = trim($_POST['temporada']);
            $entrenador_id = !empty($_POST['entrenador_id']) ? $_POST['entrenador_id'] : null;
            $activo = $_POST['activo'];
            
            // Verificar que no exista otro equipo con el mismo nombre/categoría/temporada
            $stmt = $conn->prepare("SELECT id FROM equipos WHERE nombre = ? AND categoria = ? AND temporada = ? AND id != ?");
            $stmt->bind_param("sssi", $nombre, $categoria, $temporada, $equipo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensaje = 'Ya existe otro equipo con ese nombre en esa categoría para la temporada ' . $temporada;
                $tipo_mensaje = 'error';
            } else {
                // Actualizar equipo
                $stmt = $conn->prepare("UPDATE equipos SET nombre = ?, categoria = ?, temporada = ?, entrenador_id = ?, activo = ? WHERE id = ?");
                $stmt->bind_param("sssiii", $nombre, $categoria, $temporada, $entrenador_id, $activo, $equipo_id);
                
                if($stmt->execute()) {
                    $mensaje = 'Equipo actualizado correctamente';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al actualizar el equipo: ' . $stmt->error;
                    $tipo_mensaje = 'error';
                }
            }
        }
        
        // Cambiar estado de equipo
        if(isset($_POST['cambiar_estado'])) {
            $equipo_id = $_POST['equipo_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $stmt = $conn->prepare("UPDATE equipos SET activo = ? WHERE id = ?");
            $stmt->bind_param("ii", $nuevo_estado, $equipo_id);
            
            if($stmt->execute()) {
                $mensaje = 'Estado del equipo actualizado';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al cambiar el estado: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    }
    
    // Obtener todos los entrenadores activos
    $stmt = $conn->prepare("SELECT id, nombre, apellidos FROM entrenadores WHERE activo = 1 ORDER BY nombre, apellidos");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $entrenadores[] = $row;
    }
    
    // Obtener todas las temporadas disponibles
    $stmt = $conn->prepare("SELECT DISTINCT temporada FROM equipos ORDER BY temporada DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $temporadas[] = $row['temporada'];
    }
    
    // Filtro por temporada
    $temporada_filtro = isset($_GET['temporada']) ? $_GET['temporada'] : '';
    
    // Obtener todos los equipos con información del entrenador
    if ($temporada_filtro) {
        $stmt = $conn->prepare("
            SELECT e.*, 
                   CONCAT(en.nombre, ' ', en.apellidos) as entrenador_nombre 
            FROM equipos e 
            LEFT JOIN entrenadores en ON e.entrenador_id = en.id 
            WHERE e.temporada = ?
            ORDER BY e.temporada DESC, e.categoria, e.nombre
        ");
        $stmt->bind_param("s", $temporada_filtro);
    } else {
        $stmt = $conn->prepare("
            SELECT e.*, 
                   CONCAT(en.nombre, ' ', en.apellidos) as entrenador_nombre 
            FROM equipos e 
            LEFT JOIN entrenadores en ON e.entrenador_id = en.id 
            ORDER BY e.temporada DESC, e.categoria, e.nombre
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $equipos[] = $row;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $mensaje = 'Error del sistema: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    error_log("Error en gestionar_equipos.php: " . $e->getMessage());
}

// Categorías disponibles
$categorias = [
    'Prebenjamín',
    'Benjamín',
    'Alevín',
    'Infantil',
    'Cadete',
    'Juvenil',
    'Senior'
];

// Generar temporadas (actual y próximas)
$temporadas_disponibles = [];
$año_actual = date('Y');
for ($i = -1; $i <= 2; $i++) {
    $año_inicio = $año_actual + $i;
    $año_fin = $año_inicio + 1;
    $temporadas_disponibles[] = $año_inicio . '-' . $año_fin;
}
?>
<?php include '../includes/club_header.php'; ?>

<?php
$clubConfig->renderHeader(
    'Gestionar Equipos',
    'Administración de equipos del club',
    'Administrador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

<body class="bg-gray-100">
    <!-- Header -->
    

    <div class="container mx-auto mt-8 px-4 fallback-container">
        <?php if($mensaje): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 border-l-4 border-green-500 text-green-800 fallback-alert-success' : 'bg-red-50 border-l-4 border-red-500 text-red-800 fallback-alert-error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulario para crear equipo -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">Nuevo Equipo</h2>
            
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;">
                    <div class="fallback-form-group">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Nombre del Equipo
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-input" 
                               placeholder="Ej: WARRIORS"
                               style="text-transform: uppercase;"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Se convertirá automáticamente a mayúsculas</p>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Categoría
                        </label>
                        <select id="categoria" 
                                name="categoria" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-select" 
                                required>
                            <option value="">Seleccionar categoría</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo (isset($_POST['categoria']) && $_POST['categoria'] === $cat) ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="temporada" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Temporada
                        </label>
                        <select id="temporada" 
                                name="temporada" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-select" 
                                required>
                            <option value="">Seleccionar temporada</option>
                            <?php foreach($temporadas_disponibles as $temp): ?>
                                <option value="<?php echo $temp; ?>" <?php echo (isset($_POST['temporada']) && $_POST['temporada'] === $temp) ? 'selected' : ($temp === '2024-2025' ? 'selected' : ''); ?>>
                                    <?php echo $temp; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="entrenador_id" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Entrenador (Opcional)
                        </label>
                        <select id="entrenador_id" 
                                name="entrenador_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 fallback-select">
                            <option value="">Sin asignar</option>
                            <?php foreach($entrenadores as $entrenador): ?>
                                <option value="<?php echo $entrenador['id']; ?>" <?php echo (isset($_POST['entrenador_id']) && $_POST['entrenador_id'] == $entrenador['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($entrenador['nombre'] . ' ' . $entrenador['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" 
                            name="crear_equipo" 
                            class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-club-primario focus:ring-offset-2 transition-all fallback-btn">
                        Crear Equipo
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Filtros -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 fallback-filter">
            <div class="flex items-center space-x-4" style="display: flex; align-items: center; gap: 16px;">
                <label class="text-sm font-medium text-gray-700">Filtrar por temporada:</label>
                <select onchange="filtrarPorTemporada(this.value)" class="px-3 py-2 border border-gray-300 rounded-md text-sm fallback-select" style="width: auto; min-width: 150px;">
                    <option value="">Todas las temporadas</option>
                    <?php foreach($temporadas as $temp): ?>
                        <option value="<?php echo $temp; ?>" <?php echo ($temporada_filtro === $temp) ? 'selected' : ''; ?>>
                            <?php echo $temp; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if($temporada_filtro): ?>
                    <a href="gestionar_equipos.php" class="text-blue-600 hover:text-blue-800 text-sm">Limpiar filtro</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Lista de equipos -->
        <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">
                Equipos Registrados
                <?php if($temporada_filtro): ?>
                    <span class="text-sm font-normal text-gray-600">(Temporada: <?php echo htmlspecialchars($temporada_filtro); ?>)</span>
                <?php endif; ?>
            </h2>
            
            <div class="overflow-x-auto" style="overflow-x: auto;">
                <table class="w-full fallback-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Categoría
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Temporada
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Entrenador
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
                        <?php if(count($equipos) > 0): ?>
                            <?php foreach($equipos as $equipo): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap font-semibold">
                                        <?php echo htmlspecialchars($equipo['nombre']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($equipo['categoria']); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 fallback-badge-info">
                                            <?php echo htmlspecialchars($equipo['temporada']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php echo $equipo['entrenador_nombre'] ? htmlspecialchars($equipo['entrenador_nombre']) : 'Sin asignar'; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $equipo['activo'] ? 'bg-green-100 text-green-800 fallback-badge-success' : 'bg-red-100 text-red-800 fallback-badge-error'; ?>">
                                            <?php echo $equipo['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editarEquipo(<?php echo htmlspecialchars(json_encode($equipo)); ?>)" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            Editar
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                            <input type="hidden" name="nuevo_estado" value="<?php echo $equipo['activo'] ? 0 : 1; ?>">
                                            <button type="submit" name="cambiar_estado" 
                                                    class="text-white px-3 py-1 rounded-lg text-xs transition-all hover:opacity-90 <?php echo $equipo['activo'] ? 'bg-club-secundario fallback-btn-warning' : 'bg-club-acento fallback-btn-success'; ?>">
                                                <?php echo $equipo['activo'] ? 'Desactivar' : 'Activar'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                    <?php echo $temporada_filtro ? 'No hay equipos registrados para la temporada ' . htmlspecialchars($temporada_filtro) : 'No hay equipos registrados'; ?>
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
    <div id="modalEditar" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden fallback-modal">
        <div class="flex items-center justify-center min-h-screen p-4" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 16px;">
            <div class="bg-white rounded-lg p-6 w-full max-w-md fallback-modal-content">
                <h3 class="text-lg font-semibold mb-4" style="font-size: 1.125rem; font-weight: 600; margin-bottom: 16px;">Editar Equipo</h3>
                
                <form method="POST" id="formEditar">
                    <input type="hidden" name="equipo_id" id="edit_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Equipo</label>
                        <input type="text" name="nombre" id="edit_nombre" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-transform: uppercase;">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select name="categoria" id="edit_categoria" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Temporada</label>
                        <select name="temporada" id="edit_temporada" class="w-full px-3 py-2 border border-gray-300 rounded-md" required style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach($temporadas_disponibles as $temp): ?>
                                <option value="<?php echo $temp; ?>"><?php echo $temp; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Entrenador</label>
                        <select name="entrenador_id" id="edit_entrenador_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">Sin asignar</option>
                            <?php foreach($entrenadores as $entrenador): ?>
                                <option value="<?php echo $entrenador['id']; ?>">
                                    <?php echo htmlspecialchars($entrenador['nombre'] . ' ' . $entrenador['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                        <select name="activo" id="edit_activo" class="w-full px-3 py-2 border border-gray-300 rounded-md" style="width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3" style="display: flex; justify-content: flex-end; gap: 12px;">
                        <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:opacity-90 transition-all" style="padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 8px;">
                            Cancelar
                        </button>
                        <button type="submit" name="editar_equipo" class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 transition-all" style="padding: 8px 16px; background: var(--color-primario); color: white; border: none; border-radius: 8px;">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Convertir nombre a mayúsculas automáticamente
        document.getElementById('nombre').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        function editarEquipo(equipo) {
            document.getElementById('edit_id').value = equipo.id;
            document.getElementById('edit_nombre').value = equipo.nombre;
            document.getElementById('edit_categoria').value = equipo.categoria;
            document.getElementById('edit_temporada').value = equipo.temporada;
            document.getElementById('edit_entrenador_id').value = equipo.entrenador_id || '';
            document.getElementById('edit_activo').value = equipo.activo;
            
            document.getElementById('modalEditar').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
        }
        
        function filtrarPorTemporada(temporada) {
            if (temporada) {
                window.location.href = 'gestionar_equipos.php?temporada=' + encodeURIComponent(temporada);
            } else {
                window.location.href = 'gestionar_equipos.php';
            }
        }
        
        // Convertir nombre a mayúsculas en el modal también
        document.getElementById('edit_nombre').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('modalEditar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
    </script>
</body>
</html>
