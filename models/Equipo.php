<?php
require_once __DIR__ . '/../config/database.php';

class Equipo {
    public $conn;
    private $table_name = "equipos";

    public $id;
    public $nombre;
    public $categoria;
    public $entrenador_id;
    public $activo;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear equipo
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nombre, categoria, entrenador_id, activo, created_at) 
                 VALUES (:nombre, :categoria, :entrenador_id, :activo, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        
        // Bind de parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':entrenador_id', $this->entrenador_id);
        $stmt->bindParam(':activo', $this->activo);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si el equipo ya existe
    public function equipoExiste() {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE nombre = :nombre AND categoria = :categoria";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Obtener todos los equipos
    public function obtenerTodos() {
        $query = "SELECT e.*, en.nombre as entrenador_nombre, en.apellidos as entrenador_apellidos 
                 FROM " . $this->table_name . " e 
                 LEFT JOIN entrenadores en ON e.entrenador_id = en.id 
                 ORDER BY e.categoria, e.nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener equipos por entrenador
    public function obtenerPorEntrenador($entrenador_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE entrenador_id = :entrenador_id AND activo = 1 
                 ORDER BY categoria, nombre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':entrenador_id', $entrenador_id);
        $stmt->execute();
        return $stmt;
    }

    // Actualizar equipo
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre = :nombre, categoria = :categoria, 
                     entrenador_id = :entrenador_id, activo = :activo, 
                     updated_at = NOW() 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->categoria = htmlspecialchars(strip_tags($this->categoria));
        
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':categoria', $this->categoria);
        $stmt->bindParam(':entrenador_id', $this->entrenador_id);
        $stmt->bindParam(':activo', $this->activo);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
?>
