<?php
// Habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

require_once "db.php";

// Leer el JSON del cuerpo de la petición
$input = json_decode(file_get_contents("php://input"), true);

// Validar datos
$content = trim($input["content"] ?? '');
$name = trim($input["name"] ?? 'Anónimo');

if (!$content) {
    http_response_code(400);
    echo json_encode(["error" => "El contenido de la reseña es requerido"]);
    exit;
}

// Insertar reseña
$sql = "INSERT INTO reviews (name, content) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $name, $content);

if ($stmt->execute()) {
    $last_id = $stmt->insert_id;

    // Obtener la reseña insertada
    $sql = "SELECT * FROM reviews WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $review = $result->fetch_assoc();

    http_response_code(201);
    echo json_encode($review);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error al guardar la reseña"]);
}
?>