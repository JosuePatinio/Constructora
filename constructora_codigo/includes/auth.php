<?php
session_start();

function hacerLogin($conexion, $email, $password) {
    $stmt = $conexion->prepare("SELECT idempleado, nombre, contrasena FROM empleados WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();

        if (password_verify($password, $usuario['contrasena'])) {
            $_SESSION['usuario'] = [
                'idempleado' => $usuario['idempleado'],
                'nombre' => $usuario['nombre'],
                'email' => $email
            ];
            return true;
        }
    }
    return false;
}

function verificarSesion() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: ../includes/login.php");
        exit();
    }
}

function obtenerUsuario() {
    return $_SESSION['usuario'] ?? null;
}

function cerrarSesion() {
    session_unset();
    session_destroy();
}
?>