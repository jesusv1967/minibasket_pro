<?php
// Configuración de base de datos para los modelos
class Database {
    private $host = 'localhost';
    private $db_name = 'minibasket_pro'; // Cambiado de 'minibasket' a 'minibasket_pro'
    private $username = 'root';
    private $password = '1nt3l3kSYS%';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Crear DSN (Data Source Name)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Opciones de PDO
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $exception) {
            $error_message = "Error de conexión a la base de datos: " . $exception->getMessage();
            error_log($error_message);
            
            // En desarrollo, mostrar más detalles del error
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                throw new Exception($error_message . " (Código: " . $exception->getCode() . ")");
            } else {
                throw new Exception("Error de conexión a la base de datos. Contacte al administrador.");
            }
        }
        return $this->conn;
    }
    
    // Método para probar la conexión
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return [
                'success' => true,
                'message' => 'Conexión exitosa',
                'server_info' => $conn->getAttribute(PDO::ATTR_SERVER_INFO)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Método para verificar si la base de datos existe
    public function checkDatabase() {
        try {
            // Conectar sin especificar base de datos
            $dsn = "mysql:host=" . $this->host . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            
            $conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Verificar si la base de datos existe
            $stmt = $conn->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$this->db_name]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'exists' => true,
                    'message' => "La base de datos '{$this->db_name}' existe"
                ];
            } else {
                return [
                    'exists' => false,
                    'message' => "La base de datos '{$this->db_name}' no existe"
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'exists' => false,
                'message' => "Error al verificar la base de datos: " . $e->getMessage()
            ];
        }
    }
    
    // Getters para acceder a la configuración (útil para debugging)
    public function getHost() { return $this->host; }
    public function getDbName() { return $this->db_name; }
    public function getUsername() { return $this->username; }
}
?>
