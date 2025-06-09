<?php
session_start();

// Verificar que es administrador o entrenador
if(!isset($_SESSION['user_type']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'entrenador')) {
    header('Location: ../login.php');
    exit();
}

// Cargar configuración del club
require_once '../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();

$es_admin = $_SESSION['user_type'] === 'admin';
$page_title = ($es_admin ? 'Gestionar Jugadores' : 'Mis Jugadores') . ' - ' . $clubConfig->getNombre();

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$jugadores = [];
$equipos = [];

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
    
    // Verificar si existe la tabla jugadores
    $result = $conn->query("SHOW TABLES LIKE 'jugadores'");
    $tabla_jugadores_existe = $result->num_rows > 0;
    
    if (!$tabla_jugadores_existe) {
        // Crear tabla jugadores si no existe
        $sql = "CREATE TABLE IF NOT EXISTS `jugadores` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nombre` varchar(100) NOT NULL,
            `apellidos` varchar(100) NOT NULL,
            `fecha_nacimiento` date NOT NULL,
            `equipo_id` int(11) NOT NULL,
            `numero_camiseta` varchar(2) DEFAULT NULL,
            `posicion` varchar(50) DEFAULT NULL,
            `contacto_nombre` varchar(100) DEFAULT NULL COMMENT 'Nombre del contacto principal',
            `contacto_relacion` ENUM('Padre', 'Madre', 'Tutor', 'Otro') DEFAULT NULL COMMENT 'Relación del contacto con el jugador',
            `contacto_telefono` varchar(20) DEFAULT NULL COMMENT 'Teléfono del contacto',
            `activo` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `fk_equipo` (`equipo_id`),
            UNIQUE KEY `unique_numero_equipo` (`equipo_id`, `numero_camiseta`),
            CONSTRAINT `fk_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            $mensaje = 'Tabla de jugadores creada correctamente';
            $tipo_mensaje = 'success';
            $tabla_jugadores_existe = true;
        } else {
            throw new Exception("Error creando tabla jugadores: " . $conn->error);
        }
    }
    
    // Obtener equipos según el tipo de usuario
    if ($es_admin) {
        // Admin ve todos los equipos
        $stmt = $conn->prepare("SELECT e.id, e.nombre, e.categoria, e.temporada, CONCAT(en.nombre, ' ', en.apellidos) as entrenador_nombre FROM equipos e LEFT JOIN entrenadores en ON e.entrenador_id = en.id WHERE e.activo = 1 ORDER BY e.temporada DESC, e.categoria, e.nombre");
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $equipos[] = $row;
        }
    } else {
        // Entrenador solo ve sus equipos
        $entrenador_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT id, nombre, categoria, temporada FROM equipos WHERE entrenador_id = ? AND activo = 1 ORDER BY temporada DESC, categoria, nombre");
        $stmt->bind_param("i", $entrenador_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $equipos[] = $row;
        }
    }
    
    // Procesar formulario solo si la tabla existe
    if ($tabla_jugadores_existe && $_SERVER["REQUEST_METHOD"] == "POST") {
        // Crear jugador
        if(isset($_POST['crear_jugador'])) {
            // Convertir a mayúsculas
            $nombre = strtoupper(trim($_POST['nombre']));
            $apellidos = strtoupper(trim($_POST['apellidos']));
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $equipo_id = $_POST['equipo_id'];
            $numero_camiseta = !empty($_POST['numero_camiseta']) ? $_POST['numero_camiseta'] : null;
            $posicion = !empty($_POST['posicion']) ? strtoupper(trim($_POST['posicion'])) : null;
            $activo = 1;
            
            // Datos de contacto
            $contacto_nombre = strtoupper(trim($_POST['contacto_nombre']));
            $contacto_relacion = $_POST['contacto_relacion'];
            $contacto_telefono = trim($_POST['contacto_telefono']);
            
            // Verificar que el equipo es válido para el usuario
            $equipo_valido = false;
            foreach ($equipos as $equipo) {
                if ($equipo['id'] == $equipo_id) {
                    $equipo_valido = true;
                    break;
                }
            }
            
            if (!$equipo_valido) {
                $mensaje = 'No tienes permisos para añadir jugadores a ese equipo';
                $tipo_mensaje = 'error';
            } else {
                // Verificar que no exista el número de camiseta en el mismo equipo
                if ($numero_camiseta !== null) {
                    $stmt = $conn->prepare("SELECT id FROM jugadores WHERE equipo_id = ? AND numero_camiseta = ?");
                    $stmt->bind_param("is", $equipo_id, $numero_camiseta);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $mensaje = 'Ya existe un jugador con ese número de camiseta en el equipo';
                        $tipo_mensaje = 'error';
                    } else {
                        // Insertar jugador
                        $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellidos, fecha_nacimiento, equipo_id, numero_camiseta, posicion, contacto_nombre, contacto_relacion, contacto_telefono, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssississi", $nombre, $apellidos, $fecha_nacimiento, $equipo_id, $numero_camiseta, $posicion, $contacto_nombre, $contacto_relacion, $contacto_telefono, $activo);
                        
                        if($stmt->execute()) {
                            $mensaje = 'Jugador creado correctamente';
                            $tipo_mensaje = 'success';
                            $_POST = array();
                        } else {
                            $mensaje = 'Error al crear el jugador: ' . $stmt->error;
                            $tipo_mensaje = 'error';
                        }
                    }
                } else {
                    // Insertar jugador sin número
                    $stmt = $conn->prepare("INSERT INTO jugadores (nombre, apellidos, fecha_nacimiento, equipo_id, posicion, contacto_nombre, contacto_relacion, contacto_telefono, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssisssi", $nombre, $apellidos, $fecha_nacimiento, $equipo_id, $posicion, $contacto_nombre, $contacto_relacion, $contacto_telefono, $activo);
                    
                    if($stmt->execute()) {
                        $mensaje = 'Jugador creado correctamente';
                        $tipo_mensaje = 'success';
                        $_POST = array();
                    } else {
                        $mensaje = 'Error al crear el jugador: ' . $stmt->error;
                        $tipo_mensaje = 'error';
                    }
                }
            }
        }
        
        // Editar jugador
        if(isset($_POST['editar_jugador'])) {
            $jugador_id = $_POST['jugador_id'];
            $nombre = strtoupper(trim($_POST['nombre']));
            $apellidos = strtoupper(trim($_POST['apellidos']));
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $equipo_id = $_POST['equipo_id'];
            $numero_camiseta = !empty($_POST['numero_camiseta']) ? $_POST['numero_camiseta'] : null;
            $posicion = !empty($_POST['posicion']) ? strtoupper(trim($_POST['posicion'])) : null;
            $contacto_nombre = strtoupper(trim($_POST['contacto_nombre']));
            $contacto_relacion = $_POST['contacto_relacion'];
            $contacto_telefono = trim($_POST['contacto_telefono']);
            
            // Verificar que el equipo es válido para el usuario
            $equipo_valido = false;
            foreach ($equipos as $equipo) {
                if ($equipo['id'] == $equipo_id) {
                    $equipo_valido = true;
                    break;
                }
            }
            
            if (!$equipo_valido) {
                $mensaje = 'No tienes permisos para modificar jugadores de ese equipo';
                $tipo_mensaje = 'error';
            } else {
                // Verificar que no exista el número de camiseta en el mismo equipo (excepto el jugador actual)
                if ($numero_camiseta !== null) {
                    $stmt = $conn->prepare("SELECT id FROM jugadores WHERE equipo_id = ? AND numero_camiseta = ? AND id != ?");
                    $stmt->bind_param("isi", $equipo_id, $numero_camiseta, $jugador_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $mensaje = 'Ya existe un jugador con ese número de camiseta en el equipo';
                        $tipo_mensaje = 'error';
                    } else {
                        // Actualizar jugador
                        $stmt = $conn->prepare("UPDATE jugadores SET nombre = ?, apellidos = ?, fecha_nacimiento = ?, equipo_id = ?, numero_camiseta = ?, posicion = ?, contacto_nombre = ?, contacto_relacion = ?, contacto_telefono = ? WHERE id = ?");
                        $stmt->bind_param("sssississi", $nombre, $apellidos, $fecha_nacimiento, $equipo_id, $numero_camiseta, $posicion, $contacto_nombre, $contacto_relacion, $contacto_telefono, $jugador_id);
                        
                        if ($stmt->execute()) {
                            $mensaje = 'Jugador actualizado correctamente';
                            $tipo_mensaje = 'success';
                        } else {
                            $mensaje = 'Error al actualizar el jugador: ' . $stmt->error;
                            $tipo_mensaje = 'error';
                        }
                    }
                } else {
                    // Actualizar jugador sin número
                    $stmt = $conn->prepare("UPDATE jugadores SET nombre = ?, apellidos = ?, fecha_nacimiento = ?, equipo_id = ?, numero_camiseta = NULL, posicion = ?, contacto_nombre = ?, contacto_relacion = ?, contacto_telefono = ? WHERE id = ?");
                    $stmt->bind_param("ssssssssi", $nombre, $apellidos, $fecha_nacimiento, $equipo_id, $posicion, $contacto_nombre, $contacto_relacion, $contacto_telefono, $jugador_id);
                    
                    if ($stmt->execute()) {
                        $mensaje = 'Jugador actualizado correctamente';
                        $tipo_mensaje = 'success';
                    } else {
                        $mensaje = 'Error al actualizar el jugador: ' . $stmt->error;
                        $tipo_mensaje = 'error';
                    }
                }
            }
        }
        
        // Cambiar estado de jugador (solo admin)
        if(isset($_POST['cambiar_estado']) && $es_admin) {
            $jugador_id = $_POST['jugador_id'];
            $nuevo_estado = $_POST['nuevo_estado'];
            
            $stmt = $conn->prepare("UPDATE jugadores SET activo = ? WHERE id = ?");
            $stmt->bind_param("ii", $nuevo_estado, $jugador_id);
            
            if($stmt->execute()) {
                $mensaje = 'Estado del jugador actualizado';
                $tipo_mensaje = 'success';
            } else {
                $mensaje = 'Error al cambiar el estado: ' . $stmt->error;
                $tipo_mensaje = 'error';
            }
        }
    }
    
    // Obtener jugadores según el tipo de usuario
    if ($tabla_jugadores_existe && count($equipos) > 0) {
        $filtro_equipo = isset($_GET['equipo_id']) ? $_GET['equipo_id'] : '';
        
        if ($es_admin) {
            // Admin ve todos los jugadores
            if ($filtro_equipo) {
                $stmt = $conn->prepare("
                    SELECT j.*, e.nombre as equipo_nombre, e.categoria, e.temporada 
                    FROM jugadores j 
                    INNER JOIN equipos e ON j.equipo_id = e.id 
                    WHERE j.equipo_id = ?
                    ORDER BY j.numero_camiseta ASC, j.apellidos, j.nombre
                ");
                $stmt->bind_param("i", $filtro_equipo);
            } else {
                $stmt = $conn->prepare("
                    SELECT j.*, e.nombre as equipo_nombre, e.categoria, e.temporada 
                    FROM jugadores j 
                    INNER JOIN equipos e ON j.equipo_id = e.id 
                    ORDER BY e.temporada DESC, e.categoria, e.nombre, j.numero_camiseta ASC, j.apellidos, j.nombre
                ");
            }
        } else {
            // Entrenador solo ve jugadores de sus equipos
            $equipos_ids = array_column($equipos, 'id');
            $placeholders = str_repeat('?,', count($equipos_ids) - 1) . '?';
            
            if ($filtro_equipo && in_array($filtro_equipo, $equipos_ids)) {
                $stmt = $conn->prepare("
                    SELECT j.*, e.nombre as equipo_nombre, e.categoria, e.temporada 
                    FROM jugadores j 
                    INNER JOIN equipos e ON j.equipo_id = e.id 
                    WHERE j.equipo_id = ?
                    ORDER BY j.numero_camiseta ASC, j.apellidos, j.nombre
                ");
                $stmt->bind_param("i", $filtro_equipo);
            } else {
                $stmt = $conn->prepare("
                    SELECT j.*, e.nombre as equipo_nombre, e.categoria, e.temporada 
                    FROM jugadores j 
                    INNER JOIN equipos e ON j.equipo_id = e.id 
                    WHERE j.equipo_id IN ($placeholders)
                    ORDER BY e.temporada DESC, e.categoria, e.nombre, j.numero_camiseta ASC, j.apellidos, j.nombre
                ");
                $stmt->bind_param(str_repeat('i', count($equipos_ids)), ...$equipos_ids);
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $jugadores[] = $row;
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $mensaje = 'Error del sistema: ' . $e->getMessage();
    $tipo_mensaje = 'error';
    error_log("Error en gestionar_jugadores.php: " . $e->getMessage());
}

// Posiciones disponibles
$posiciones = [
    'BASE',
    'ESCOLTA',
    'ALERO',
    'ALA-PÍVOT',
    'PÍVOT'
];

// Buscar jugador para editar
$jugador_editar = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $jugador_id_editar = $_GET['edit'];
    foreach ($jugadores as $jugador) {
        if ($jugador['id'] == $jugador_id_editar) {
            $jugador_editar = $jugador;
            break;
        }
    }
}
?>
<?php include '../includes/club_header.php'; ?>

<?php
// Renderizar header
$clubConfig->renderHeader(
    $es_admin ? 'Gestionar Jugadores' : 'Mis Jugadores',
    $es_admin ? 'Administración de jugadores del club' : 'Gestión de jugadores de tus equipos',
    ($es_admin ? 'Administrador: ' : 'Entrenador: ') . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

&lt;!-- Main Content -->
<main class="container mx-auto px-4 py-8 fallback-container">
    <?php if($mensaje): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $tipo_mensaje === 'success' ? 'bg-green-50 border-l-4 border-club-acento text-green-800 fallback-alert-success' : 'bg-red-50 border-l-4 border-red-500 text-red-800 fallback-alert-error'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$tabla_jugadores_existe): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-yellow-800">Tabla de jugadores no encontrada</h3>
                    <p class="text-yellow-700">La tabla de jugadores se creará automáticamente cuando añadas el primer jugador.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (count($equipos) == 0): ?>
        <div class="bg-blue-50 border-l-4 border-club-primario p-4 mb-8">
            <div class="flex">
                <svg class="w-5 h-5 text-club-primario mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h3 class="text-lg font-medium text-blue-800">
                        <?php echo $es_admin ? 'No hay equipos disponibles' : 'No tienes equipos asignados'; ?>
                    </h3>
                    <p class="text-blue-700">
                        <?php echo $es_admin ? 'Crea equipos primero para poder añadir jugadores.' : 'Contacta con el administrador para que te asigne equipos y puedas gestionar jugadores.'; ?>
                    </p>
                </div>
            </div>
        </div>
    <?php else: ?>
        &lt;!-- Formulario para crear/editar jugador -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">
                <?php echo $jugador_editar ? 'Editar Jugador' : 'Nuevo Jugador'; ?>
            </h2>
            
            <form method="POST">
                <?php if ($jugador_editar): ?>
                    <input type="hidden" name="jugador_id" value="<?php echo htmlspecialchars($jugador_editar['id']); ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 fallback-grid" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="fallback-form-group">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Nombre *
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['nombre']) : (isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input uppercase" 
                               style="text-transform: uppercase;"
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="apellidos" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Apellidos *
                        </label>
                        <input type="text" 
                               id="apellidos" 
                               name="apellidos" 
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['apellidos']) : (isset($_POST['apellidos']) ? htmlspecialchars($_POST['apellidos']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input uppercase" 
                               style="text-transform: uppercase;"
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Fecha de Nacimiento *
                        </label>
                        <input type="date" 
                               id="fecha_nacimiento" 
                               name="fecha_nacimiento" 
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['fecha_nacimiento']) : (isset($_POST['fecha_nacimiento']) ? htmlspecialchars($_POST['fecha_nacimiento']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input" 
                               required>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="equipo_id" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Equipo *
                        </label>
                        <select id="equipo_id" 
                                name="equipo_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-select" 
                                required>
                            <option value="">Seleccionar equipo</option>
                            <?php foreach($equipos as $equipo): ?>
                                <option value="<?php echo $equipo['id']; ?>" 
                                    <?php echo ($jugador_editar && $jugador_editar['equipo_id'] == $equipo['id']) || (isset($_POST['equipo_id']) && $_POST['equipo_id'] == $equipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($equipo['nombre'] . ' - ' . $equipo['categoria'] . ' (' . $equipo['temporada'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="numero_camiseta" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Número de Camiseta (00-99)
                        </label>
                        <input type="text" 
                               id="numero_camiseta" 
                               name="numero_camiseta" 
                               pattern="[0-9]{1,2}"
                               maxlength="2"
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['numero_camiseta']) : (isset($_POST['numero_camiseta']) ? htmlspecialchars($_POST['numero_camiseta']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input"
                               placeholder="00-99">
                        <p class="text-xs text-gray-500 mt-1">Introduce un número entre 00 y 99</p>
                    </div>
                    
                    <div class="fallback-form-group">
                        <label for="posicion" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Posición
                        </label>
                        <select id="posicion" 
                                name="posicion" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-select">
                            <option value="">Seleccionar posición</option>
                            <?php foreach($posiciones as $pos): ?>
                                <option value="<?php echo $pos; ?>" 
                                    <?php echo ($jugador_editar && $jugador_editar['posicion'] === $pos) || (isset($_POST['posicion']) && $_POST['posicion'] === $pos) ? 'selected' : ''; ?>>
                                    <?php echo $pos; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="fallback-form-group">
                        <label for="contacto_nombre" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Nombre del Contacto
                        </label>
                        <input type="text" 
                               id="contacto_nombre" 
                               name="contacto_nombre" 
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['contacto_nombre']) : (isset($_POST['contacto_nombre']) ? htmlspecialchars($_POST['contacto_nombre']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input uppercase" 
                               style="text-transform: uppercase;"
                               placeholder="Ej: MARÍA GARCÍA PÉREZ">
                    </div>

                    <div class="fallback-form-group">
                        <label for="contacto_relacion" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Relación
                        </label>
                        <select id="contacto_relacion" 
                                name="contacto_relacion" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-select">
                            <option value="">Seleccionar relación</option>
                            <option value="Padre" <?php echo ($jugador_editar && $jugador_editar['contacto_relacion'] === 'Padre') || (isset($_POST['contacto_relacion']) && $_POST['contacto_relacion'] === 'Padre') ? 'selected' : ''; ?>>Padre</option>
                            <option value="Madre" <?php echo ($jugador_editar && $jugador_editar['contacto_relacion'] === 'Madre') || (isset($_POST['contacto_relacion']) && $_POST['contacto_relacion'] === 'Madre') ? 'selected' : ''; ?>>Madre</option>
                            <option value="Tutor" <?php echo ($jugador_editar && $jugador_editar['contacto_relacion'] === 'Tutor') || (isset($_POST['contacto_relacion']) && $_POST['contacto_relacion'] === 'Tutor') ? 'selected' : ''; ?>>Tutor</option>
                            <option value="Otro" <?php echo ($jugador_editar && $jugador_editar['contacto_relacion'] === 'Otro') || (isset($_POST['contacto_relacion']) && $_POST['contacto_relacion'] === 'Otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>

                    <div class="fallback-form-group">
                        <label for="contacto_telefono" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                            Teléfono de Contacto
                        </label>
                        <input type="tel" 
                               id="contacto_telefono" 
                               name="contacto_telefono" 
                               value="<?php echo $jugador_editar ? htmlspecialchars($jugador_editar['contacto_telefono']) : (isset($_POST['contacto_telefono']) ? htmlspecialchars($_POST['contacto_telefono']) : ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input" 
                               placeholder="Ej: 666 123 456">
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" 
                            name="<?php echo $jugador_editar ? 'editar_jugador' : 'crear_jugador'; ?>" 
                            class="px-4 py-2 bg-club-primario text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-club-primario focus:ring-offset-2 transition-all fallback-btn">
                        <?php echo $jugador_editar ? 'Guardar Cambios' : 'Crear Jugador'; ?>
                    </button>
                    <?php if ($jugador_editar): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo isset($_GET['equipo_id']) ? '?equipo_id=' . $_GET['equipo_id'] : ''; ?>" 
                           class="ml-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all fallback-btn-secondary">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        &lt;!-- Filtros -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 fallback-filter">
            <div class="flex items-center space-x-4" style="display: flex; align-items: center; gap: 16px;">
                <label class="text-sm font-medium text-gray-700">Filtrar por equipo:</label>
                <select onchange="filtrarPorEquipo(this.value)" class="px-3 py-2 border border-gray-300 rounded-md text-sm fallback-select" style="width: auto; min-width: 200px;">
                    <option value="">Todos los equipos</option>
                    <?php foreach($equipos as $equipo): ?>
                        <option value="<?php echo $equipo['id']; ?>" <?php echo (isset($_GET['equipo_id']) && $_GET['equipo_id'] == $equipo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($equipo['nombre'] . ' - ' . $equipo['categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(isset($_GET['equipo_id'])): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="text-club-primario hover:opacity-80 text-sm">Limpiar filtro</a>
                <?php endif; ?>
            </div>
        </div>
        
        &lt;!-- Lista de jugadores -->
        <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
            <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">
                <?php echo $es_admin ? 'Jugadores Registrados' : 'Mis Jugadores'; ?>
                <?php if(isset($_GET['equipo_id']) && !empty($_GET['equipo_id'])): ?>
                    <?php 
                    $equipo_filtrado = array_filter($equipos, function($e) { return $e['id'] == $_GET['equipo_id']; });
                    $equipo_filtrado = reset($equipo_filtrado);
                    ?>
                    <span class="text-sm font-normal text-gray-600">(Equipo: <?php echo htmlspecialchars($equipo_filtrado['nombre'] ?? 'Desconocido'); ?>)</span>
                <?php endif; ?>
            </h2>
            
            <div class="overflow-x-auto" style="overflow-x: auto;">
                <table class="w-full fallback-table">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Edad
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Equipo
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Posición
                            </th>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contacto
                            </th>
                            <?php if ($es_admin): ?>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <?php endif; ?>
                            <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(count($jugadores) > 0): ?>
                            <?php foreach($jugadores as $jugador): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <?php if($jugador['numero_camiseta']): ?>
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-club-primario text-white rounded-full text-sm font-bold">
                                                <?php echo htmlspecialchars($jugador['numero_camiseta']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            <?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php 
                                        $fecha_nac = new DateTime($jugador['fecha_nacimiento']);
                                        $hoy = new DateTime();
                                        $edad = $hoy->diff($fecha_nac)->y;
                                        echo $edad . ' años';
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($jugador['equipo_nombre']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($jugador['categoria']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $jugador['posicion'] ? htmlspecialchars($jugador['posicion']) : '-'; ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <?php if($jugador['contacto_nombre']): ?>
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($jugador['contacto_nombre']); ?>
                                                </div>
                                                <?php if($jugador['contacto_relacion']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        <?php echo htmlspecialchars($jugador['contacto_relacion']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if($jugador['contacto_telefono']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        <?php echo htmlspecialchars($jugador['contacto_telefono']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">Sin contacto</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($es_admin): ?>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $jugador['activo'] ? 'bg-green-100 text-green-800 fallback-badge-success' : 'bg-red-100 text-red-800 fallback-badge-error'; ?>">
                                            <?php echo $jugador['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <?php endif; ?>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?edit=<?php echo $jugador['id']; ?><?php echo isset($_GET['equipo_id']) ? '&equipo_id=' . $_GET['equipo_id'] : ''; ?>" 
                                           class="text-club-primario hover:text-club-secundario mr-3">Editar</a>
                                        <?php if ($es_admin): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="jugador_id" value="<?php echo $jugador['id']; ?>">
                                                <input type="hidden" name="nuevo_estado" value="<?php echo $jugador['activo'] ? 0 : 1; ?>">
                                                <button type="submit" name="cambiar_estado" 
                                                        class="text-white px-3 py-1 rounded-lg text-xs transition-all hover:opacity-90 <?php echo $jugador['activo'] ? 'bg-club-secundario fallback-btn-warning' : 'bg-club-acento fallback-btn-success'; ?>">
                                                    <?php echo $jugador['activo'] ? 'Desactivar' : 'Activar'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $es_admin ? '8' : '7'; ?>" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z"/>
                                    </svg>
                                    <?php if(isset($_GET['equipo_id']) && !empty($_GET['equipo_id'])): ?>
                                        No hay jugadores registrados en este equipo
                                    <?php else: ?>
                                        <?php echo $es_admin ? 'No hay jugadores registrados' : 'No hay jugadores registrados en tus equipos'; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mt-6 flex space-x-4" style="margin-top: 24px; display: flex; gap: 16px;">
        <a href="<?php echo $es_admin ? '../admin/dashboard.php' : '../entrenador/dashboard.php'; ?>" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all fallback-btn-secondary">
            Volver al Dashboard
        </a>
        <?php if ($es_admin): ?>
            <a href="gestionar_equipos.php" 
               class="px-4 py-2 bg-club-secundario text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-club-secundario focus:ring-offset-2 transition-all fallback-btn">
                Gestionar Equipos
            </a>
        <?php endif; ?>
    </div>
</main>

<script>
    // Convertir texto a mayúsculas
    document.addEventListener('DOMContentLoaded', function() {
        const uppercaseInputs = document.querySelectorAll('.uppercase');
        uppercaseInputs.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
        
        // Validar número de camiseta (00-99)
        const numeroCamiseta = document.getElementById('numero_camiseta');
        if (numeroCamiseta) {
            numeroCamiseta.addEventListener('input', function() {
                // Eliminar caracteres no numéricos
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Limitar a 2 dígitos
                if (this.value.length > 2) {
                    this.value = this.value.slice(0, 2);
                }
                
                // Asegurar que está entre 0 y 99
                const num = parseInt(this.value, 10);
                if (!isNaN(num) && (num < 0 || num > 99)) {
                    this.value = '';
                }
            });
        }
    });

    function filtrarPorEquipo(equipoId) {
        const currentUrl = window.location.pathname;
        if (equipoId) {
            window.location.href = currentUrl + '?equipo_id=' + encodeURIComponent(equipoId);
        } else {
            window.location.href = currentUrl;
        }
    }
</script>
</body>
</html>
