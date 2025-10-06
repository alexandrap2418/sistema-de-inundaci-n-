<?php
// database.php - Clase Database MySQLi con debugging mejorado
class Database {
    // Configuración de la base de datos
    public $host = 'localhost';
    public $db_name = 'latidoverde';
    public $username = 'root';
    public $password = '';
    public $charset = 'utf8mb4';
    
    private $conn;
    
    public function __construct() {
        // Headers CORS (solo si no se han enviado ya)
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Content-Type: application/json; charset=utf-8');
        }
    }
    
    public function getConnection() {
        if ($this->conn !== null) {
            return $this->conn;
        }
        
        try {
            // Habilitar reporting de errores de MySQLi
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Crear conexión
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
            
            // Verificar conexión
            if ($this->conn->connect_error) {
                error_log("Error de conexión MySQLi: " . $this->conn->connect_error);
                throw new Exception("Error de conexión: " . $this->conn->connect_error);
            }
            
            // Establecer charset
            if (!$this->conn->set_charset($this->charset)) {
                error_log("Error estableciendo charset: " . $this->conn->error);
                throw new Exception("Error estableciendo charset: " . $this->conn->error);
            }
            
            // Verificar que la base de datos existe
            $db_check = $this->conn->select_db($this->db_name);
            if (!$db_check) {
                error_log("Base de datos no encontrada: " . $this->db_name);
                throw new Exception("Base de datos '{$this->db_name}' no encontrada");
            }
            
            // Verificar que la tabla usuarios existe
            $table_check = $this->conn->query("SHOW TABLES LIKE 'usuario'");
            if (!$table_check || $table_check->num_rows === 0) {
                error_log("Tabla 'usuario' no encontrada");
                throw new Exception("La tabla 'usuario' no existe en la base de datos");
            }
            
            error_log("Conexión MySQLi establecida exitosamente");
            return $this->conn;
            
        } catch (mysqli_sql_exception $e) {
            error_log("MySQLi Exception: " . $e->getMessage());
            throw new Exception("Error de base de datos: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Database Exception: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Método para obtener información de conexión (para debugging)
    public function getConnectionInfo() {
        if ($this->conn && !$this->conn->connect_error) {
            return [
                'host' => $this->host,
                'database' => $this->db_name,
                'user' => $this->username,
                'charset' => $this->conn->character_set_name(),
                'server_info' => $this->conn->server_info,
                'server_version' => $this->conn->server_version,
                'protocol_version' => $this->conn->protocol_version
            ];
        }
        return null;
    }
    
    // Método para cerrar conexión
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
    }
    
    // Destructor
    public function __destruct() {
        $this->closeConnection();
    }
    
    // Método para verificar si una tabla existe
    public function tableExists($tableName) {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->bind_param("s", $tableName);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            return $exists;
        } catch (Exception $e) {
            error_log("Error verificando tabla: " . $e->getMessage());
            return false;
        }
    }
    
    // Método para obtener la estructura de una tabla
    public function getTableStructure($tableName) {
        try {
            $conn = $this->getConnection();
            $result = $conn->query("DESCRIBE " . $conn->real_escape_string($tableName));
            $structure = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $structure[] = $row;
                }
            }
            
            return $structure;
        } catch (Exception $e) {
            error_log("Error obteniendo estructura de tabla: " . $e->getMessage());
            return [];
        }
    }
}
?>