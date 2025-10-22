<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$dbname = "sistema_de_inundacion";

//crear conexion
$conn = new mysqli($host, $user, $pass, $dbname, 3306);

//verificar conexion
if ($conn->connect_error) {
    die("Error de conexion:" . $conn->connect_error);
}

?>