<?php
session_start();

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Cargar configuración del club
require_once '../utils/ClubConfig.php';
$clubConfig = ClubConfig::getInstance();

$page_title = 'Dashboard Administrador - ' . $clubConfig->getNombre();
?>
<?php include '../includes/club_header.php'; ?>

<?php
// Renderizar header
$clubConfig->renderHeader(
    $clubConfig->getNombre(),
    'Panel de Administración',
    'Administrador: ' . ($_SESSION['nombre_completo'] ?? $_SESSION['username']),
    '../logout.php'
);
?>

<!-- Main Content -->
<main class="container mx-auto px-4 py-8 fallback-container">
    <!-- Mensaje de bienvenida -->
    <div class="bg-blue-50 border-l-4 border-club-primario p-4 mb-8 fallback-card" style="background: #eff6ff; border-left: 4px solid var(--color-primario); padding: 16px; margin-bottom: 32px;">
        <div class="flex" style="display: flex;">
            <svg class="w-5 h-5 text-club-primario mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20" style="width: 20px; height: 20px; color: var(--color-primario); margin-right: 8px; margin-top: 2px;">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="text-lg font-medium text-blue-800" style="font-size: 1.125rem; font-weight: 500; color: #1e40af; margin: 0 0 8px 0;">¡Bienvenido al Panel de Administración!</h3>
                <p class="text-blue-700" style="color: #1d4ed8; margin: 0;">Desde aquí puedes gestionar todos los aspectos de tu club de baloncesto.</p>
                <?php if ($clubConfig->tieneColoresExtraidos()): ?>
                    <p class="text-blue-600 text-sm mt-2" style="color: #2563eb; font-size: 0.875rem; margin-top: 8px;">
                        🎨 <strong>Colores personalizados activos:</strong> La interfaz usa los colores extraídos del logotipo de tu club.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 fallback-grid">
    <!-- Tarjeta Gestionar Club -->
    <a href="gestionar_club.php" class="block bg-blue-100 hover:bg-blue-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #dbeafe; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-club-primario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-primario); margin-right: 16px;">
                <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Gestionar Club</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Configura el nombre, logotipo y colores del club</p>
            </div>
        </div>
    </a>

    <!-- Tarjeta Entrenadores -->
    <a href="gestionar_entrenadores.php" class="block bg-green-100 hover:bg-green-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #dcfce7; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-club-acento mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-acento); margin-right: 16px;">
                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Entrenadores</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Gestiona los entrenadores del club</p>
            </div>
        </div>
    </a>

    <!-- Tarjeta Equipos -->
    <a href="gestionar_equipos.php" class="block bg-orange-100 hover:bg-orange-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #fed7aa; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-club-primario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-primario); margin-right: 16px;">
                <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Equipos</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Administra los equipos del club</p>
            </div>
        </div>
    </a>

    <!-- Tarjeta Jugadores -->
    <a href="gestionar_jugadores.php" class="block bg-purple-100 hover:bg-purple-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #e9d5ff; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-club-secundario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-secundario); margin-right: 16px;">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Jugadores</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Administra todos los jugadores</p>
            </div>
        </div>
    </a>

    <!-- Tarjeta Reportes -->
    <a href="reportes.php" class="block bg-indigo-100 hover:bg-indigo-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #e0e7ff; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-club-secundario mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: var(--color-secundario); margin-right: 16px;">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Reportes</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Consulta estadísticas y reportes</p>
            </div>
        </div>
    </a>

    <!-- Tarjeta Configuración -->
    <a href="configuracion.php" class="block bg-gray-100 hover:bg-gray-200 p-6 rounded-xl transition-colors fallback-card" style="display: block; background: #f3f4f6; padding: 24px; border-radius: 12px;">
        <div class="flex items-center" style="display: flex; align-items: center;">
            <svg class="w-12 h-12 text-gray-600 mr-4" fill="currentColor" viewBox="0 0 20 20" style="width: 48px; height: 48px; color: #4b5563; margin-right: 16px;">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.533 1.533 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
            </svg>
            <div>
                <h3 class="text-xl font-semibold text-gray-800" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Configuración</h3>
                <p class="text-sm text-gray-600" style="font-size: 0.875rem; color: #4b5563;">Ajustes generales del sistema</p>
            </div>
        </div>
    </a>
