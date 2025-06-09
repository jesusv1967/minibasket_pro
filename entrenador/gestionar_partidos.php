<?php
session_start();
require_once '../config/database.php';
require_once '../models/Equipo.php';
require_once '../utils/ClubConfig.php';

// Verificar que el usuario esté logueado y sea entrenador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entrenador') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$clubConfig = ClubConfig::getInstance();

// Obtener equipos del entrenador
$equipo = new Equipo();
$equipos_stmt = $equipo->obtenerPorEntrenador($_SESSION['user_id']);
$equipos = $equipos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario de nuevo partido
if ($_POST && isset($_POST['crear_partido'])) {
    try {
        // Insertar partido
        $query = "INSERT INTO partidos (equipo_local_id, equipo_visitante, fecha, periodos, reglas_pasarela) 
                 VALUES (:equipo_local_id, :equipo_visitante, :fecha, :periodos, :reglas_pasarela)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':equipo_local_id', $_POST['equipo_id']);
        $stmt->bindParam(':equipo_visitante', $_POST['equipo_visitante']);
        $stmt->bindParam(':fecha', $_POST['fecha']);
        $stmt->bindParam(':periodos', $_POST['periodos']);
        
        $reglas_pasarela = ($_POST['periodos'] == 6 && isset($_POST['reglas_pasarela'])) ? 1 : 0;
        $stmt->bindParam(':reglas_pasarela', $reglas_pasarela);
        
        $stmt->execute();
        $partido_id = $conn->lastInsertId();
        
        // Insertar jugadores seleccionados
        if (isset($_POST['jugadores']) && is_array($_POST['jugadores'])) {
            $query_jugadores = "INSERT INTO partido_jugadores (partido_id, jugador_id, presente) VALUES (:partido_id, :jugador_id, 1)";
            $stmt_jugadores = $conn->prepare($query_jugadores);
            
            foreach ($_POST['jugadores'] as $jugador_id) {
                $stmt_jugadores->bindParam(':partido_id', $partido_id);
                $stmt_jugadores->bindParam(':jugador_id', $jugador_id);
                $stmt_jugadores->execute();
            }
        }
        
        $mensaje_exito = "Partido creado exitosamente";
        
    } catch(PDOException $e) {
        $mensaje_error = "Error al crear el partido: " . $e->getMessage();
    }
}

// Obtener partidos del entrenador
$query_partidos = "SELECT p.*, e.nombre as equipo_nombre, e.categoria 
                  FROM partidos p 
                  JOIN equipos e ON p.equipo_local_id = e.id 
                  WHERE e.entrenador_id = :entrenador_id 
                  ORDER BY p.fecha DESC";
$stmt_partidos = $conn->prepare($query_partidos);
$stmt_partidos->bindParam(':entrenador_id', $_SESSION['user_id']);
$stmt_partidos->execute();
$partidos = $stmt_partidos->fetchAll(PDO::FETCH_ASSOC);

// Variables para el header
$page_title = "Gestionar Partidos - " . $clubConfig->getNombre();
?>

<?php include '../includes/club_header.php'; ?>

