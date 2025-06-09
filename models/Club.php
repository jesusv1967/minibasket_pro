<?php
require_once 'config/database.php';

class Club {
    private $conn;
    private $table_name = "club";

    public $id;
    public $nombre;
    public $logotipo;
    public $created_at;
    public $updated_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Obtener datos del club
    public function obtenerDatos() {
        $query = "SELECT * FROM " . $this->table_name . " LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->logotipo = $row['logotipo'];
            return true;
        }
        return false;
    }

    // Actualizar datos del club
    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre = :nombre, logotipo = :logotipo, updated_at = NOW() 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->logotipo = htmlspecialchars(strip_tags($this->logotipo));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind de parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':logotipo', $this->logotipo);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Crear club si no existe
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nombre, logotipo, created_at) 
                 VALUES (:nombre, :logotipo, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->logotipo = htmlspecialchars(strip_tags($this->logotipo));
        
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':logotipo', $this->logotipo);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
