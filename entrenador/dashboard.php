<?php
session_start();

// Verificar que es entrenador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entrenador') {
   header('Location: ../login.php');
   exit();
}

// Cargar configuración del club
require_once __DIR__ . '/../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();

$page_title = 'Dashboard Entrenador - ' . $clubConfig->getNombre();

?>
<?php include __DIR__ . '/../includes/club_header.php'; ?>

<?php
// Obtener nombre completo del entrenador
$nombre_entrenador = '';
if (isset($_SESSION['nombre_completo']) && !empty(trim($_SESSION['nombre_completo']))) {
    $nombre_entrenador = trim($_SESSION['nombre_completo']);
} elseif (isset($_SESSION['nombre']) && isset($_SESSION['apellidos'])) {
    $nombre_entrenador = trim($_SESSION['nombre'] . ' ' . $_SESSION['apellidos']);
} else {
    $nombre_entrenador = $_SESSION['username'] ?? 'Entrenador';
}

// Renderizar header
$clubConfig->renderHeader(
    $clubConfig->getNombre(),
    'Panel de Entrenador',
    'Bienvenido, ' . $nombre_entrenador,
    '../logout.php'
);
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8 fallback-container" style="max-width: 1200px; margin: 0 auto; padding: 32px 16px;">
   
   <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fallback-grid" style="display: grid; grid-template-columns: 1fr; gap: 32px;">
       
       <!-- Mis Equipos -->
       <?php
// Obtener equipos del entrenador
require_once __DIR__ . '/../models/Equipo.php';
$equipoModel = new Equipo();

// Verificar si existe el campo temporada
$campo_temporada_existe = false;
try {
    $check_field = $equipoModel->conn->query("SHOW COLUMNS FROM equipos LIKE 'temporada'");
    $campo_temporada_existe = $check_field->rowCount() > 0;
} catch (PDOException $e) {
    echo "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4' role='alert'>";
    echo "<p>Error al verificar el campo temporada: " . $e->getMessage() . "</p>";
    echo "</div>";
}

// Si no existe el campo temporada, mostrar mensaje para ejecutar actualización
if (!$campo_temporada_existe) {
    echo "<div class='bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4' role='alert'>";
    echo "<h4 class='font-bold'>⚠️ Actualización requerida</h4>";
    echo "<p>Es necesario actualizar la base de datos para añadir el campo temporada.</p>";
    echo "<p><a href='../ejecutar_actualizacion_equipos_temporada.php' class='text-blue-600 hover:underline'>Ejecutar actualización</a></p>";
    echo "</div>";
}

// Obtener datos según si existe el campo temporada
if ($campo_temporada_existe) {
    // Obtener temporadas disponibles
    $query_temporadas = "SELECT DISTINCT temporada FROM equipos WHERE entrenador_id = :entrenador_id ORDER BY temporada DESC";
    $stmt_temporadas = $equipoModel->conn->prepare($query_temporadas);
    $stmt_temporadas->bindParam(':entrenador_id', $_SESSION['user_id']);
    $stmt_temporadas->execute();
    $temporadas = $stmt_temporadas->fetchAll(PDO::FETCH_ASSOC);

    // Temporada seleccionada (por defecto la más reciente)
    $temporada_actual = isset($_GET['temporada']) ? $_GET['temporada'] : (count($temporadas) > 0 ? $temporadas[0]['temporada'] : date('Y'));

    // Obtener equipos de la temporada seleccionada
    $query_equipos = "SELECT e.*, 
                             (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.activo = 1) as total_jugadores
                      FROM equipos e 
                      WHERE e.entrenador_id = :entrenador_id 
                      AND e.temporada = :temporada 
                      AND e.activo = 1 
                      ORDER BY e.categoria, e.nombre";
    $stmt_equipos = $equipoModel->conn->prepare($query_equipos);
    $stmt_equipos->bindParam(':entrenador_id', $_SESSION['user_id']);
    $stmt_equipos->bindParam(':temporada', $temporada_actual);
    $stmt_equipos->execute();
} else {
    // Consulta alternativa sin filtro de temporada
    $temporadas = [];
    $temporada_actual = date('Y');
    
    $query_equipos = "SELECT e.*, 
                             (SELECT COUNT(*) FROM jugadores j WHERE j.equipo_id = e.id AND j.activo = 1) as total_jugadores
                      FROM equipos e 
                      WHERE e.entrenador_id = :entrenador_id 
                      AND e.activo = 1 
                      ORDER BY e.categoria, e.nombre";
    $stmt_equipos = $equipoModel->conn->prepare($query_equipos);
    $stmt_equipos->bindParam(':entrenador_id', $_SESSION['user_id']);
    $stmt_equipos->execute();
}

$equipos = $stmt_equipos->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Mis Equipos -->
<div class="bg-white rounded-xl shadow-lg p-6 fallback-card" style="background: white; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 24px;">
    <div class="flex justify-between items-center mb-6" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 class="text-2xl font-bold text-gray-800" style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin: 0;">
    Equipos de <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? $_SESSION['username'] ?? 'Entrenador'); ?>
