<?php
// api/jugadores.php
session_start(); // Siempre iniciar sesión
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Entrenador'])) {
    header("Location: acceso_denegado.php");
    exit();
}
// Set the content type of the response to JSON.
header('Content-Type: application/json');
// Include the database connection configuration.
require_once '../config/db.php'; // Adjust path as necessary

// --- Handle Actions ---
// Determine the action to perform based on the 'action' parameter in the request.
// Uses $_REQUEST to be flexible with GET or POST for 'action', though specific methods are checked later.
$action = $_REQUEST['action'] ?? ''; // Default to empty string if not set

// Route the request to the appropriate function based on the action.
switch ($action) {
    case 'listar':
        listarJugadores($conn);
        break;
    case 'agregar':
        // For adding data, we expect a POST request.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            agregarJugador($conn);
        } else {
            // Respond with an error if the request method is not POST.
            echo json_encode(['success' => false, 'message' => 'Método no permitido para agregar jugador. Se requiere POST.']);
        }
        break;
    // TODO: Implement 'actualizar' (edit) and 'eliminar' (delete) actions.
    // case 'actualizar':
    //     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //         actualizarJugador($conn);
    //     } else {
    //         echo json_encode(['success' => false, 'message' => 'Método no permitido para actualizar jugador. Se requiere POST.']);
    //     }
    //     break;
    // case 'eliminar':
    //      if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Or DELETE, but forms typically use POST
    //         eliminarJugador($conn);
    //     } else {
    //         echo json_encode(['success' => false, 'message' => 'Método no permitido para eliminar jugador.']);
    //     }
    //     break;
    default:
        // Respond with an error if the action is not recognized.
        echo json_encode(['success' => false, 'message' => 'Acción no válida para jugadores.']);
}

/**
 * Fetches and returns a list of all players from the database.
 * @param mysqli $conn The database connection object.
 */
function listarJugadores($conn) {
    $sql = "SELECT id, nombre, edad, posicion, equipo, fecha_registro FROM jugadores ORDER BY nombre ASC";
    $result = $conn->query($sql);
    $jugadores = [];

    if ($result) {
        if ($result->num_rows > 0) {
            // Fetch each row and add it to the $jugadores array.
            while ($row = $result->fetch_assoc()) {
                $jugadores[] = $row;
            }
        }
        // Respond with success and the list of players (can be empty).
        echo json_encode(['success' => true, 'data' => $jugadores]);
    } else {
        // Respond with an error if the query failed.
        echo json_encode(['success' => false, 'message' => 'Error al obtener jugadores: ' . $conn->error]);
    }
}

/**
 * Adds a new player to the database.
 * @param mysqli $conn The database connection object.
 */
function agregarJugador($conn) {
    // Retrieve data from the POST request.
    $nombre = $_POST['nombre'] ?? null;
    $edad = $_POST['edad'] ?? null;
    $posicion = $_POST['posicion'] ?? ''; // Default to empty string if not provided
    $equipo = $_POST['equipo'] ?? '';   // Default to empty string; 'equipo' is VARCHAR in 'jugadores' table.

    // --- Basic Validation ---
    if (empty($nombre) || $nombre === null) {
        echo json_encode(['success' => false, 'message' => 'El nombre del jugador es requerido.']);
        return;
    }
    if ($edad === null || $edad === '') { // Check for empty string too, as form might send it
        echo json_encode(['success' => false, 'message' => 'La edad del jugador es requerida.']);
        return;
    }
    if (!filter_var($edad, FILTER_VALIDATE_INT) || (int)$edad <= 0) {
        echo json_encode(['success' => false, 'message' => 'La edad debe ser un número entero positivo.']);
        return;
    }

    // --- Prepare SQL Statement ---
    // Use prepared statements to prevent SQL injection vulnerabilities.
    // The 'jugadores' table has 'equipo' as VARCHAR(100).
    // This design might be temporary; a better approach would be an 'equipo_id' foreign key
    // linking to an 'equipos' table, or using the 'jugadores_equipos' junction table.
    // For now, we adhere to the current schema for `jugadores.equipo`.
    $stmt = $conn->prepare("INSERT INTO jugadores (nombre, edad, posicion, equipo, fecha_registro) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn->error]);
        return;
    }

    // Bind parameters to the prepared statement. 'siss' means String, Integer, String, String.
    $stmt->bind_param("siss", $nombre, $edad, $posicion, $equipo);

    // --- Execute Statement and Respond ---
    if ($stmt->execute()) {
        $newPlayerId = $stmt->insert_id; // Get the ID of the newly inserted player.
        // Fetch the newly added player to return it in the response.
        $result = $conn->query("SELECT id, nombre, edad, posicion, equipo, fecha_registro FROM jugadores WHERE id = $newPlayerId");
        if ($result && $newPlayer = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'message' => 'Jugador añadido con éxito.', 'data' => $newPlayer]);
        } else {
            // Should ideally not happen if insert was successful, but good to cover.
            echo json_encode(['success' => true, 'message' => 'Jugador añadido, pero error al recuperar datos. ID: ' . $newPlayerId]);
        }
    } else {
        // Respond with an error if execution failed.
        echo json_encode(['success' => false, 'message' => 'Error al añadir jugador: ' . $stmt->error]);
    }
    // Close the statement.
    $stmt->close();
}

// Close the database connection.
$conn->close();
?>
