<?php
include('../includes/conexion.php');
include('../includes/auth.php');
verificarSesion();

header('Content-Type: application/json');

if (!isset($_GET['id_pedido'])) {
    echo json_encode(['success' => false, 'error' => 'ID de pedido no proporcionado']);
    exit;
}

$id_pedido = (int)$_GET['id_pedido'];

$sql = "SELECT nombre_cliente, domicilio_cliente, telefono_cliente 
        FROM pedidos 
        WHERE id = ? LIMIT 1";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
    exit;
}

$cliente = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'data' => [
        'nombre' => $cliente['nombre_cliente'],
        'domicilio' => $cliente['domicilio_cliente'],
        'telefono' => $cliente['telefono_cliente'] ?? 'No especificado'
    ]
]);

?>