</h2>
        
        <!-- Filtro de temporada -->
        <?php if ($campo_temporada_existe && count($temporadas) > 0): ?>
        <div class="flex items-center space-x-2" style="display: flex; align-items: center; gap: 8px;">
            <label for="temporada" class="text-sm font-medium text-gray-700" style="font-size: 0.875rem; font-weight: 500; color: #374151;">Temporada:</label>
            <select id="temporada" onchange="cambiarTemporada(this.value)" 
                    class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-club-primario focus:border-club-primario"
                    style="border: 1px solid #d1d5db; border-radius: 6px; padding: 4px 12px; font-size: 0.875rem;">
                <?php foreach ($temporadas as $temp): ?>
                    <option value="<?php echo htmlspecialchars($temp['temporada']); ?>" 
                            <?php echo $temp['temporada'] == $temporada_actual ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($temp['temporada']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php elseif ($campo_temporada_existe): ?>
        <div class="text-sm text-gray-500" style="font-size: 0.875rem; color: #6b7280;">
            Temporada: <?php echo $temporada_actual; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="space-y-4" style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (count($equipos) > 0): ?>
            <?php foreach ($equipos as $equipo): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; transition: background-color 0.3s;">
                    <div class="flex justify-between items-center" style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800" style="font-size: 1.125rem; font-weight: 600; color: #1f2937; margin: 0 0 4px 0;"><?php echo htmlspecialchars($equipo['nombre']); ?></h3>
                            <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563; margin: 0;">
                                Categoría: <span class="font-medium" style="font-weight: 500;"><?php echo htmlspecialchars($equipo['categoria']); ?></span>
                                | Jugadores: <span class="font-medium" style="font-weight: 500;"><?php echo $equipo['total_jugadores']; ?></span>
                                <?php if ($campo_temporada_existe && isset($equipo['temporada'])): ?>
                                | Temporada: <span class="font-medium" style="font-weight: 500;"><?php echo htmlspecialchars($equipo['temporada']); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex space-x-2" style="display: flex; gap: 8px;">
                            <a href="gestionar_jugadores.php?equipo_id=<?php echo $equipo['id']; ?>" 
                               class="bg-club-primario text-white px-3 py-1 rounded text-sm hover:opacity-90 transition-opacity"
                               style="background: var(--color-primario); color: white; padding: 4px 12px; border-radius: 4px; font-size: 0.875rem; text-decoration: none; transition: opacity 0.3s;">
                                Ver Jugadores
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8" style="text-align: center; padding: 32px 0;">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 64px; height: 64px; color: #9ca3af; margin: 0 auto 16px auto;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2" style="font-size: 1.125rem; font-weight: 500; color: #111827; margin-bottom: 8px;">No tienes equipos asignados</h3>
                <p class="text-gray-500" style="color: #6b7280; margin: 0;">
                    <?php if ($campo_temporada_existe): ?>
                        Para la temporada <?php echo $temporada_actual; ?> no tienes equipos asignados.
                    <?php else: ?>
                        No tienes equipos asignados actualmente.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function cambiarTemporada(temporada) {
    window.location.href = '?temporada=' + encodeURIComponent(temporada);
}
</script>

       <!-- Acciones Rápidas -->
       <div class="bg-white rounded-xl shadow-lg p-6 fallback-card" style="background: white; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 24px;">
           <h2 class="text-2xl font-bold text-gray-800 mb-6" style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin-bottom: 24px;">Acciones Rápidas</h2>
           <div class="space-y-4" style="display: flex; flex-direction: column; gap: 16px;">
               
               <a href="gestionar_jugadores.php" class="block bg-green-100 hover:bg-green-200 p-6 rounded-xl transition-all fallback-card" style="display: block; background: #dcfce7; padding: 24px; border-radius: 12px; transition: all 0.3s; text-decoration: none;">
                   <div class="flex items-center" style="display: flex; align-items: center;">
                       <svg class="w-12 h-12 text-club-acento mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-acento); margin-right: 16px;">
                           <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                       </svg>
                       <div>
                           <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Gestionar Jugadores</h3>
                           <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Añadir y administrar jugadores de tus equipos</p>
                       </div>
                   </div>
               </a>
               
               <a href="gestionar_partidos.php" class="block bg-blue-100 hover:bg-blue-200 p-6 rounded-xl transition-all fallback-card" style="display: block; background: #dbeafe; padding: 24px; border-radius: 12px; transition: all 0.3s; text-decoration: none;">
                   <div class="flex items-center" style="display: flex; align-items: center;">
                       <svg class="w-12 h-12 text-club-secundario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-secundario); margin-right: 16px;">
                           <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                       </svg>
                       <div>
                           <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Gestionar Partidos</h3>
                           <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Programar y gestionar partidos de tus equipos</p>
                       </div>
                   </div>
               </a>

               <a href="calendario.php" class="block bg-orange-100 hover:bg-orange-200 p-6 rounded-xl transition-all fallback-card" style="display: block; background: #fed7aa; padding: 24px; border-radius: 12px; transition: all 0.3s; text-decoration: none;">
                   <div class="flex items-center" style="display: flex; align-items: center;">
                       <svg class="w-12 h-12 text-club-primario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-primario); margin-right: 16px;">
                           <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                       </svg>
                       <div>
                           <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Calendario</h3>
                           <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Ver entrenamientos y partidos programados</p>
                       </div>
                   </div>
               </a>
           </div>
       </div>
   </div>

   <!-- Responsive Grid para móviles -->
   <style>
       @media (max-width: 1024px) {
           .fallback-grid {
               grid-template-columns: 1fr !important;
           }
       }
       
       @media (max-width: 768px) {
           .fallback-container {
               padding: 16px !important;
           }
           
           .fallback-card {
               padding: 16px !important;
           }
           
           .fallback-card h2 {
               font-size: 1.25rem !important;
           }
           
           .fallback-card h3 {
               font-size: 1.125rem !important;
           }
       }
   </style>
</main>
</body>
</html>
