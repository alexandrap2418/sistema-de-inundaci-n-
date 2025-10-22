<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once "db.php";

// Solo mostrar reseñas visibles
$sql = "SELECT * FROM reviews WHERE visible = 1 ORDER BY created_at DESC";
$result = $conn->query($sql);

$resenas = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $resenas[] = $row;
    }
}

echo json_encode($resenas); 