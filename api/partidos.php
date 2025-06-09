<?php
// api/partidos.php
session_start(); // Siempre iniciar sesión
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Entrenador'])) {
    header("Location: acceso_denegado.php");
    exit();
}
header('Content-Type: application/json');
require_once '../config/db.php'; // Adjust path as necessary

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'listar':
        listarPartidos($conn);
        break;
    case 'agregar_partido': // Changed action name to be more specific
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            agregarPartido($conn);
        } else {
            echo json_encode(['success' => false, 'message' => 'Método no permitido para agregar partido. Se requiere POST.']);
        }
        break;
    // TODO: Implement 'actualizar_partido' and 'eliminar_partido' actions.
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida para partidos.']);
}

/**
 * Fetches and returns a list of all matches from the database.
 * @param mysqli $conn The database connection object.
 */
function listarPartidos($conn) {
    // Selects relevant fields for matches, ordered by date.
    $sql = "SELECT id, fecha, equipo_local, equipo_visitante, resultado, fecha_registro FROM partidos ORDER BY fecha DESC, id DESC";
    $result = $conn->query($sql);
    $partidos = [];

    if ($result) {
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $partidos[] = $row;
            }
        }
        echo json_encode(['success' => true, 'data' => $partidos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al obtener partidos: ' . $conn->error]);
    }
}

/**
 * Adds a new match to the database.
 * @param mysqli $conn The database connection object.
 */
function agregarPartido($conn) {
    // Retrieve data from POST request.
    $fecha = $_POST['fecha'] ?? null;
    $equipo_local = $_POST['equipo_local'] ?? null;
    $equipo_visitante = $_POST['equipo_visitante'] ?? null;
    $resultado = $_POST['resultado'] ?? ''; // Default to empty string

    // --- Basic Validation ---
    if (empty($fecha) || empty($equipo_local) || empty($equipo_visitante)) {
        echo json_encode(['success' => false, 'message' => 'Fecha, equipo local y equipo visitante son requeridos.']);
        return;
    }
    // Validate date format (YYYY-MM-DD)
    if (!preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
        return;
    }


    // --- Prepare SQL Statement ---
    // Using prepared statements for security.
    $stmt = $conn->prepare("INSERT INTO partidos (fecha, equipo_local, equipo_visitante, resultado, fecha_registro) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn->error]);
        return;
    }

    // Bind parameters. 'ssss' means four strings.
    $stmt->bind_param("ssss", $fecha, $equipo_local, $equipo_visitante, $resultado);

    // --- Execute Statement and Respond ---
    if ($stmt->execute()) {
        $newPartidoId = $stmt->insert_id;
        // Fetch the newly added match to return it.
        $result = $conn->query("SELECT id, fecha, equipo_local, equipo_visitante, resultado, fecha_registro FROM partidos WHERE id = $newPartidoId");
        if ($result && $newPartido = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'message' => 'Partido añadido con éxito.', 'data' => $newPartido]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Partido añadido, pero error al recuperar datos. ID: ' . $newPartidoId]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al añadir partido: ' . $stmt->error]);
    }
    $stmt->close();
}


$conn->close();
?>
