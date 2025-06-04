<?php
$servername = "localhost";
$username = "root";
$password = "123456"; 
$database = "Constructora";

$conexion = new mysqli($servername, $username, $password, $database);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
