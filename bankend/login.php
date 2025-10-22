<?php
// Configurar encabezados CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Cambiar a dominio específico en producción
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight (CORS pre-request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir archivo de conexión a la base de datos
include "db.php";

// Leer cuerpo del POST
$data = json_decode(file_get_contents("php://input"));

// Verificar si se enviaron los campos necesarios
if (!isset($data->username) || !isset($data->password)) {
    echo json_encode([
        "success" => false,
        "message" => "Faltan datos: username o password"
    ]);
    http_response_code(400); // Bad Request
    exit();
}

$username = $data->username;
$password = md5($data->password); // Usa MD5 solo si tu base de datos ya lo tiene así

// Consulta con prepared statement para prevenir SQL Injection
$sql = "SELECT * FROM usuario WHERE username=? AND password=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result-> fetch_assoc();
    echo json_encode([
        "success" => true,
        "message" => "Login correcto",
        "rol" => $user["rol"]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Usuario o contraseña incorrectos"
    ]);
}

$stmt->close();
$conn->close();
?>