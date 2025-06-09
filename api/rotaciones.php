<?php
header('Content-Type: application/json');

// Incluye tu conexión a la base de datos
require_once '../includes/db.php';

if ($_GET['action'] === 'guardar') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['rotaciones']) || !is_array($input['rotaciones'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $rotaciones = $input['rotaciones'];

    try {
        $db->beginTransaction();

        foreach ($rotaciones as $periodo => $jugadores) {
            foreach ($jugadores as $posicion => $jugadorId) {
                if (!is_numeric($jugadorId)) continue;

                $stmt = $db->prepare("REPLACE INTO rotaciones (periodo, posicion, jugador_id) VALUES (?, ?, ?)");
                $stmt->execute([$periodo, $posicion, $jugadorId]);
            }
        }

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}
