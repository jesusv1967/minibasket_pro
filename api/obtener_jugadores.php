<?php
session_start();
require_once '../config/database.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'entrenador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if (!isset($_GET['equipo_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de equipo requerido']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verificar que el equipo pertenece al entrenador
    $query_verificar = "SELECT id FROM equipos WHERE id = :equipo_id AND entrenador_id = :entrenador_id";
    $stmt_verificar = $conn->prepare($query_verificar);
    $stmt_verificar->bindParam(':equipo_id', $_GET['equipo_id']);
    $stmt_verificar->bindParam(':entrenador_id', $_SESSION['user_id']);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->rowCount() === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Equipo no autorizado']);
        exit();
    }
    
    // Obtener jugadores del equipo
    $query = "SELECT id, nombre, apellidos, numero_camiseta, posicion 
             FROM jugadores 
             WHERE equipo_id = :equipo_id AND activo = 1 
             ORDER BY numero_camiseta";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':equipo_id', $_GET['equipo_id']);
    $stmt->execute();
    
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($jugadores);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
