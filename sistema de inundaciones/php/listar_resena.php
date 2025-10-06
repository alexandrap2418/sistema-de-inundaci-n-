<?php
require_once 'conexion.php';

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

try {
    // Obtener conexión usando tu función existente
    $pdo = getConnection();
    if (!$pdo) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Consultar reseñas activas ordenadas por fecha descendente
    $sql = "SELECT ID_Resena, nombre, contenido, calificacion, fecha 
            FROM sistema_resenas 
            WHERE estado = 'activo' 
            ORDER BY fecha DESC 
            LIMIT 50";
    
    $stmt = $pdo->query($sql);
    $resenas = $stmt->fetchAll();

    // Retornar resultado
    echo json_encode($resenas, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error al cargar las reseñas: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>