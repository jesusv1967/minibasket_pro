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

// Obtener ID del partido
$partido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$partido_id) {
    header("Location: gestionar_partidos.php");
    exit();
}

// Verificar que el partido pertenece al entrenador
$query_verificar = "SELECT p.*, e.nombre as equipo_nombre, e.categoria, e.entrenador_id 
                   FROM partidos p 
                   JOIN equipos e ON p.equipo_local_id = e.id 
                   WHERE p.id = :partido_id AND e.entrenador_id = :entrenador_id";
$stmt_verificar = $conn->prepare($query_verificar);
$stmt_verificar->bindParam(':partido_id', $partido_id);
$stmt_verificar->bindParam(':entrenador_id', $_SESSION['user_id']);
$stmt_verificar->execute();
$partido = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

if (!$partido) {
    header("Location: gestionar_partidos.php");
    exit();
}

// Obtener jugadores del partido
$query_jugadores_partido = "SELECT pj.jugador_id, j.nombre, j.apellidos, j.numero_camiseta
                           FROM partido_jugadores pj
                           JOIN jugadores j ON pj.jugador_id = j.id
                           WHERE pj.partido_id = :partido_id AND pj.presente = 1";
$stmt_jugadores_partido = $conn->prepare($query_jugadores_partido);
$stmt_jugadores_partido->bindParam(':partido_id', $partido_id);
$stmt_jugadores_partido->execute();
$jugadores_partido = $stmt_jugadores_partido->fetchAll(PDO::FETCH_ASSOC);

// Obtener asignaciones por período
$query_periodos = "SELECT periodo, jugador_id, estado
                  FROM partido_periodo_jugadores
                  WHERE partido_id = :partido_id
                  ORDER BY periodo, jugador_id";
$stmt_periodos = $conn->prepare($query_periodos);
$stmt_periodos->bindParam(':partido_id', $partido_id);
$stmt_periodos->execute();
$asignaciones_periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);

// Organizar asignaciones por período
$jugadores_por_periodo = [];
foreach ($asignaciones_periodos as $asignacion) {
    $periodo = $asignacion['periodo'];
    if (!isset($jugadores_por_periodo[$periodo])) {
        $jugadores_por_periodo[$periodo] = [];
    }
    $jugadores_por_periodo[$periodo][] = $asignacion;
}

