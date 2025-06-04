<?php
include('../includes/conexion.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_POST['id_pedido']) || !isset($_POST['estado'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$id_pedido = (int)$_POST['id_pedido'];
$nuevo_estado = $conexion->real_escape_string($_POST['estado']);

// Validar estados permitidos
$estados_permitidos = ['pendiente', 'en proceso', 'finalizado', 'cancelado'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Estado no válido']);
    exit;
}

$conexion->begin_transaction();

try {
    // Actualizar el estado del pedido
    $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_pedido);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el estado: " . $stmt->error);
    }
    
    // Determinar clase CSS para el botón
    $clase_boton = match($nuevo_estado) {
        'pendiente' => 'btn-warning',
        'en proceso' => 'btn-info',
        'finalizado' => 'btn-success',
        'cancelado' => 'btn-danger',
        default => 'btn-secondary'
    };
    
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'clase_boton' => $clase_boton
    ]);
    
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>