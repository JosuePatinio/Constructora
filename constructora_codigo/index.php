<?php
require_once 'includes/auth.php';
verificarSesion();

$usuario = obtenerUsuario();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constructora - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></h1>
            <a href="includes/logout.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Menú Principal</h3>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="pedidos/agregar.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle"></i> Crear Nuevo Pedido
                    </a>
                    <a href="pedidos/listar.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list"></i> Ver Pedidos
                    </a>
                    <a href="mantenimiento_unidades/mantenimiento.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle"></i> Mantenimiento de unidades
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>