// Procesar formulario de actualización
if ($_POST && isset($_POST['actualizar_partido'])) {
    try {
        $conn->beginTransaction();
        
        // Actualizar datos básicos del partido
        $query_update = "UPDATE partidos SET 
                        equipo_visitante = :equipo_visitante,
                        fecha = :fecha,
                        periodos = :periodos,
                        reglas_pasarela = :reglas_pasarela
                        WHERE id = :partido_id";
        
        $stmt_update = $conn->prepare($query_update);
        $stmt_update->bindParam(':equipo_visitante', $_POST['equipo_visitante']);
        $stmt_update->bindParam(':fecha', $_POST['fecha']);
        $stmt_update->bindParam(':periodos', $_POST['periodos']);
        
        $reglas_pasarela = ($_POST['periodos'] == 6 && isset($_POST['reglas_pasarela'])) ? 1 : 0;
        $stmt_update->bindParam(':reglas_pasarela', $reglas_pasarela);
        $stmt_update->bindParam(':partido_id', $partido_id);
        $stmt_update->execute();
        
        // Limpiar jugadores anteriores
        $query_delete_jugadores = "DELETE FROM partido_jugadores WHERE partido_id = :partido_id";
        $stmt_delete_jugadores = $conn->prepare($query_delete_jugadores);
        $stmt_delete_jugadores->bindParam(':partido_id', $partido_id);
        $stmt_delete_jugadores->execute();
        
        // Limpiar asignaciones de períodos anteriores
        $query_delete_periodos = "DELETE FROM partido_periodo_jugadores WHERE partido_id = :partido_id";
        $stmt_delete_periodos = $conn->prepare($query_delete_periodos);
        $stmt_delete_periodos->bindParam(':partido_id', $partido_id);
        $stmt_delete_periodos->execute();
        
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
        
        // Insertar asignaciones por período
        $query_insert_periodo = "INSERT INTO partido_periodo_jugadores (partido_id, jugador_id, periodo, estado) 
                                VALUES (:partido_id, :jugador_id, :periodo, 'normal')";
        $stmt_insert_periodo = $conn->prepare($query_insert_periodo);
        
        $periodos_partido = $_POST['periodos'];
        for ($p = 1; $p <= $periodos_partido; $p++) {
            if (isset($_POST["periodo_{$p}_jugadores"])) {
                foreach ($_POST["periodo_{$p}_jugadores"] as $jugador_id) {
                    $stmt_insert_periodo->bindParam(':partido_id', $partido_id);
                    $stmt_insert_periodo->bindParam(':jugador_id', $jugador_id);
                    $stmt_insert_periodo->bindParam(':periodo', $p);
                    $stmt_insert_periodo->execute();
                }
            }
        }
        
        $conn->commit();
        $mensaje_exito = "Partido actualizado exitosamente";
        
        // Recargar datos actualizados
        $stmt_verificar->execute();
        $partido = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
        
        $stmt_jugadores_partido->execute();
        $jugadores_partido = $stmt_jugadores_partido->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt_periodos->execute();
        $asignaciones_periodos = $stmt_periodos->fetchAll(PDO::FETCH_ASSOC);
        
        $jugadores_por_periodo = [];
        foreach ($asignaciones_periodos as $asignacion) {
            $periodo = $asignacion['periodo'];
            if (!isset($jugadores_por_periodo[$periodo])) {
                $jugadores_por_periodo[$periodo] = [];
            }
            $jugadores_por_periodo[$periodo][] = $asignacion;
        }
        
    } catch(PDOException $e) {
        $conn->rollback();
        $mensaje_error = "Error al actualizar el partido: " . $e->getMessage();
    }
}

// Obtener todos los jugadores del equipo
$equipo = new Equipo();
$jugadores_stmt = $equipo->obtenerJugadores($partido['equipo_local_id']);
$todos_jugadores = $jugadores_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Editar Partido - " . $clubConfig->getNombre();
?>

<?php include '../includes/club_header.php'; ?>