<?php
// Renderizar header igual que en la página de jugadores
$clubConfig->renderHeader(
    'Gestionar Partidos',
    'Administración de partidos del equipo',
    'Entrenador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8 fallback-container">
    <?php if(isset($mensaje_exito)): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4 border-club-acento text-green-800 fallback-alert-success">
            ✅ <?php echo htmlspecialchars($mensaje_exito); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($mensaje_error)): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-800 fallback-alert-error">
            ❌ <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Formulario para crear nuevo partido -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8 fallback-card">
        <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">
            📝 Crear Nuevo Partido
        </h2>
        
        <form method="POST" id="partidoForm">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 fallback-grid" style="grid-template-columns: repeat(4, 1fr);">
                <div class="fallback-form-group">
                    <label for="equipo_id" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                        🏀 Mi Equipo *
                    </label>
                    <select id="equipo_id" 
                            name="equipo_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input" 
                            required onchange="cargarJugadores()">
                        <option value="">Seleccionar equipo...</option>
                        <?php foreach($equipos as $equipo_item): ?>
                            <option value="<?php echo $equipo_item['id']; ?>">
                                <?php echo htmlspecialchars($equipo_item['nombre'] . ' - ' . $equipo_item['categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="fallback-form-group">
                    <label for="equipo_visitante" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                        🏆 Equipo Contrario *
                    </label>
                    <input type="text" 
                           id="equipo_visitante" 
                           name="equipo_visitante" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input uppercase" 
                           style="text-transform: uppercase;"
                           placeholder="Nombre del equipo contrario"
                           required>
                </div>
                
                <div class="fallback-form-group">
                    <label for="fecha" class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                        📅 Fecha y Hora *
                    </label>
                    <input type="datetime-local" 
                           id="fecha" 
                           name="fecha" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-club-primario focus:border-club-primario fallback-input" 
                           required>
                </div>
                
                <div class="fallback-form-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2 fallback-label">
                        ⏱️ Número de Períodos *
                    </label>
                    <div style="display: flex; gap: 20px; align-items: center;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="periodos" value="4" onchange="toggleReglasPasarela()" style="margin: 0;">
                            <span style="font-weight: 500;">4 Períodos</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.3s;">
                            <input type="radio" name="periodos" value="6" onchange="toggleReglasPasarela()" style="margin: 0;">
                            <span style="font-weight: 500;">6 Períodos</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Sección de reglas y validaciones -->
            <div id="reglasSection" style="display: none; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid var(--color-acento);">
                <!-- Checkbox de reglas pasarela (solo para 6 períodos) -->
                <div id="reglasPasarelaCheckbox" style="display: none; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="reglas_pasarela" id="reglas_pasarela" value="1" onchange="actualizarValidacionJugadores()">
                        <label for="reglas_pasarela" class="text-sm font-medium text-gray-700">
                            ⚖️ ¿Con reglas de pasarela?
                        </label>
                    </div>
                </div>
                
                <!-- Mensaje de validación de jugadores -->
                <div id="mensajeValidacion" style="padding: 10px; border-radius: 6px; font-size: 14px; font-weight: 500;">
                    <!-- El contenido se actualizará dinámicamente -->
                </div>
            </div>
            
            <!-- Sección de jugadores -->
            <div id="jugadoresSection" style="display: none; margin-top: 25px;">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--color-secundario);">
                    👥 Seleccionar Jugadores Presentes
                </h3>
                <p style="color: #666; margin-bottom: 15px;">
                    Haz clic en los jugadores que han venido al partido:
                </p>
                <div class="jugadores-grid" id="jugadoresGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <!-- Los jugadores se cargarán aquí via JavaScript -->
                </div>
            </div>
            
            <!-- Sección de asignación por períodos -->
            <div id="periodosAsignacion" style="display: none; margin-top: 30px;">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--color-secundario);">
                    ⚽ Asignación de Jugadores por Período
                </h3>
                <p style="color: #666; margin-bottom: 20px;">
                    Selecciona los 5 jugadores que jugarán en cada período:
                </p>
                
                <div id="periodosContainer" style="display: grid; gap: 20px;">
                    <!-- Los períodos se generarán dinámicamente aquí -->
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px; border-left: 4px solid var(--color-acento);">
                    <p style="margin: 0; font-size: 14px; color: #2d5016;">
                        💡 <strong>Consejo:</strong> Asegúrate de que cada jugador tenga tiempo de juego equilibrado según las reglas de tu categoría.
                    </p>
                </div>
                
                <!-- Leyenda de iconos -->
                <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #666;">
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600;">Leyenda de iconos:</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; font-size: 13px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="background: #ffcccc; color: #cc0000; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">X</span>
                            <span>Descalificado</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="background: #ffcccc; color: #cc0000; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">🤕</span>
                            <span>Lesionado</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="background: #ffcccc; color: #cc0000; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">5</span>
                            <span>5 Faltas</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <span style="background: #fff3cd; color: #ff9800; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-weight: bold;">⚠️</span>
                            <span>Obligatorio (regla pasarela)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="submit" 
                        name="crear_partido" 
                        class="px-6 py-3 bg-club-primario text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-club-primario focus:ring-offset-2 transition-all fallback-btn">
                    ⚡ Crear Partido
                </button>
            </div>
        </form>
    </div>
    
    <!-- Lista de partidos -->
    <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
        <h2 class="text-xl font-semibold mb-6" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px;">
            📅 Mis Partidos
        </h2>
        
        <?php if (empty($partidos)): ?>
            <div style="text-align: center; color: #666; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #ddd;">
                <div style="font-size: 3rem; margin-bottom: 15px;">🏀</div>
                <p style="font-size: 1.1rem; margin: 0;">No tienes partidos registrados aún.</p>
                <p style="margin: 5px 0 0 0; color: var(--color-secundario); font-weight: 500;">¡Crea tu primer partido!</p>
            </div>
        <?php else: ?>
            <?php foreach ($partidos as $partido): ?>
                <div class="border border-gray-200 rounded-lg p-4 mb-4 hover:border-club-primario transition-all fallback-card" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div>
                            <div style="font-weight: 600; color: var(--color-secundario); font-size: 1.1rem;">
                                📅 <?php echo date('d/m/Y H:i', strtotime($partido['fecha'])); ?>
                            </div>
                            <div style="font-weight: 600; margin-top: 8px; font-size: 1.1rem;">
                                <span style="color: var(--color-secundario);"><?php echo htmlspecialchars($partido['equipo_nombre']); ?></span>
                                <span style="color: #666;"> vs </span>
                                <span style="color: var(--color-acento);"><?php echo htmlspecialchars($partido['equipo_visitante']); ?></span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <button onclick="editarPartido(<?php echo $partido['id']; ?>)" 
                                    style="padding: 6px 12px; background: var(--color-primario); color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.3s;">
                                ✏️ Editar
                            </button>
                            <div style="padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: var(--color-acento); color: white;">
                                <?php echo ucfirst($partido['estado']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; font-size: 14px; color: #666; flex-wrap: wrap;">
                        <span>🏀 <?php echo $partido['periodos']; ?> períodos</span>
                        <?php if ($partido['reglas_pasarela']): ?>
                            <span>⚖️ Con reglas de pasarela</span>
                        <?php endif; ?>
                        <span>📍 <?php echo htmlspecialchars($partido['categoria']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="mt-6 flex space-x-4" style="margin-top: 24px; display: flex; gap: 16px;">
        <a href="dashboard.php" 
           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all fallback-btn-secondary">
            Volver al Dashboard
        </a>
    </div>
</main>

<!-- Modal para marcar jugador -->
<div id="marcarJugadorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 12px; padding: 20px; width: 90%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; color: var(--color-secundario); font-size: 1.2rem; font-weight: 600; margin-bottom: 15px;">
            Marcar jugador
        </h3>
        
        <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
            Selecciona el motivo por el que este jugador debe ser sustituido:
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
            <button onclick="marcarJugador('descalificado')" style="padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                <span style="font-size: 20px; font-weight: bold; color: #cc0000;">X</span>
                <span style="font-size: 12px;">Descalificado</span>
            </button>
            
            <button onclick="marcarJugador('lesionado')" style="padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                <span style="font-size: 20px;">🤕</span>
                <span style="font-size: 12px;">Lesionado</span>
            </button>
            
            <button onclick="marcarJugador('faltas')" style="padding: 10px; border: 2px solid #e9ecef; border-radius: 8px; background: white; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; align-items: center; gap: 5px;">
                <span style="font-size: 20px; font-weight: bold; color: #cc0000;">5</span>
                <span style="font-size: 12px;">5 Faltas</span>
            </button>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button onclick="cerrarModal()" style="padding: 8px 16px; background: #f1f1f1; border: none; border-radius: 6px; cursor: pointer;">
                Cancelar
            </button>
        </div>
    </div>
</div>

<style>
    .jugadores-grid .jugador-card {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .jugadores-grid .jugador-card:hover {
        border-color: var(--color-primario);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .jugadores-grid .jugador-card.selected {
        background: linear-gradient(135deg, var(--color-primario) 0%, var(--color-secundario) 100%);
        color: white;
        border-color: var(--color-primario);
        box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        transform: translateY(-3px) scale(1.02);
    }

    .jugadores-grid .jugador-card.selected .jugador-numero,
    .jugadores-grid .jugador-card.selected .jugador-nombre {
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    
    .jugadores-grid .jugador-numero {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .jugadores-grid .jugador-nombre {
        font-size: 14px;
        font-weight: 500;
    }

    input[name="periodos"] {
        accent-color: var(--color-primario);
    }

    input[name="periodos"]:checked + span {
        color: var(--color-primario);
        font-weight: 600;
    }

    .periodo-card {
        transition: all 0.3s ease;
    }

    .periodo-card:hover {
        border-color: var(--color-primario);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    .jugador-periodo-card:hover {
        border-color: var(--color-primario);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .jugador-periodo-card.selected-periodo {
        transform: translateY(-2px) scale(1.05);
    }
    
    .jugador-periodo-card {
        transition: all 0.3s ease !important;
        position: relative;
    }

    .jugadores-periodo-grid {
        transition: all 0.3s ease;
    }

    .periodo-completo {
        background: linear-gradient(135deg, #e8f5e8 0%, #f0f9f0 100%) !important;
        border-color: var(--color-acento) !important;
    }

    .periodo-completo h4 {
        color: var(--color-acento) !important;
    }
    
    .jugador-periodo-card.descalificado,
    .jugador-periodo-card.lesionado,
    .jugador-periodo-card.faltas {
        background: #fff0f0 !important;
        border-color: #ffcccc !important;
        color: #cc0000 !important;
        opacity: 0.8;
    }
    
    .jugador-periodo-card .estado-icono {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #ffcccc;
        color: #cc0000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .jugador-periodo-card .menu-opciones {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #f1f1f1;
        color: #666;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .jugador-periodo-card:hover .menu-opciones {
        opacity: 1;
    }

.jugador-periodo-card.bloqueado-pasarela {
    background: #f5f5f5 !important;
    border-color: #ccc !important;
    color: #999 !important;
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

.jugador-periodo-card.bloqueado-pasarela:hover {
    transform: none !important;
    box-shadow: none !important;
}

.jugador-periodo-card .icono-bloqueo-pasarela {
    position: absolute;
    top: -8px;
    left: -8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #ffeeee;
    color: #cc0000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.jugador-periodo-card.obligatorio-pasarela {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
    border-color: #ff9800 !important;
    box-shadow: 0 0 10px rgba(255,152,0,0.3) !important;
}

.jugador-periodo-card.obligatorio-pasarela.selected-periodo {
    background: linear-gradient(135deg, #ff9800, #f57c00) !important;
    border-color: #ff9800 !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(255,152,0,0.4) !important;
}

.jugador-periodo-card .icono-obligatorio-pasarela {
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #fff3cd;
    color: #ff9800;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    animation: pulse-obligatorio 2s infinite;
}

@keyframes pulse-obligatorio {
    0% { transform: translateX(-50%) scale(1); }
    50% { transform: translateX(-50%) scale(1.1); }
    100% { transform: translateX(-50%) scale(1); }
}
</style>

<script>
// Variables globales para el modal
let jugadorActualId = null;
let periodoActual = null;
let cardActual = null;

function toggleReglasPasarela() {
    const periodosRadio = document.querySelector('input[name="periodos"]:checked');
    if (!periodosRadio) {
        // No hay períodos seleccionados, ocultar sección de reglas
        document.getElementById('reglasSection').style.display = 'none';
        return;
    }
    
    const periodos = periodosRadio.value;
    const reglasSection = document.getElementById('reglasSection');
    const reglasPasarelaCheckbox = document.getElementById('reglasPasarelaCheckbox');
    const reglasPasarelaInput = document.getElementById('reglas_pasarela');
    
    // Siempre mostrar la sección de reglas cuando hay períodos seleccionados
    reglasSection.style.display = 'block';
    
    if (periodos === '6') {
        reglasPasarelaCheckbox.style.display = 'block';
    } else {
        reglasPasarelaCheckbox.style.display = 'none';
        reglasPasarelaInput.checked = false;
    }
    
    // Actualizar estilos de radio buttons
    document.querySelectorAll('input[name="periodos"]').forEach(radio => {
        const label = radio.closest('label');
        if (radio.checked) {
            label.style.borderColor = 'var(--color-primario)';
            label.style.backgroundColor = '#f0f9ff';
        } else {
            label.style.borderColor = '#e5e7eb';
            label.style.backgroundColor = 'white';
        }
    });
    
    actualizarValidacionJugadores();
}

function actualizarValidacionJugadores() {
    const periodosRadio = document.querySelector('input[name="periodos"]:checked');
    if (!periodosRadio) {
        // No hay períodos seleccionados, ocultar validación
        document.getElementById('mensajeValidacion').style.display = 'none';
        return;
    }
    
    const periodos = periodosRadio.value;
    const reglasPasarela = document.getElementById('reglas_pasarela').checked;
    const mensajeValidacion = document.getElementById('mensajeValidacion');
    
    let jugadoresNecesarios;
    let mensaje;
    let colorFondo;
    let colorTexto;
    
    if (periodos === '4') {
        jugadoresNecesarios = 5;
        mensaje = `⚠️ Se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para un partido de 4 períodos.`;
        colorFondo = '#fff3cd';
        colorTexto = '#856404';
    } else if (periodos === '6' && reglasPasarela) {
        jugadoresNecesarios = 9;
        mensaje = `⚖️ Con reglas de pasarela se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para garantizar tiempo mínimo de juego.`;
        colorFondo = '#d1ecf1';
        colorTexto = '#0c5460';
    } else if (periodos === '6' && !reglasPasarela) {
        jugadoresNecesarios = 5;
        mensaje = `⚠️ Se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para un partido de 6 períodos.`;
        colorFondo = '#fff3cd';
        colorTexto = '#856404';
    }
    
    mensajeValidacion.innerHTML = mensaje;
    mensajeValidacion.style.backgroundColor = colorFondo;
    mensajeValidacion.style.color = colorTexto;
    mensajeValidacion.style.border = `1px solid ${colorTexto}40`;
    mensajeValidacion.style.display = 'block';
    
    // Verificar jugadores seleccionados si ya hay alguno
    verificarJugadoresSeleccionados();

    // También actualizar bloqueos cuando cambian las reglas de pasarela
    actualizarBloqueosPasarela();
}

function verificarJugadoresSeleccionados() {
    const periodos = document.querySelector('input[name="periodos"]:checked').value;
    const reglasPasarela = document.getElementById('reglas_pasarela').checked;
    const jugadoresSeleccionados = document.querySelectorAll('.jugador-card.selected').length;
    const mensajeValidacion = document.getElementById('mensajeValidacion');
    
    let jugadoresNecesarios;
    
    if (periodos === '4') {
        jugadoresNecesarios = 5;
    } else if (periodos === '6' && reglasPasarela) {
        jugadoresNecesarios = 9;
    } else if (periodos === '6' && !reglasPasarela) {
        jugadoresNecesarios = 5;
    }
    
    if (jugadoresSeleccionados >= jugadoresNecesarios) {
        // Ocultar mensaje cuando se cumple el requisito
        mensajeValidacion.style.display = 'none';
        
        // Mostrar sección de asignación por períodos
        mostrarAsignacionPeriodos();
    } else {
        // Ocultar sección de asignación si no hay suficientes jugadores
        document.getElementById('periodosAsignacion').style.display = 'none';
        
        // Mostrar mensaje actualizado con el progreso
        mensajeValidacion.style.display = 'block';
        const faltantes = jugadoresNecesarios - jugadoresSeleccionados;
        
        let mensaje;
        if (periodos === '4') {
            mensaje = `⚠️ Se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para un partido de 4 períodos. <br><small>Seleccionados: ${jugadoresSeleccionados}/${jugadoresNecesarios} (faltan ${faltantes})</small>`;
        } else if (periodos === '6' && reglasPasarela) {
            mensaje = `⚖️ Con reglas de pasarela se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para garantizar tiempo mínimo de juego. <br><small>Seleccionados: ${jugadoresSeleccionados}/${jugadoresNecesarios} (faltan ${faltantes})</small>`;
        } else if (periodos === '6' && !reglasPasarela) {
            mensaje = `⚠️ Se necesitan al menos <strong>${jugadoresNecesarios} jugadores</strong> para un partido de 6 períodos. <br><small>Seleccionados: ${jugadoresSeleccionados}/${jugadoresNecesarios} (faltan ${faltantes})</small>`;
        }
        
        mensajeValidacion.innerHTML = mensaje;
    }
}
    
    function cargarJugadores() {
        const equipoId = document.getElementById('equipo_id').value;
        const jugadoresSection = document.getElementById('jugadoresSection');
        const jugadoresGrid = document.getElementById('jugadoresGrid');
        
        if (!equipoId) {
            jugadoresSection.style.display = 'none';
            // Limpiar selección de períodos cuando no hay equipo
            document.querySelectorAll('input[name="periodos"]').forEach(radio => {
                radio.checked = false;
            });
            document.getElementById('reglasSection').style.display = 'none';
            return;
        }
        
        // Obtener la categoría del equipo seleccionado
        const equipoSelect = document.getElementById('equipo_id');
        const equipoTexto = equipoSelect.options[equipoSelect.selectedIndex].text;
        const equipoTextoLower = equipoTexto.toLowerCase();
        
        // Configuración automática según la categoría
        let esCategoriaInferior = false;
        let categoria = '';
        
        // Verificar cada categoría
        if (equipoTextoLower.includes('prebenjamin') || equipoTextoLower.includes('pre-benjamin') || equipoTextoLower.includes('pre benjamin')) {
            esCategoriaInferior = true;
            categoria = 'Prebenjamín';
        } else if (equipoTextoLower.includes('benjamin') || equipoTextoLower.includes('benjamín')) {
            esCategoriaInferior = true;
            categoria = 'Benjamín';
        } else if (equipoTextoLower.includes('alevin') || equipoTextoLower.includes('alevín')) {
            esCategoriaInferior = true;
            categoria = 'Alevín';
        }
        
        if (esCategoriaInferior) {
            // Categorías menores: 6 períodos con reglas de pasarela
            document.querySelector('input[name="periodos"][value="6"]').checked = true;
            document.getElementById('reglas_pasarela').checked = true;
        
            // Mostrar mensaje informativo
            setTimeout(() => {
                alert('ℹ️ Configuración automática para ' + categoria + ':\n\n• 6 períodos\n• Reglas de pasarela activadas\n\nEsto garantiza tiempo mínimo de juego para todos los jugadores.');
            }, 500);
        } else {
            // Categorías mayores: 4 períodos sin reglas de pasarela
            document.querySelector('input[name="periodos"][value="4"]').checked = true;
            document.getElementById('reglas_pasarela').checked = false;
        }
        
        // Actualizar la interfaz
        toggleReglasPasarela();
        
        // Hacer petición AJAX para obtener jugadores
        fetch(`../api/obtener_jugadores.php?equipo_id=${equipoId}`)
            .then(response => response.json())
            .then(data => {
                jugadoresGrid.innerHTML = '';
                
                data.forEach(jugador => {
                    const jugadorCard = document.createElement('div');
                    jugadorCard.className = 'jugador-card';
                    jugadorCard.onclick = () => toggleJugador(jugadorCard, jugador.id);
                    
                    jugadorCard.innerHTML = `
                        <div class="jugador-numero">#${jugador.numero_camiseta}</div>
                        <div class="jugador-nombre">${jugador.nombre} ${jugador.apellidos}</div>
                        <input type="checkbox" name="jugadores[]" value="${jugador.id}" style="display: none;">
                    `;
                    
                    jugadoresGrid.appendChild(jugadorCard);
                });
                
                jugadoresSection.style.display = 'block';
                
                // Inicializar validación de jugadores
                actualizarValidacionJugadores();
            })
            .catch(error => {
                console.error('Error al cargar jugadores:', error);
            });
}
    
    function toggleJugador(card, jugadorId) {
        const checkbox = card.querySelector('input[type="checkbox"]');
        
        if (card.classList.contains('selected')) {
            card.classList.remove('selected');
            checkbox.checked = false;
        } else {
            card.classList.add('selected');
            checkbox.checked = true;
        }
        
        // Verificar requisitos de jugadores después de cada selección
        verificarJugadoresSeleccionados();
    }
    
    function editarPartido(partidoId) {
        // Por ahora, redirigir a una página de edición
        window.location.href = `editar_partido.php?id=${partidoId}`;
    }
    
    // Establecer fecha mínima como hoy
    document.getElementById('fecha').min = new Date().toISOString().slice(0, 16);

    // Convertir nombre del equipo contrario a mayúsculas
    document.getElementById('equipo_visitante').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar estilos de radio buttons
        toggleReglasPasarela();
        
        // Añadir event listeners para radio buttons
        document.querySelectorAll('input[name="periodos"]').forEach(radio => {
            radio.addEventListener('change', toggleReglasPasarela);
        });
    });

    function mostrarAsignacionPeriodos() {
        const periodos = document.querySelector('input[name="periodos"]:checked').value;
        const periodosAsignacion = document.getElementById('periodosAsignacion');
        const periodosContainer = document.getElementById('periodosContainer');
        
        // Limpiar contenedor
        periodosContainer.innerHTML = '';
        
        // Crear tarjetas para cada período
        for (let i = 1; i <= parseInt(periodos); i++) {
            const periodoCard = document.createElement('div');
            periodoCard.className = 'periodo-card';
            periodoCard.style.cssText = `
                background: white;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;
            
            periodoCard.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="margin: 0; color: var(--color-secundario); font-size: 1.1rem; font-weight: 600;">
                        🏀 Período ${i}
                    </h4>
                    <div id="contador-periodo-${i}" style="font-size: 14px; color: #666; font-weight: 500;">
                        0/5 jugadores
                    </div>
                </div>
                
                <div class="jugadores-periodo-grid" id="jugadores-periodo-${i}" 
                     style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                    <!-- Los jugadores se cargarán aquí -->
                </div>
            `;
            
            periodosContainer.appendChild(periodoCard);
        }
        
        // Cargar jugadores en cada período
        cargarJugadoresEnPeriodos();
        
        // Mostrar la sección
        periodosAsignacion.style.display = 'block';
    }

    function cargarJugadoresEnPeriodos() {
        const jugadoresSeleccionados = document.querySelectorAll('.jugador-card.selected');
        const periodos = document.querySelector('input[name="periodos"]:checked').value;
        
        // Para cada período, crear las tarjetas de jugadores
        for (let periodo = 1; periodo <= parseInt(periodos); periodo++) {
            const container = document.getElementById(`jugadores-periodo-${periodo}`);
            container.innerHTML = '';
            
            jugadoresSeleccionados.forEach(jugadorCard => {
                const numero = jugadorCard.querySelector('.jugador-numero').textContent;
                const nombre = jugadorCard.querySelector('.jugador-nombre').textContent;
                const jugadorId = jugadorCard.querySelector('input[type="checkbox"]').value;
                
                const jugadorPeriodoCard = document.createElement('div');
                jugadorPeriodoCard.className = 'jugador-periodo-card';
                jugadorPeriodoCard.dataset.jugadorId = jugadorId;
                jugadorPeriodoCard.style.cssText = `
                    background: #f8f9fa;
                    border: 2px solid #e9ecef;
                    border-radius: 8px;
                    padding: 10px;
                    text-align: center;
                    cursor: pointer;
                    transition: all 0.3s;
                    font-size: 12px;
                `;
                
                jugadorPeriodoCard.onclick = () => toggleJugadorPeriodo(jugadorPeriodoCard, periodo, jugadorId);
                
                jugadorPeriodoCard.innerHTML = `
                    <div style="font-weight: bold; margin-bottom: 4px;">${numero}</div>
                    <div style="font-size: 10px;">${nombre.split(' ')[0]}</div>
                    <input type="checkbox" name="periodo_${periodo}_jugadores[]" value="${jugadorId}" style="display: none;">
                    <div class="menu-opciones" onclick="mostrarModalMarcar(event, ${jugadorId}, ${periodo}, this.parentNode)">⋮</div>
                `;
                
                container.appendChild(jugadorPeriodoCard);

                // Inicializar bloqueos de pasarela
                setTimeout(() => {
                    actualizarBloqueosPasarela();
                }, 100);
            });
        }
    }

    function toggleJugadorPeriodo(card, periodo, jugadorId) {
        // Verificar si está bloqueado por reglas de pasarela
        if (card.classList.contains('bloqueado-pasarela')) {
            const nombreJugador = card.querySelector('div:nth-child(2)').textContent;
            alert(`⚖️ ${nombreJugador} está bloqueado por la regla de pasarela.

No puede jugar más de 2 períodos seguidos en los primeros 5 períodos.`);
            return;
        }

        const checkbox = card.querySelector('input[type="checkbox"]');
        const contador = document.getElementById(`contador-periodo-${periodo}`);
        const jugadoresEnPeriodo = document.querySelectorAll(`#jugadores-periodo-${periodo} .jugador-periodo-card.selected-periodo`);
        
        // Si el jugador está marcado como descalificado, lesionado o con 5 faltas, no permitir seleccionarlo
        if (card.classList.contains('descalificado') || card.classList.contains('lesionado') || card.classList.contains('faltas')) {
            alert('⚠️ Este jugador no puede ser seleccionado porque está marcado como no disponible.');
            return;
        }

        // Verificar si es un jugador obligatorio por reglas de pasarela
        if (card.classList.contains('obligatorio-pasarela') && card.classList.contains('selected-periodo')) {
            const nombreJugador = card.querySelector('div:nth-child(2)').textContent;
            alert(`⚖️ ${nombreJugador} es obligatorio en este período por la regla de pasarela.

No ha participado en ninguno de los primeros 4 períodos y debe jugar al menos una vez.

Solo se puede quitar si se marca como:
• Descalificado
• Lesionado  
• 5 Faltas`);
            return;
        }
        
        // Verificar regla de pasarela: no más de 2 períodos seguidos en los primeros 5
        if (document.getElementById('reglas_pasarela').checked && periodo <= 5) {
            if (!verificarReglaPasarela(jugadorId, periodo)) {
                return;
            }
        }
        
        if (card.classList.contains('selected-periodo')) {
            // Deseleccionar
            card.classList.remove('selected-periodo');
            card.style.background = '#f8f9fa';
            card.style.borderColor = '#e9ecef';
            card.style.color = '#333';
            checkbox.checked = false;
            
            // Mostrar todos los jugadores cuando se deselecciona uno
            mostrarTodosJugadoresPeriodo(periodo);
        } else {
            // Verificar si ya hay 5 jugadores seleccionados
            if (jugadoresEnPeriodo.length >= 5) {
                alert('⚠️ Ya has seleccionado 5 jugadores para este período.');
                return;
            }
            
            // Seleccionar
            card.classList.add('selected-periodo');
            card.style.background = 'linear-gradient(135deg, var(--color-primario), var(--color-secundario))';
            card.style.borderColor = 'var(--color-primario)';
            card.style.color = 'white';
            card.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
            checkbox.checked = true;
        }
        
        // Actualizar contador
        const seleccionados = document.querySelectorAll(`#jugadores-periodo-${periodo} .jugador-periodo-card.selected-periodo`).length;
        contador.textContent = `${seleccionados}/5 jugadores`;

        // Actualizar bloqueos de pasarela después de cada cambio
        actualizarBloqueosPasarela();
        
        if (seleccionados === 5) {
            contador.style.color = 'var(--color-acento)';
            contador.style.fontWeight = '600';
            contador.innerHTML = `✅ ${seleccionados}/5 jugadores - <span style="font-size: 12px;">¡Período completo!</span>`;
            
            // Ocultar jugadores no seleccionados cuando se completan los 5
            ocultarJugadoresNoSeleccionados(periodo);
        } else {
            contador.style.color = '#666';
            contador.style.fontWeight = '500';
            contador.textContent = `${seleccionados}/5 jugadores`;
            
            // Mostrar todos los jugadores si no están completos los 5
            mostrarTodosJugadoresPeriodo(periodo);
        }
    }

    function ocultarJugadoresNoSeleccionados(periodo) {
        const container = document.getElementById(`jugadores-periodo-${periodo}`);
        const jugadores = container.querySelectorAll('.jugador-periodo-card');
        
        jugadores.forEach(jugador => {
            if (!jugador.classList.contains('selected-periodo') && 
                !jugador.classList.contains('descalificado') && 
                !jugador.classList.contains('lesionado') && 
                !jugador.classList.contains('faltas')) {
                // Ocultar con animación suave
                jugador.style.transition = 'all 0.3s ease';
                jugador.style.opacity = '0';
                jugador.style.transform = 'scale(0.8)';
                
                setTimeout(() => {
                    jugador.style.display = 'none';
                }, 300);
            }
        });
        
        // Reorganizar grid para centrar los seleccionados
        setTimeout(() => {
            container.style.justifyContent = 'center';
            container.style.gridTemplateColumns = 'repeat(5, 150px)';
        }, 300);
    }

    function mostrarTodosJugadoresPeriodo(periodo) {
        const container = document.getElementById(`jugadores-periodo-${periodo}`);
        const jugadores = container.querySelectorAll('.jugador-periodo-card');
        
        // Restaurar grid original
        container.style.justifyContent = '';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(150px, 1fr))';
        
        jugadores.forEach(jugador => {
            // Mostrar con animación suave
            jugador.style.display = 'block';
            jugador.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                jugador.style.opacity = '1';
                jugador.style.transform = 'scale(1)';
            }, 50);
        });
    }
    
    function mostrarModalMarcar(event, jugadorId, periodo, card) {
        // Evitar que el click se propague al padre
        event.stopPropagation();
        
        // Guardar referencias para usar en el modal
        jugadorActualId = jugadorId;
        periodoActual = periodo;
        cardActual = card;
        
        // Mostrar el modal
        const modal = document.getElementById('marcarJugadorModal');
        modal.style.display = 'flex';
    }
    
    function cerrarModal() {
        const modal = document.getElementById('marcarJugadorModal');
        modal.style.display = 'none';
    }
    
    function marcarJugador(tipo) {
        // Verificar que tenemos los datos necesarios
        if (!jugadorActualId || !periodoActual || !cardActual) {
            cerrarModal();
            return;
        }
        
        // Marcar el jugador según el tipo
        let icono = '';
        
        if (tipo === 'descalificado') {
            icono = 'X';
            cardActual.classList.add('descalificado');
        } else if (tipo === 'lesionado') {
            icono = '🤕';
            cardActual.classList.add('lesionado');
        } else if (tipo === 'faltas') {
            icono = '5';
            cardActual.classList.add('faltas');
        }
        
        // Si el jugador estaba seleccionado, quitarlo de la selección
        if (cardActual.classList.contains('selected-periodo')) {
            cardActual.classList.remove('selected-periodo');
            cardActual.querySelector('input[type="checkbox"]').checked = false;
            
            // Actualizar contador
            const contador = document.getElementById(`contador-periodo-${periodoActual}`);
            const seleccionados = document.querySelectorAll(`#jugadores-periodo-${periodoActual} .jugador-periodo-card.selected-periodo`).length;
            contador.textContent = `${seleccionados}/5 jugadores`;
            
            if (seleccionados === 5) {
                contador.style.color = 'var(--color-acento)';
                contador.style.fontWeight = '600';
                contador.innerHTML = `✅ ${seleccionados}/5 jugadores - <span style="font-size: 12px;">¡Período completo!</span>`;
            } else {
                contador.style.color = '#666';
                contador.style.fontWeight = '500';
            }
        }
        
        // Añadir icono de estado
        const menuOpciones = cardActual.querySelector('.menu-opciones');
        if (menuOpciones) {
            menuOpciones.remove();
        }
        
        // Crear el icono de estado
        const estadoIcono = document.createElement('div');
        estadoIcono.className = 'estado-icono';
        estadoIcono.innerHTML = icono;
        cardActual.appendChild(estadoIcono);
        
        // Aplicar estilos
        cardActual.style.background = '#fff0f0';
        cardActual.style.borderColor = '#ffcccc';
        cardActual.style.color = '#cc0000';
        cardActual.style.opacity = '0.8';
        
        // Añadir campo oculto para registrar el estado
        const inputEstado = document.createElement('input');
        inputEstado.type = 'hidden';
        inputEstado.name = `jugador_${jugadorActualId}_estado_${periodoActual}`;
        inputEstado.value = tipo;
        cardActual.appendChild(inputEstado);
        
        // Cerrar el modal
        cerrarModal();
        
        // Mostrar todos los jugadores para permitir seleccionar otro
        mostrarTodosJugadoresPeriodo(periodoActual);
    }

function verificarReglaPasarela(jugadorId, periodoActual) {
    // Solo aplicar en los primeros 5 períodos
    if (periodoActual > 5) return true;
    
    // Obtener todos los períodos donde este jugador está seleccionado
    const periodosJugador = [];
    for (let p = 1; p <= 5; p++) {
        const jugadorCard = document.querySelector(`#jugadores-periodo-${p} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
        if (jugadorCard && jugadorCard.classList.contains('selected-periodo')) {
            periodosJugador.push(p);
        }
    }
    
    // Si ya está seleccionado en el período actual, permitir deselección
    const jugadorCardActual = document.querySelector(`#jugadores-periodo-${periodoActual} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
    if (jugadorCardActual && jugadorCardActual.classList.contains('selected-periodo')) {
        return true;
    }
    
    // Verificar si añadir este período violaría la regla
    const nuevosPeriodos = [...periodosJugador, periodoActual].sort((a, b) => a - b);
    
    // Buscar secuencias de 3 períodos consecutivos
    for (let i = 0; i < nuevosPeriodos.length - 2; i++) {
        if (nuevosPeriodos[i + 1] === nuevosPeriodos[i] + 1 && 
            nuevosPeriodos[i + 2] === nuevosPeriodos[i] + 2) {
            
            const nombreJugador = jugadorCardActual.querySelector('div:nth-child(2)').textContent;
            alert(`⚖️ Regla de pasarela: ${nombreJugador} no puede jugar más de 2 períodos seguidos en los primeros 5 períodos.

Períodos ya asignados: ${periodosJugador.join(', ')}
Período que intentas asignar: ${periodoActual}

Esto crearía una secuencia de 3 períodos consecutivos: ${nuevosPeriodos[i]}, ${nuevosPeriodos[i + 1]}, ${nuevosPeriodos[i + 2]}`);
            return false;
        }
    }
    
    return true;
}

function actualizarBloqueosPasarela() {
    // Solo aplicar si las reglas de pasarela están activadas
    if (!document.getElementById('reglas_pasarela').checked) {
        // Quitar todos los bloqueos si no hay reglas de pasarela
        document.querySelectorAll('.jugador-periodo-card.bloqueado-pasarela').forEach(card => {
            card.classList.remove('bloqueado-pasarela');
            const iconoBloqueo = card.querySelector('.icono-bloqueo-pasarela');
            if (iconoBloqueo) iconoBloqueo.remove();
        });
        return;
    }
    
    // Para cada jugador, verificar en qué períodos puede jugar
    const jugadoresSeleccionados = document.querySelectorAll('.jugador-card.selected');
    
    jugadoresSeleccionados.forEach(jugadorCard => {
        const jugadorId = jugadorCard.querySelector('input[type="checkbox"]').value;
        
        // Para cada período del 1 al 5, verificar si puede jugar
        for (let periodo = 1; periodo <= 5; periodo++) {
            const jugadorPeriodoCard = document.querySelector(`#jugadores-periodo-${periodo} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
            if (!jugadorPeriodoCard) continue;
            
            // Si ya está seleccionado, no bloquear
            if (jugadorPeriodoCard.classList.contains('selected-periodo')) {
                continue;
            }
            
            // Si está marcado como no disponible, no verificar reglas de pasarela
            if (jugadorPeriodoCard.classList.contains('descalificado') || 
                jugadorPeriodoCard.classList.contains('lesionado') || 
                jugadorPeriodoCard.classList.contains('faltas')) {
                continue;
            }
            
            // Verificar si puede jugar en este período según las reglas de pasarela
            const puedeJugar = verificarReglaPasarelaSimulacion(jugadorId, periodo);
            
            if (!puedeJugar) {
                // Bloquear el jugador
                jugadorPeriodoCard.classList.add('bloqueado-pasarela');
                
                // Añadir icono de bloqueo si no existe
                if (!jugadorPeriodoCard.querySelector('.icono-bloqueo-pasarela')) {
                    const iconoBloqueo = document.createElement('div');
                    iconoBloqueo.className = 'icono-bloqueo-pasarela';
                    iconoBloqueo.innerHTML = '🚫';
                    iconoBloqueo.title = 'Bloqueado por regla de pasarela';
                    jugadorPeriodoCard.appendChild(iconoBloqueo);
                }
            } else {
                // Desbloquear el jugador
                jugadorPeriodoCard.classList.remove('bloqueado-pasarela');
                const iconoBloqueo = jugadorPeriodoCard.querySelector('.icono-bloqueo-pasarela');
                if (iconoBloqueo) iconoBloqueo.remove();
            }
        }
    });

    // Aplicar regla de tiempo mínimo: todos deben jugar al menos 1 período en los primeros 5
    aplicarReglaJugadorObligatorio();
}

function verificarReglaPasarelaSimulacion(jugadorId, periodoSimulado) {
    // Solo aplicar en los primeros 5 períodos
    if (periodoSimulado > 5) return true;
    
    // Obtener todos los períodos donde este jugador está seleccionado
    const periodosJugador = [];
    for (let p = 1; p <= 5; p++) {
        const jugadorCard = document.querySelector(`#jugadores-periodo-${p} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
        if (jugadorCard && jugadorCard.classList.contains('selected-periodo')) {
            periodosJugador.push(p);
        }
    }
    
    // Simular añadir el período
    const nuevosPeriodos = [...periodosJugador, periodoSimulado].sort((a, b) => a - b);
    
    // Buscar secuencias de 3 períodos consecutivos
    for (let i = 0; i < nuevosPeriodos.length - 2; i++) {
        if (nuevosPeriodos[i + 1] === nuevosPeriodos[i] + 1 && 
            nuevosPeriodos[i + 2] === nuevosPeriodos[i] + 2) {
            return false;
        }
    }
    
    return true;
}

function aplicarReglaJugadorObligatorio() {
    // Solo aplicar si las reglas de pasarela están activadas
    if (!document.getElementById('reglas_pasarela').checked) {
        // Quitar todos los marcados como obligatorios
        document.querySelectorAll('.jugador-periodo-card.obligatorio-pasarela').forEach(card => {
            card.classList.remove('obligatorio-pasarela');
            const iconoObligatorio = card.querySelector('.icono-obligatorio-pasarela');
            if (iconoObligatorio) iconoObligatorio.remove();
        });
        return;
    }

    const jugadoresSeleccionados = document.querySelectorAll('.jugador-card.selected');
    
    jugadoresSeleccionados.forEach(jugadorCard => {
        const jugadorId = jugadorCard.querySelector('input[type="checkbox"]').value;
        
        // Verificar si el jugador ha jugado en ALGUNO de los períodos 1-4
        let haJugadoEnPrimerosCuatro = false;
        for (let p = 1; p <= 4; p++) {
            const jugadorPeriodoCard = document.querySelector(`#jugadores-periodo-${p} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
            if (jugadorPeriodoCard && jugadorPeriodoCard.classList.contains('selected-periodo')) {
                haJugadoEnPrimerosCuatro = true;
                break;
            }
        }
        
        // Solo marcar como obligatorio si NO ha jugado en NINGUNO de los primeros 4 períodos
        const jugadorPeriodo5 = document.querySelector(`#jugadores-periodo-5 .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
        if (jugadorPeriodo5) {
            if (!haJugadoEnPrimerosCuatro) {
                // Verificar si está marcado como no disponible
                const estaNoDisponible = jugadorPeriodo5.classList.contains('descalificado') || 
                                        jugadorPeriodo5.classList.contains('lesionado') || 
                                        jugadorPeriodo5.classList.contains('faltas');
                
                if (!estaNoDisponible) {
                    // Marcar como obligatorio SOLO si no ha jugado en ningún período anterior
                    jugadorPeriodo5.classList.add('obligatorio-pasarela');
                    
                    // Añadir icono de obligatorio si no existe
                    if (!jugadorPeriodo5.querySelector('.icono-obligatorio-pasarela')) {
                        const iconoObligatorio = document.createElement('div');
                        iconoObligatorio.className = 'icono-obligatorio-pasarela';
                        iconoObligatorio.innerHTML = '⚠️';
                        iconoObligatorio.title = 'Obligatorio: no ha jugado en ningún período anterior';
                        jugadorPeriodo5.appendChild(iconoObligatorio);
                    }
                    
                    // NO seleccionar automáticamente - dejar que el entrenador elija
                }
            } else {
                // Ya ha jugado en algún período, quitar marcado de obligatorio si lo tenía
                jugadorPeriodo5.classList.remove('obligatorio-pasarela');
                const iconoObligatorio = jugadorPeriodo5.querySelector('.icono-obligatorio-pasarela');
                if (iconoObligatorio) iconoObligatorio.remove();
            }
        }
    });
}

function esJugadorObligatorio(jugadorId, periodo) {
    // Solo aplicar en período 5 y con reglas de pasarela
    if (periodo !== 5 || !document.getElementById('reglas_pasarela').checked) {
        return false;
    }
    
    // Verificar si el jugador ha jugado en los períodos 1-4
    for (let p = 1; p <= 4; p++) {
        const jugadorPeriodoCard = document.querySelector(`#jugadores-periodo-${p} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
        if (jugadorPeriodoCard && jugadorPeriodoCard.classList.contains('selected-periodo')) {
            return false; // Ya ha jugado, no es obligatorio
        }
    }
    
    // No ha jugado en los primeros 4, es obligatorio en el 5
    return true;
}
</script>

</body>
</html>
