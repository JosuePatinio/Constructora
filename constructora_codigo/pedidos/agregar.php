<?php
include('../includes/conexion.php');
include('../includes/auth.php');
verificarSesion();

$mensaje_exito = '';
$mensaje_error = '';
$usuario = obtenerUsuario();

// Función para generar nuevo código de pedido
function generarNuevoCodigoPedido($conexion, $fecha) {
    // Formatear fecha (AAAAMMDD)
    $fecha_formato = date('Ymd', strtotime($fecha));
    
    // Obtener número consecutivo
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = DATE(?)");
    $stmt->bind_param("s", $fecha);
    $stmt->execute();
    $stmt->bind_result($consecutivo);
    $stmt->fetch();
    $stmt->close();
    
    $consecutivo++;
    $consecutivo_formato = str_pad($consecutivo, 4, '0', STR_PAD_LEFT);
    
    return "PED-{$fecha_formato}-{$consecutivo_formato}";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conexion->begin_transaction();
    
    try {
        // Validación de campos obligatorios
        if (empty($_POST['fecha_pedido'])) {
            throw new Exception("La fecha del pedido es obligatoria");
        }

        // Generar nuevo código de pedido
        $codigo_pedido = generarNuevoCodigoPedido($conexion, $_POST['fecha_pedido']);

        // Insertar pedido principal
        $stmt = $conexion->prepare("INSERT INTO pedidos (
            codigo_pedido, 
            id_empleado_registra, 
            id_empleado_chofer, 
            fecha_pedido, 
            observaciones,
            estado
        ) VALUES (?, ?, ?, ?, ?, 'pendiente')");
        
        $stmt->bind_param("siiss", 
            $codigo_pedido,
            $usuario['idempleado'], // Usuario actual de la sesión
            $_POST['id_empleado_chofer'],
            $_POST['fecha_pedido'],
            $_POST['observaciones']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el pedido: " . $conexion->error);
        }
        
        $pedido_id = $conexion->insert_id;
        $productos_procesados = 0;
        
        // Procesar productos
        if (!empty($_POST['productos'])) {
            $stmt_detalle = $conexion->prepare("INSERT INTO pedido_productos (
                id_pedido, 
                idproducto, 
                cantidad
            ) VALUES (?, ?, ?)");
            
            foreach ($_POST['productos'] as $index => $idproducto) {
                if (!empty($idproducto)) {
                    $cantidad = $_POST['cantidades'][$index] ?? 1;
                    
                    if (!is_numeric($cantidad) || $cantidad <= 0) {
                        throw new Exception("La cantidad debe ser un número positivo");
                    }
                    
                    $stmt_detalle->bind_param("iii", $pedido_id, $idproducto, $cantidad);
                    if (!$stmt_detalle->execute()) {
                        throw new Exception("Error al guardar productos: " . $conexion->error);
                    }
                    $productos_procesados++;
                }
            }
            
            if ($productos_procesados === 0) {
                throw new Exception("Debe agregar al menos un producto");
            }
        }
        
        $conexion->commit();
        $mensaje_exito = 'Pedido registrado correctamente con folio: ' . $codigo_pedido;
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje_error = $e->getMessage();
    }
}

// Obtener choferes (empleados con tipo_puesto = 'Chofer')
$choferes = $conexion->query("
    SELECT idempleado as id, nombre 
    FROM empleados 
    WHERE tipo_puesto = 'Chofer'
")->fetch_all(MYSQLI_ASSOC);

// Obtener productos
$productos = $conexion->query("
    SELECT idproducto as id, descripcion as nombre, precio 
    FROM productos
")->fetch_all(MYSQLI_ASSOC);

$titulo = "Nuevo Pedido";
ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0"><i class="fas fa-plus-circle mr-2"></i>Nuevo Pedido</h2>
        <div>
            <span class="badge bg-primary">
                <i class="fas fa-user"></i> <?= htmlspecialchars($usuario['nombre']) ?>
            </span>
            <a href="listar.php" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?= $mensaje_exito ?></div>
        <?php elseif ($mensaje_error): ?>
            <div class="alert alert-danger"><?= $mensaje_error ?></div>
        <?php endif; ?>

        <form method="post" id="form-pedido" class="needs-validation" novalidate>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Chofer asignado *</label>
                        <select name="id_empleado_chofer" class="form-control select2" required>
                            <option value="">Seleccionar chofer...</option>
                            <?php foreach ($choferes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($_POST['id_empleado_chofer'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Seleccione un chofer</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Fecha del pedido *</label>
                        <input type="date" name="fecha_pedido" class="form-control" 
                               value="<?= htmlspecialchars($_POST['fecha_pedido'] ?? date('Y-m-d')) ?>" required>
                        <div class="invalid-feedback">Ingrese una fecha válida</div>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Productos *</label>
                <div id="productos-container">
                    <?php 
                    $productos_post = $_POST['productos'] ?? [''];
                    $cantidades_post = $_POST['cantidades'] ?? [1];
                    foreach ($productos_post as $index => $producto_id): 
                    ?>
                    <div class="producto-item mb-3">
                        <div class="row g-2">
                            <div class="col-md-7">
                                <select name="productos[]" class="form-control select2-producto" required>
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $p): ?>
                                        <option value="<?= $p['id'] ?>" 
                                            <?= $producto_id == $p['id'] ? 'selected' : '' ?>
                                            data-precio="<?= $p['precio'] ?>">
                                            <?= htmlspecialchars($p['nombre']) ?> ($<?= number_format($p['precio'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="cantidades[]" class="form-control" 
                                       min="1" value="<?= $cantidades_post[$index] ?? 1 ?>" required>
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
            </div>

            <div class="form-group mb-4">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"><?= 
                    htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Guardar Pedido
                </button>
                <button type="reset" class="btn btn-secondary ms-2">
                    <i class="fas fa-undo mr-1"></i> Limpiar
                </button>
            </div>
        </form>
    </div>
</div>

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
            .removeClass('btn-success').addClass('btn-danger')
            .html('<i class="fas fa-minus"></i>')
            .removeClass('btn-add-producto').addClass('btn-remove-producto');
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

    // Validación antes de enviar
    $('#form-pedido').on('submit', function(e) {
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
            e.preventDefault();
            Swal.fire('Error', 'Complete todos los campos obligatorios', 'error');
        }
    });
});
</script>

<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>