<?php
$clubConfig->renderHeader(
    'Editar Partido',
    'Modificar datos del partido',
    'Entrenador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

<main class="container mx-auto px-4 py-8">
    <!-- Botón volver -->
    <div class="mb-4">
        <a href="gestionar_partidos.php" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            ← Volver a Gestionar Partidos
        </a>
    </div>

    <?php if(isset($mensaje_exito)): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4 border-green-500 text-green-800">
            ✅ <?php echo htmlspecialchars($mensaje_exito); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($mensaje_error)): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-800">
            ❌ <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
    <?php endif; ?>

    <!-- Información del partido -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">📝 Editar Partido</h2>
        
        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-semibold text-blue-800">Información del Partido</h3>
            <p><strong>Equipo:</strong> <?php echo htmlspecialchars($partido['equipo_nombre']); ?> (<?php echo htmlspecialchars($partido['categoria']); ?>)</p>
            <p><strong>Estado:</strong> <?php echo ucfirst($partido['estado']); ?></p>
        </div>

        <form method="POST" id="editarPartidoForm">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="equipo_visitante" class="block text-sm font-medium text-gray-700 mb-2">
                        🏆 Equipo Contrario *
                    </label>
                    <input type="text" 
                           id="equipo_visitante" 
                           name="equipo_visitante" 
                           value="<?php echo htmlspecialchars($partido['equipo_visitante']); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md uppercase" 
                           required>
                </div>
                
                <div>
                    <label for="fecha" class="block text-sm font-medium text-gray-700 mb-2">
                        📅 Fecha y Hora *
                    </label>
                    <input type="datetime-local" 
                           id="fecha" 
                           name="fecha" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($partido['fecha'])); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md" 
                           required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        ⏱️ Número de Períodos *
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="periodos" value="4" 
                                   <?php echo ($partido['periodos'] == 4) ? 'checked' : ''; ?>
                                   onchange="toggleReglasPasarela()">
                            <span class="ml-2">4 Períodos</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="periodos" value="6" 
                                   <?php echo ($partido['periodos'] == 6) ? 'checked' : ''; ?>
                                   onchange="toggleReglasPasarela()">
                            <span class="ml-2">6 Períodos</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Reglas de pasarela -->
            <div id="reglasSection" class="mb-6 p-4 bg-gray-50 rounded-lg">
                <div id="reglasPasarelaCheckbox" style="<?php echo ($partido['periodos'] == 6) ? '' : 'display: none;'; ?>">
                    <label class="flex items-center">
                        <input type="checkbox" name="reglas_pasarela" id="reglas_pasarela" value="1" 
                               <?php echo $partido['reglas_pasarela'] ? 'checked' : ''; ?>
                               onchange="actualizarValidacionJugadores()">
                        <span class="ml-2">⚖️ Con reglas de pasarela</span>
                    </label>
                </div>
                
                <div id="mensajeValidacion" class="mt-3 p-3 rounded text-sm">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>

            <!-- Sección de jugadores -->
            <div id="jugadoresSection" class="mb-6">
                <h3 class="text-lg font-semibold mb-4">👥 Jugadores Presentes</h3>
                <div class="jugadores-grid" id="jugadoresGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                    <?php foreach($todos_jugadores as $jugador): ?>
                        <?php 
                        $esta_seleccionado = false;
                        foreach($jugadores_partido as $jp) {
                            if ($jp['jugador_id'] == $jugador['id']) {
                                $esta_seleccionado = true;
                                break;
                            }
                        }
                        ?>
                        <div class="jugador-card <?php echo $esta_seleccionado ? 'selected' : ''; ?>" 
                             onclick="toggleJugador(this, <?php echo $jugador['id']; ?>)">
                            <div class="jugador-numero">#<?php echo $jugador['numero_camiseta']; ?></div>
                            <div class="jugador-nombre"><?php echo htmlspecialchars($jugador['nombre'] . ' ' . $jugador['apellidos']); ?></div>
                            <input type="checkbox" name="jugadores[]" value="<?php echo $jugador['id']; ?>" 
                                   <?php echo $esta_seleccionado ? 'checked' : ''; ?> style="display: none;">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sección de asignación por períodos -->
            <div id="periodosAsignacion" class="mb-6">
                <h3 class="text-lg font-semibold mb-4">⚽ Asignación de Jugadores por Período</h3>
                <div id="periodosContainer" style="display: grid; gap: 20px;">
                    <!-- Se generará dinámicamente -->
                </div>
            </div>

            <div class="flex gap-4">
                <button type="submit" name="actualizar_partido" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    💾 Actualizar Partido
                </button>
                <a href="gestionar_partidos.php" 
                   class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</main>

<style>
/* Reutilizar los mismos estilos del archivo principal */
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

.jugador-periodo-card {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 12px;
    position: relative;
}

.jugador-periodo-card.selected-periodo {
    background: linear-gradient(135deg, var(--color-primario), var(--color-secundario));
    color: white;
    border-color: var(--color-primario);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transform: translateY(-2px) scale(1.05);
}
</style>

<script>
// Variables globales con datos del partido
const partidoData = {
    jugadoresPorPeriodo: <?php echo json_encode($jugadores_por_periodo); ?>,
    periodos: <?php echo $partido['periodos']; ?>,
    reglasPasarela: <?php echo $partido['reglas_pasarela'] ? 'true' : 'false'; ?>
};

