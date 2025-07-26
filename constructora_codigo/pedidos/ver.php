<?php
include('../includes/conexion.php');
include('../includes/auth.php');
verificarSesion();

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id_pedido = (int)$_GET['id'];

// Obtener información del pedido
$pedido = $conexion->query("
    SELECT p.*, 
           e_reg.nombre AS empleado_registra,
           e_chofer.nombre AS chofer
    FROM pedidos p
    JOIN empleados e_reg ON p.id_empleado_registra = e_reg.idempleado
    LEFT JOIN empleados e_chofer ON p.id_empleado_chofer = e_chofer.idempleado
    WHERE p.id = $id_pedido
")->fetch_assoc();

if (!$pedido) {
    header('Location: listar.php');
    exit;
}

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion->begin_transaction();
    
    try {
        if (empty($_POST['fecha_pedido'])) {
            throw new Exception("La fecha del pedido es obligatoria");
        }

        $id_chofer = !empty($_POST['id_empleado_chofer']) ? (int)$_POST['id_empleado_chofer'] : null;
        $fecha_pedido = $_POST['fecha_pedido'];
        $observaciones = $_POST['observaciones'] ?? '';

        if ($id_chofer !== null) {
            $chofer_valido = $conexion->query("
                SELECT 1 FROM empleados 
                WHERE idempleado = $id_chofer AND tipo_puesto = 'Chofer'
            ")->num_rows;
            if (!$chofer_valido) {
                throw new Exception("El chofer seleccionado no es válido");
            }
        }

        $stmt = $conexion->prepare("UPDATE pedidos SET 
            id_empleado_chofer = ?,
            fecha_pedido = ?,
            observaciones = ?
            WHERE id = ?");
        $stmt->bind_param("issi", $id_chofer, $fecha_pedido, $observaciones, $id_pedido);

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el pedido: " . $conexion->error);
        }

        $conexion->query("DELETE FROM pedido_productos WHERE id_pedido = $id_pedido");

        if (!empty($_POST['productos'])) {
            $stmt_detalle = $conexion->prepare("INSERT INTO pedido_productos (id_pedido, idproducto, cantidad) VALUES (?, ?, ?)");
            foreach ($_POST['productos'] as $index => $idproducto) {
                if (!empty($idproducto)) {
                    $cantidad = (int)($_POST['cantidades'][$index] ?? 1);
                    $stmt_detalle->bind_param("iii", $id_pedido, $idproducto, $cantidad);
                    $stmt_detalle->execute();
                }
            }
        }

        $conexion->commit();
        $mensaje_exito = 'Pedido actualizado correctamente';

        // Recargar datos
        $pedido = $conexion->query("
            SELECT p.*, 
                   e_reg.nombre AS empleado_registra,
                   e_chofer.nombre AS chofer
            FROM pedidos p
            JOIN empleados e_reg ON p.id_empleado_registra = e_reg.idempleado
            LEFT JOIN empleados e_chofer ON p.id_empleado_chofer = e_chofer.idempleado
            WHERE p.id = $id_pedido
        ")->fetch_assoc();
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje_error = $e->getMessage();
    }
}

// Obtener choferes
$choferes = $conexion->query("
    SELECT idempleado as id, nombre
    FROM empleados 
    WHERE tipo_puesto = 'Chofer'
    ORDER BY nombre ASC
")->fetch_all(MYSQLI_ASSOC);

// Obtener productos disponibles
$todos_productos = $conexion->query("
    SELECT idproducto as id, descripcion, precio 
    FROM productos
")->fetch_all(MYSQLI_ASSOC);

// Obtener productos del pedido actual
$productos_pedido = $conexion->query("
    SELECT pp.idproducto, pp.cantidad, pr.descripcion, pr.precio
    FROM pedido_productos pp
    JOIN productos pr ON pp.idproducto = pr.idproducto
    WHERE pp.id_pedido = $id_pedido
")->fetch_all(MYSQLI_ASSOC);

if (empty($productos_pedido)) {
    $productos_pedido = [['idproducto' => '', 'cantidad' => 1, 'descripcion' => '', 'precio' => 0]];
}

$titulo = "Pedido #" . $pedido['codigo_pedido'];
$modo_edicion = isset($_GET['editar']) && $pedido['estado'] === 'pendiente';
ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">
            <i class="fas fa-file-invoice mr-2"></i>
            <?= $modo_edicion ? 'Editar Pedido' : 'Detalle del Pedido' ?>
        </h2>
        <div>
            <?php if ($modo_edicion): ?>
                <a href="ver.php?id=<?= $id_pedido ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </a>
            <?php else: ?>
                <a href="listar.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
                <?php if ($pedido['estado'] === 'pendiente'): ?>
                    <a href="ver.php?id=<?= $id_pedido ?>&editar=1" class="btn btn-primary btn-sm ml-2">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card-body">
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success mb-4"><?= $mensaje_exito ?></div>
        <?php elseif ($mensaje_error): ?>
            <div class="alert alert-danger mb-4"><?= $mensaje_error ?></div>
        <?php endif; ?>

        <form method="post" id="form-pedido">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Registrado por</label>
                    <p class="form-control-static"><?= htmlspecialchars($pedido['empleado_registra']) ?></p>
                </div>
                <div class="col-md-6">
                    <label>Chofer</label>
                    <?php if ($modo_edicion): ?>
                        <select name="id_empleado_chofer" class="form-control select2" required>
                            <option value="">Sin asignar</option>
                            <?php foreach ($choferes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($c['id'] == $pedido['id_empleado_chofer']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <p class="form-control-static"><?= htmlspecialchars($pedido['chofer'] ?? 'Sin asignar') ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Fecha *</label>
                    <?php if ($modo_edicion): ?>
                        <input type="date" name="fecha_pedido" class="form-control" 
                               value="<?= htmlspecialchars($pedido['fecha_pedido']) ?>" required>
                    <?php else: ?>
                        <p class="form-control-static"><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label>Estado</label>
                    <p class="form-control-static">
                        <span class="badge 
                        <?= match(strtolower($pedido['estado'])) {
                            'pendiente' => 'bg-warning text-dark',
                            'en proceso' => 'bg-info text-white',
                            'finalizado' => 'bg-success text-white',
                            'cancelado' => 'bg-danger text-white',
                            default => 'bg-secondary text-white'
                        } ?>">
                            <?= ucfirst($pedido['estado']) ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>Productos</label>
                <?php if ($modo_edicion): ?>
                    <div id="productos-container">
                        <?php foreach ($productos_pedido as $index => $prod): ?>
                        <div class="producto-item mb-3">
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <select name="productos[]" class="form-control" required>
                                        <option value="">Seleccionar producto</option>
                                        <?php foreach ($todos_productos as $p): ?>
                                            <option value="<?= $p['id'] ?>" 
                                                <?= ($p['id'] == $prod['idproducto']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['descripcion']) ?> - $<?= number_format($p['precio'], 2) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="cantidades[]" class="form-control" 
                                        min="1" value="<?= $prod['cantidad'] ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <?php if ($index === 0): ?>
                                        <button type="button" class="btn btn-success btn-sm btn-add-producto">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-producto">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-right">Precio Unitario</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($productos_pedido as $prod): 
                                    $subtotal = $prod['precio'] * $prod['cantidad'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['descripcion']) ?></td>
                                    <td class="text-center"><?= $prod['cantidad'] ?></td>
                                    <td class="text-right">$<?= number_format($prod['precio'], 2) ?></td>
                                    <td class="text-right">$<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Total:</th>
                                    <th class="text-right">$<?= number_format($total, 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group mb-4">
                <label>Observaciones</label>
                <?php if ($modo_edicion): ?>
                    <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($pedido['observaciones']) ?></textarea>
                <?php else: ?>
                    <div class="bg-light p-3 rounded">
                        <?= !empty($pedido['observaciones']) ? nl2br(htmlspecialchars($pedido['observaciones'])) : 'Sin observaciones' ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($modo_edicion): ?>
                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
                    <a href="ver.php?id=<?= $id_pedido ?>" class="btn btn-secondary ml-2">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($modo_edicion): ?>
<script>
$(document).ready(function() {
    
    $('.select2, .select-producto').select2({ width: '100%' });

    $(document).on('click', '.btn-add-producto', function() {
        const newItem = $('.producto-item:first').clone();
        newItem.find('select').val('');
        newItem.find('input').val('1');
        newItem.find('.btn-add-producto')
            .removeClass('btn-success btn-add-producto')
            .addClass('btn-danger btn-remove-producto')
            .html('<i class="fas fa-minus"></i>');
        $('#productos-container').append(newItem);
    });

    // Eliminar producto
    $(document).on('click', '.btn-remove-producto', function() {
        if ($('.producto-item').length > 1) {
            $(this).closest('.producto-item').remove();
        } else {
            alert('Debe haber al menos un producto');
        }
    });

    // Validación básica al enviar
    $('#form-pedido').on('submit', function(e) {
        let valid = true;
        $('select[name="productos[]"]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                valid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        if (!valid) {
            e.preventDefault();
            alert('Seleccione productos válidos para todos los items');
        }
    });
});
</script>
<?php endif; ?>

<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>
