<?php
// login.php - Sistema de autenticación con MySQLi
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Use POST.'
    ]);
    exit();
}

require_once __DIR__ . '/database.php';

function logError($message, $context = []) {
    error_log("LOGIN ERROR: " . $message . " Context: " . json_encode($context));
}

try {
    // Obtener datos JSON
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
    
    // Validar campos
    if (!isset($data['email']) || !isset($data['password'])) {
        throw new Exception('Email y contraseña son requeridos');
    }
    
    $email = trim($data['email']);
    $password = trim($data['password']);
    
    if (empty($email) || empty($password)) {
        throw new Exception('Email y contraseña no pueden estar vacíos');
    }
    
    logError("Intento de login", ['email' => $email]);
    
    // Lista de administradores (acceso directo sin BD)
    $admins = [
        [
            'email' => 'brainermontoya383@gmail.com',
            'username' => 'admin',
            'password' => '1128282968bb',
            'name' => 'Brainer Montoya',
            'role' => 'Administrador Principal'
        ],
        [
            'email' => 'henaosebas315@gmail.com',
            'username' => 'owner',
            'password' => 'Nain456',
            'name' => 'SebasNao',
            'role' => 'Administrador Iniciativo'
        ],
        [
            'email' => 'tamara@gotmail.com',
            'username' => 'admin3',
            'password' => '1234',
            'name' => 'Admin Terciario',
            'role' => 'Moderador'
        ]
    ];
    
    // Verificar si es administrador
    foreach ($admins as $admin) {
        if (($email === $admin['email'] || $email === $admin['username']) && $password === $admin['password']) {
            logError("Login de administrador exitoso", ['admin' => $admin['name']]);
            
            echo json_encode([
                'success' => true,
                'userType' => 'admin',
                'message' => 'Bienvenido, ' . $admin['name'],
                'data' => [
                    'name' => $admin['name'],
                    'role' => $admin['role'],
                    'email' => $admin['email'],
                    'initials' => implode('', array_map(fn($n) => $n[0], explode(' ', $admin['name'])))
                ]
            ]);
            exit();
        }
    }
    
    // Si no es admin, verificar en la base de datos
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception('No se pudo conectar a la base de datos');
        }
        
        // Buscar usuario por correo
        $stmt = $conn->prepare("SELECT ID_Usuario, Nombre, Correo, Contrasena, Edad FROM usuario WHERE Correo = ?");
        
        if (!$stmt) {
            throw new Exception('Error preparando consulta: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception('Error ejecutando consulta: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            throw new Exception('No existe una cuenta con este correo. Por favor regístrate primero.');
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Verificar contraseña
        if (!password_verify($password, $user['Contrasena'])) {
            throw new Exception('Contraseña incorrecta');
        }
        
        // Login exitoso
        logError("Login de usuario exitoso", ['user_id' => $user['ID_Usuario']]);
        
        echo json_encode([
            'success' => true,
            'userType' => 'user',
            'message' => 'Bienvenido, ' . $user['Nombre'],
            'data' => [
                'user_id' => $user['ID_Usuario'],
                'name' => $user['Nombre'],
                'email' => $user['Correo'],
                'edad' => $user['Edad']
            ]
        ]);
        
    } catch (Exception $e) {
        logError("Error en verificación de usuario", ['error' => $e->getMessage()]);
        throw $e;
    }
    
} catch (Exception $e) {
    logError("ERROR FINAL", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>