// Funciones principales (reutilizar del archivo principal)
function toggleReglasPasarela() {
    const periodosRadio = document.querySelector('input[name="periodos"]:checked');
    if (!periodosRadio) return;
    
    const periodos = periodosRadio.value;
    const reglasPasarelaCheckbox = document.getElementById('reglasPasarelaCheckbox');
    const reglasPasarelaInput = document.getElementById('reglas_pasarela');
    
    if (periodos === '6') {
        reglasPasarelaCheckbox.style.display = 'block';
    } else {
        reglasPasarelaCheckbox.style.display = 'none';
        reglasPasarelaInput.checked = false;
    }
    
    actualizarValidacionJugadores();
    mostrarAsignacionPeriodos();
}

function actualizarValidacionJugadores() {
    const periodosRadio = document.querySelector('input[name="periodos"]:checked');
    if (!periodosRadio) return;
    
    const periodos = periodosRadio.value;
    const reglasPasarela = document.getElementById('reglas_pasarela').checked;
    const mensajeValidacion = document.getElementById('mensajeValidacion');
    
    let jugadoresNecesarios;
    let mensaje;
    
    if (periodos === '4') {
        jugadoresNecesarios = 5;
        mensaje = `⚠️ Se necesitan al menos ${jugadoresNecesarios} jugadores para un partido de 4 períodos.`;
    } else if (periodos === '6' && reglasPasarela) {
        jugadoresNecesarios = 9;
        mensaje = `⚖️ Con reglas de pasarela se necesitan al menos ${jugadoresNecesarios} jugadores para garantizar tiempo mínimo de juego.`;
    } else if (periodos === '6' && !reglasPasarela) {
        jugadoresNecesarios = 5;
        mensaje = `⚠️ Se necesitan al menos ${jugadoresNecesarios} jugadores para un partido de 6 períodos.`;
    }
    
    mensajeValidacion.innerHTML = mensaje;
    mensajeValidacion.style.backgroundColor = '#fff3cd';
    mensajeValidacion.style.color = '#856404';
    mensajeValidacion.style.border = '1px solid #856404';
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
    
    mostrarAsignacionPeriodos();
}

function mostrarAsignacionPeriodos() {
    const periodos = document.querySelector('input[name="periodos"]:checked').value;
    const periodosContainer = document.getElementById('periodosContainer');
    
    periodosContainer.innerHTML = '';
    
    for (let i = 1; i <= parseInt(periodos); i++) {
        const periodoCard = document.createElement('div');
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
            
            <div id="jugadores-periodo-${i}" 
                 style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
            </div>
        `;
        
        periodosContainer.appendChild(periodoCard);
    }
    
    cargarJugadoresEnPeriodos();
}

function cargarJugadoresEnPeriodos() {
    const jugadoresSeleccionados = document.querySelectorAll('.jugador-card.selected');
    const periodos = document.querySelector('input[name="periodos"]:checked').value;
    
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
            
            // Verificar si este jugador estaba asignado a este período
            let estabaAsignado = false;
            if (partidoData.jugadoresPorPeriodo[periodo]) {
                estabaAsignado = partidoData.jugadoresPorPeriodo[periodo].some(jp => jp.jugador_id == jugadorId);
            }
            
            jugadorPeriodoCard.onclick = () => toggleJugadorPeriodo(jugadorPeriodoCard, periodo, jugadorId);
            
            jugadorPeriodoCard.innerHTML = `
                <div style="font-weight: bold; margin-bottom: 4px;">${numero}</div>
                <div style="font-size: 10px;">${nombre.split(' ')[0]}</div>
                <input type="checkbox" name="periodo_${periodo}_jugadores[]" value="${jugadorId}" 
                       ${estabaAsignado ? 'checked' : ''} style="display: none;">
            `;
            
            if (estabaAsignado) {
                jugadorPeriodoCard.classList.add('selected-periodo');
            }
            
            container.appendChild(jugadorPeriodoCard);
        });
        
        // Actualizar contador
        const seleccionados = document.querySelectorAll(`#jugadores-periodo-${periodo} .jugador-periodo-card.selected-periodo`).length;
        document.getElementById(`contador-periodo-${periodo}`).textContent = `${seleccionados}/5 jugadores`;
    }
}

