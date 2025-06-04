<?php
include('../includes/conexion.php');

if (!isset($_GET['id'])) {
    header('Location: listar.php');
    exit;
}

$id_pedido = (int)$_GET['id'];

// Obtener información del pedido
$pedido = $conexion->query("
    SELECT p.*, e.nombre AS empleado 
    FROM pedidos p
    JOIN empleados e ON p.idempleado = e.id
    WHERE p.id = $id_pedido
")->fetch_assoc();

if (!$pedido) {
    header('Location: listar.php');
    exit;
}

// Procesar actualización si se envió el formulario
$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion->begin_transaction();
    
    try {
        // Validar campos requeridos
        $campos_requeridos = ['id_empleado', 'id_chofer', 'fecha_pedido'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("El campo {$campo} es obligatorio");
            }
        }

        // Actualizar datos principales del pedido
        $stmt = $conexion->prepare("UPDATE pedidos SET 
            id_empleado = ?,
            id_chofer = ?,
            fecha_pedido = ?,
            observaciones = ?
            WHERE id = ?");
        
        $stmt->bind_param("iissi", 
            $_POST['id_empleado'],
            $_POST['id_chofer'],
            $_POST['fecha_pedido'],
            $_POST['observaciones'],
            $id_pedido
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el pedido: " . $conexion->error);
        }

        // Actualizar productos
        $conexion->query("DELETE FROM pedido_productos WHERE id_pedido = $id_pedido");
        
        if (!empty($_POST['productos'])) {
            $stmt_detalle = $conexion->prepare("INSERT INTO pedido_productos (id_pedido, id_producto, cantidad) VALUES (?, ?, ?)");
            
            foreach ($_POST['productos'] as $index => $id_producto) {
                if (!empty($id_producto)) {
                    $cantidad = (int)($_POST['cantidades'][$index] ?? 1);
                    $stmt_detalle->bind_param("iii", $id_pedido, $id_producto, $cantidad);
                    $stmt_detalle->execute();
                }
            }
        }

        $conexion->commit();
        $mensaje_exito = 'Pedido actualizado correctamente';
        
        // Recargar los datos del pedido
        $pedido = $conexion->query("SELECT p.*, e.nombre AS empleado, c.nombre AS chofer 
                                  FROM pedidos p
                                  JOIN empleados e ON p.id_empleado = e.id
                                  JOIN choferes c ON p.id_chofer = c.id
                                  WHERE p.id = $id_pedido")->fetch_assoc();
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje_error = $e->getMessage();
    }
}

// Obtener productos del pedido
$productos = $conexion->query("
    SELECT pp.*, pr.descripcion, pr.precio 
    FROM pedido_productos pp
    JOIN productos pr ON pp.id_producto = pr.id
    WHERE pp.id_pedido = $id_pedido
");

// Obtener listas para los selects
$empleados = $conexion->query("SELECT id, nombre FROM empleados")->fetch_all(MYSQLI_ASSOC);

$todos_productos = $conexion->query("SELECT id, descripcion, precio FROM productos")->fetch_all(MYSQLI_ASSOC);

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
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Empleado</label>
                        <?php if ($modo_edicion): ?>
                            <select name="id_empleado" class="form-control select2" required>
                                <?php foreach ($empleados as $e): ?>
                                    <option value="<?= $e['id'] ?>" <?= $e['id'] == $pedido['id_empleado'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <p class="form-control-static"><?= htmlspecialchars($pedido['empleado']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Chofer</label>
                        <?php if ($modo_edicion): ?>
                            <select name="id_chofer" class="form-control select2" required>
                                <?php foreach ($choferes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $pedido['id_chofer'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <p class="form-control-static"><?= htmlspecialchars($pedido['chofer']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fecha</label>
                        <?php if ($modo_edicion): ?>
                            <input type="date" name="fecha_pedido" class="form-control" 
                                   value="<?= htmlspecialchars($pedido['fecha_pedido']) ?>" required>
                        <?php else: ?>
                            <p class="form-control-static">
                                <?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Estado</label>
                        <p class="form-control-static">
                            <span class="badge <?php
                                echo match(strtolower($pedido['estado'])) {
                                    'pendiente' => 'bg-warning text-dark',
                                    'en proceso' => 'bg-info text-white',
                                    'finalizado' => 'bg-success text-white',
                                    'cancelado' => 'bg-danger text-white',
                                    default => 'bg-secondary text-white'
                                };
                            ?>">
                                <?= ucfirst($pedido['estado']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Productos</label>
                <?php if ($modo_edicion): ?>
                    <div id="productos-container">
                        <?php 
                        $productos_array = $productos->fetch_all(MYSQLI_ASSOC);
                        if (empty($productos_array)) {
                            $productos_array = [['id_producto' => '', 'cantidad' => 1]];
                        }
                        
                        foreach ($productos_array as $index => $prod): 
                        ?>
                        <div class="producto-item mb-3">
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <select name="productos[]" class="form-control select2-producto" required>
                                        <option value="">Seleccionar producto</option>
                                        <?php foreach ($todos_productos as $p): ?>
                                            <option value="<?= $p['id'] ?>" 
                                                <?= $p['id'] == $prod['id_producto'] ? 'selected' : '' ?>
                                                data-precio="<?= $p['precio'] ?>">
                                                <?= htmlspecialchars($p['descripcion']) ?> ($<?= number_format($p['precio'], 2) ?>)
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
                    <small class="text-muted">Mínimo 1 producto requerido</small>
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
                                $productos->data_seek(0); // Reiniciar puntero
                                while ($prod = $productos->fetch_assoc()): 
                                    $subtotal = $prod['precio'] * $prod['cantidad'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($prod['descripcion']) ?></td>
                                    <td class="text-center"><?= $prod['cantidad'] ?></td>
                                    <td class="text-right">$<?= number_format($prod['precio'], 2) ?></td>
                                    <td class="text-right">$<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
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

            <div class="form-group">
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
    // Inicializar Select2
    $('.select2').select2({
        width: '100%',
        placeholder: 'Seleccione una opción'
    });

    // Añadir nuevo producto
    $(document).on('click', '.btn-add-producto', function() {
        const newItem = $('.producto-item:first').clone();
        newItem.find('select').val('').trigger('change');
        newItem.find('input').val('1');
        newItem.find('.btn-add-producto')
            .removeClass('btn-success')
            .addClass('btn-danger')
            .html('<i class="fas fa-minus"></i>')
            .removeClass('btn-add-producto')
            .addClass('btn-remove-producto');
        $('#productos-container').append(newItem);
    });

    // Eliminar producto
    $(document).on('click', '.btn-remove-producto', function() {
        if ($('.producto-item').length > 1) {
            $(this).closest('.producto-item').remove();
        } else {
            Swal.fire('Advertencia', 'Debe haber al menos un producto', 'warning');
        }
    });

    // Validación del formulario
    $('#form-pedido').on('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            Swal.fire('Error', 'Por favor complete todos los campos obligatorios', 'error');
            return;
        }

        Swal.fire({
            title: '¿Guardar cambios?',
            text: '¿Está seguro que desea actualizar este pedido?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#234076',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
});
</script>
<?php endif; ?>

<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>