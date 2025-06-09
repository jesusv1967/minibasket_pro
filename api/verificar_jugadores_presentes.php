<?php
session_start();
require_once '../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entrenador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['partido_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de partido requerido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Obtener jugadores presentes en el partido
    $query = "SELECT jugador_id FROM partido_jugadores WHERE partido_id = :partido_id AND presente = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':partido_id', $_GET['partido_id']);
    $stmt->execute();
    
    $jugadores = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode($jugadores);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
