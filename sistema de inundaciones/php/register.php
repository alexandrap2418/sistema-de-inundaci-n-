<?php
// register.php - Procesar registro de usuarios con MySQLi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// INCLUIR database.php
require_once __DIR__ . '/database.php';

// Solo permitir POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

function logError($message, $context = []) {
    error_log("REGISTER ERROR: " . $message . " Context: " . json_encode($context));
}

try {
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    logError("Input recibido", ['input' => $input]);
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
    
    if (!$data) {
        throw new Exception('No se recibieron datos válidos');
    }
    
    logError("Datos decodificados", $data);
    
    // Validar campos requeridos (SIN USERNAME)
    $required_fields = ['nombre', 'correo', 'contrasena', 'edad'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("El campo '$field' es requerido");
        }
    }
    
    // Sanitizar datos
    $nombre = trim($data['nombre']);
    $correo = trim(strtolower($data['correo']));
    $contrasena = trim($data['contrasena']);
    $edad = intval($data['edad']);
    
    // Validaciones adicionales
    if (strlen($nombre) < 2) {
        throw new Exception('El nombre debe tener al menos 2 caracteres');
    }
    
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido');
    }
    
    if (strlen($contrasena) < 8) {
        throw new Exception('La contraseña debe tener al menos 8 caracteres');
    }
    
    if ($edad < 13 || $edad > 120) {
        throw new Exception('La edad debe estar entre 13 y 120 años');
    }
    
    logError("Validaciones pasadas", ['correo' => $correo, 'nombre' => $nombre]);
    
    // Crear instancia de la base de datos
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception('No se pudo obtener la conexión a la base de datos');
        }
        
        if (!$conn->ping()) {
            throw new Exception('La conexión a la base de datos no está activa');
        }
        
        logError("Conexión establecida", ['host' => $database->host, 'db' => $database->db_name]);
        
    } catch (Exception $e) {
        logError("Error de conexión", ['error' => $e->getMessage()]);
        throw new Exception('Error de conexión a la base de datos: ' . $e->getMessage());
    }
    
    // Verificar si el correo ya existe
    try {
        $stmt = $conn->prepare("SELECT ID_Usuario FROM usuario WHERE Correo = ?");
        
        if (!$stmt) {
            logError("Error preparando consulta SELECT correo", ['error' => $conn->error]);
            throw new Exception('Error preparando consulta de verificación: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $correo);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            logError("Error ejecutando SELECT correo", ['error' => $error]);
            throw new Exception('Error ejecutando consulta de verificación: ' . $error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            throw new Exception('Ya existe un usuario con este correo electrónico');
        }
        
        $stmt->close();
        logError("Verificación de correo completada", ['correo' => $correo]);
        
    } catch (Exception $e) {
        logError("Error en verificación de correo", ['error' => $e->getMessage()]);
        throw $e;
    }
    
    // Hash de la contraseña
    $hashed_password = password_hash($contrasena, PASSWORD_BCRYPT);
    logError("Password hasheada");
    
    // Insertar nuevo usuario (SIN USERNAME)
    try {
        $stmt = $conn->prepare("INSERT INTO usuario (Nombre, Correo, Contrasena, Edad) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            logError("Error preparando INSERT", ['error' => $conn->error]);
            throw new Exception('Error preparando consulta de inserción: ' . $conn->error);
        }
        
        $stmt->bind_param("sssi", $nombre, $correo, $hashed_password, $edad);
        
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            logError("Error ejecutando INSERT", ['error' => $error]);
            throw new Exception('Error al crear el usuario: ' . $error);
        }
        
        $user_id = $conn->insert_id;
        $stmt->close();
        
        logError("Usuario creado exitosamente", ['user_id' => $user_id]);
        
    } catch (Exception $e) {
        logError("Error en inserción", ['error' => $e->getMessage()]);
        throw new Exception('Error al crear el usuario: ' . $e->getMessage());
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'data' => [
            'user_id' => $user_id,
            'nombre' => $nombre,
            'correo' => $correo
        ]
    ]);

} catch (Exception $e) {
    // Log del error completo
    logError("ERROR FINAL", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
?>