</div>

    <!-- Estadísticas rápidas -->
    <div class="bg-white rounded-xl shadow-lg p-6 fallback-card">
        <h2 class="text-2xl font-bold text-gray-800 mb-6" style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin-bottom: 24px;">Resumen del Club</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 fallback-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="text-center p-4 bg-blue-50 rounded-lg" style="text-align: center; padding: 16px; background: #eff6ff; border-radius: 8px;">
                <div class="text-3xl font-bold text-club-primario" id="total-entrenadores" style="font-size: 1.875rem; font-weight: bold; color: var(--color-primario);">-</div>
                <div class="text-gray-600" style="color: #4b5563;">Entrenadores</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg" style="text-align: center; padding: 16px; background: #f0fdf4; border-radius: 8px;">
                <div class="text-3xl font-bold text-club-acento" id="total-equipos" style="font-size: 1.875rem; font-weight: bold; color: var(--color-acento);">-</div>
                <div class="text-gray-600" style="color: #4b5563;">Equipos</div>
            </div>
            <div class="text-center p-4 bg-orange-50 rounded-lg" style="text-align: center; padding: 16px; background: #fff7ed; border-radius: 8px;">
                <div class="text-3xl font-bold text-club-primario" id="total-jugadores" style="font-size: 1.875rem; font-weight: bold; color: var(--color-primario);">-</div>
                <div class="text-gray-600" style="color: #4b5563;">Jugadores</div>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg" style="text-align: center; padding: 16px; background: #faf5ff; border-radius: 8px;">
                <div class="text-3xl font-bold text-club-secundario" id="total-partidos" style="font-size: 1.875rem; font-weight: bold; color: var(--color-secundario);">-</div>
                <div class="text-gray-600" style="color: #4b5563;">Partidos</div>
            </div>
        </div>
    </div>

    <!-- Actividad reciente -->
    <div class="bg-white rounded-xl shadow-lg p-6 mt-8 fallback-card" style="margin-top: 32px;">
        <h2 class="text-2xl font-bold text-gray-800 mb-6" style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin-bottom: 24px;">Actividad Reciente</h2>
        <div class="space-y-4" style="display: flex; flex-direction: column; gap: 16px;">
            <div class="flex items-center p-3 bg-gray-50 rounded-lg" style="display: flex; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                <div class="w-2 h-2 bg-club-acento rounded-full mr-3" style="width: 8px; height: 8px; background: var(--color-acento); border-radius: 50%; margin-right: 12px;"></div>
                <span class="text-gray-700" style="color: #374151;">Sistema iniciado correctamente</span>
                <span class="text-gray-500 text-sm ml-auto" style="color: #6b7280; font-size: 0.875rem; margin-left: auto;">Ahora</span>
            </div>
            <div class="flex items-center p-3 bg-gray-50 rounded-lg" style="display: flex; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                <div class="w-2 h-2 bg-club-primario rounded-full mr-3" style="width: 8px; height: 8px; background: var(--color-primario); border-radius: 50%; margin-right: 12px;"></div>
                <span class="text-gray-700" style="color: #374151;">Sesión de administrador iniciada</span>
                <span class="text-gray-500 text-sm ml-auto" style="color: #6b7280; font-size: 0.875rem; margin-left: auto;">Hace 1 min</span>
            </div>
            <?php if ($clubConfig->tieneColoresExtraidos()): ?>
            <div class="flex items-center p-3 bg-gray-50 rounded-lg" style="display: flex; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                <div class="w-2 h-2 bg-club-primario rounded-full mr-3" style="width: 8px; height: 8px; background: var(--color-primario); border-radius: 50%; margin-right: 12px;"></div>
                <span class="text-gray-700" style="color: #374151;">Colores del club aplicados automáticamente</span>
                <span class="text-gray-500 text-sm ml-auto" style="color: #6b7280; font-size: 0.875rem; margin-left: auto;">Reciente</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    // Cargar estadísticas
    document.addEventListener('DOMContentLoaded', function() {
        loadStatistics();
    });

    function loadStatistics() {
        // Por ahora, valores de ejemplo
        document.getElementById('total-entrenadores').textContent = '1';
        document.getElementById('total-equipos').textContent = '0';
        document.getElementById('total-jugadores').textContent = '0';
        document.getElementById('total-partidos').textContent = '0';
    }
</script>
</body>
</html>
