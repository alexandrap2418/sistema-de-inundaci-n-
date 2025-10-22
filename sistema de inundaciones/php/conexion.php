<?php
// conexion.php - Configuración de conexión al sistema de monitoreo de inundaciones

// CONFIGURACIÓN BASE DE DATOS
define('DB_HOST', 'localhost');
define('DB_NAME', 'inundaciones_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambiar si usas contraseña en MySQL

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

$pdo = null;

// Obtener conexión
function getConnection() {
    global $pdo;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $GLOBALS['pdo_options']);
            $pdo->query("SELECT 1"); // test
        } catch (PDOException $e) {
            error_log("Error de conexión: " . $e->getMessage());
            throw new Exception("Error de conexión: " . $e->getMessage());
        }
    }
    return $pdo;
}

function closeConnection() {
    global $pdo;
    $pdo = null;
}

// FUNCIONES DE USUARIOS

function usuarioExistePorCorreo($correo) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        error_log("Error al verificar usuario: " . $e->getMessage());
        return false;
    }
}

function crearUsuario($datos) {
    try {
        $pdo = getConnection();
        $sql = "INSERT INTO usuarios (nombre, correo, password_hash, creado_en) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $datos['nombre'],
            $datos['correo'],
            $datos['password_hash']
        ]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error al crear usuario: " . $e->getMessage());
        return false;
    }
}

function obtenerUsuarioPorCorreo($correo) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error al obtener usuario: " . $e->getMessage());
        return false;
    }
}

// FUNCIONES DE REPORTES


function crearReporte($datos) {
    try {
        $pdo = getConnection();
        $sql = "INSERT INTO reportes (usuario_id, ubicacion, nivel_agua, descripcion, fecha_reporte)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $datos['usuario_id'],
            $datos['ubicacion'],
            $datos['nivel_agua'],
            $datos['descripcion']
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error al crear reporte: " . $e->getMessage());
        return false;
    }
}

function listarReportes($limite = 50) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT r.*, u.nombre AS autor FROM reportes r JOIN usuarios u ON r.usuario_id = u.id ORDER BY r.fecha_reporte DESC LIMIT ?");
        $stmt->bindValue(1, (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error al listar reportes: " . $e->getMessage());
        return [];
    }
}
// TEST DE CONEXIÓN


if (basename($_SERVER['PHP_SELF']) === 'conexion.php') {
    header('Content-Type: application/json');
    try {
        $pdo = getConnection();
        echo json_encode([
            'success' => true,
            'message' => '✅ Conexión a base de datos exitosa',
            'base_de_datos' => DB_NAME
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Error de conexión: ' . $e->getMessage()
        ]);
    }
}
?>
