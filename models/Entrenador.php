<?php
require_once 'config/database.php';

class Entrenador {
    private $conn;
    private $table_name = "entrenadores";

    public $id;
    public $nombre;
    public $apellidos;
    public $email;
    public $telefono;
    public $usuario;
    public $password;
    public $activo;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Crear entrenador
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nombre, apellidos, email, telefono, usuario, password, activo, created_at) 
                 VALUES (:nombre, :apellidos, :email, :telefono, :usuario, :password, :activo, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellidos = htmlspecialchars(strip_tags($this->apellidos));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->usuario = htmlspecialchars(strip_tags($this->usuario));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // Bind de parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':apellidos', $this->apellidos);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':telefono', $this->telefono);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':activo', $this->activo);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Verificar si el usuario ya existe
    public function usuarioExiste() {
        $query = "SELECT id FROM " . $this->table_name . " 
                 WHERE usuario = :usuario OR email = :email";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $this->usuario);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Obtener todos los entrenadores
    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nombre, apellidos";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Activar/Desactivar entrenador
    public function cambiarEstado() {
        $query = "UPDATE " . $this->table_name . " 
                 SET activo = :activo, updated_at = NOW() 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':activo', $this->activo);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
?>