function toggleJugadorPeriodo(card, periodo, jugadorId) {
    const checkbox = card.querySelector('input[type="checkbox"]');
    const contador = document.getElementById(`contador-periodo-${periodo}`);
    
    // Verificar regla de pasarela antes de seleccionar
    if (!card.classList.contains('selected-periodo')) {
        if (document.getElementById('reglas_pasarela').checked && periodo <= 5) {
            if (!verificarReglaPasarela(jugadorId, periodo)) {
                return;
            }
        }
    }
    
    if (card.classList.contains('selected-periodo')) {
        card.classList.remove('selected-periodo');
        checkbox.checked = false;
    } else {
        const jugadoresEnPeriodo = document.querySelectorAll(`#jugadores-periodo-${periodo} .jugador-periodo-card.selected-periodo`);
        if (jugadoresEnPeriodo.length >= 5) {
            alert('⚠️ Ya has seleccionado 5 jugadores para este período.');
            return;
        }
        
        card.classList.add('selected-periodo');
        checkbox.checked = true;
    }
    
    const seleccionados = document.querySelectorAll(`#jugadores-periodo-${periodo} .jugador-periodo-card.selected-periodo`).length;
    contador.textContent = `${seleccionados}/5 jugadores`;
    
    // Actualizar bloqueos después del cambio
    if (document.getElementById('reglas_pasarela').checked) {
        setTimeout(() => {
            actualizarBloqueosPasarela();
        }, 100);
    }
}

function verificarReglaPasarela(jugadorId, periodoActual) {
    // Solo aplicar en los primeros 5 períodos
    if (periodoActual > 5) return true;
    
    // Si ya está seleccionado en el período actual, permitir deselección
    const jugadorCardActual = document.querySelector(`#jugadores-periodo-${periodoActual} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
    if (jugadorCardActual && jugadorCardActual.classList.contains('selected-periodo')) {
        return true;
    }
    
    // Obtener todos los períodos donde este jugador está seleccionado
    const periodosJugador = [];
    for (let p = 1; p <= 5; p++) {
        const jugadorCard = document.querySelector(`#jugadores-periodo-${p} .jugador-periodo-card[data-jugador-id="${jugadorId}"]`);
        if (jugadorCard && jugadorCard.classList.contains('selected-periodo')) {
            periodosJugador.push(p);
        }
    }
    
    // Simular añadir el período actual
    const nuevosPeriodos = [...periodosJugador, periodoActual].sort((a, b) => a - b);
    
    // Verificar si hay tres períodos consecutivos
    for (let i = 0; i < nuevosPeriodos.length - 2; i++) {
        if (nuevosPeriodos[i + 1] === nuevosPeriodos[i] + 1 && 
            nuevosPeriodos[i + 2] === nuevosPeriodos[i] + 2) {
            
            const nombreJugador = jugadorCardActual.querySelector('div:nth-child(2)').textContent;
            alert(`⚖️ Regla de pasarela: ${nombreJugador} no puede jugar tres períodos consecutivos en los primeros 5 períodos.

Períodos ya asignados: ${periodosJugador.join(', ')}
Período que intentas asignar: ${periodoActual}

Esto crearía una secuencia de 3 períodos consecutivos: ${nuevosPeriodos[i]}, ${nuevosPeriodos[i + 1]}, ${nuevosPeriodos[i + 2]}`);
            return false;
        }
    }
    
    return true;
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
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    toggleReglasPasarela();
    
    document.querySelectorAll('input[name="periodos"]').forEach(radio => {
        radio.addEventListener('change', toggleReglasPasarela);
    });
    
    document.getElementById('equipo_visitante').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
});
</script>

</body>
</html>
