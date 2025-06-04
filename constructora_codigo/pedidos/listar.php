<?php
include('../includes/conexion.php');
include('../includes/auth.php');
verificarSesion();

$titulo = "Lista de Pedidos";

// Consulta corregida para usar los nombres de campos correctos
$sql = "
    SELECT p.id, p.codigo_pedido, 
           e_reg.nombre AS empleado_registra,
           e_chofer.nombre AS chofer,
           
           p.fecha_pedido, p.estado
    FROM pedidos p
    JOIN empleados e_reg ON p.id_empleado_registra = e_reg.idempleado
    LEFT JOIN empleados e_chofer ON p.id_empleado_chofer = e_chofer.idempleado AND e_chofer.tipo_puesto = 'Chofer'
   
    ORDER BY p.fecha_pedido DESC
";

$resultado = $conexion->query($sql);

ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0"><i class="fas fa-list"></i> Lista de Pedidos</h2>
        <div>
            <a href="agregar.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nuevo Pedido
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($resultado->num_rows === 0): ?>
            <div class="alert alert-info">No hay pedidos registrados</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Registrado por</th>
                            <th>Chofer</th>
                            <th>Unidad</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($pedido = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($pedido['codigo_pedido']) ?></td>
                            <td><?= htmlspecialchars($pedido['empleado_registra']) ?></td>
                            <td><?= htmlspecialchars($pedido['chofer'] ?? 'Sin asignar') ?></td>
                            <td><?= htmlspecialchars($pedido['unidad'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y', strtotime($pedido['fecha_pedido'])) ?></td>
                            <td>
                                <span class="badge <?php 
                                    switch(strtolower($pedido['estado'])) {
                                        case 'pendiente': echo 'bg-warning'; break;
                                        case 'en proceso': echo 'bg-info'; break;
                                        case 'finalizado': echo 'bg-success'; break;
                                        case 'cancelado': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                ?>">
                                    <?= ucfirst($pedido['estado']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="ver.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($pedido['estado'] === 'pendiente'): ?>
                                        <a href="editar.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Script para manejar cambios de estado si lo necesitas
    $('.cambiar-estado').click(function() {
        const pedidoId = $(this).data('id');
        const nuevoEstado = $(this).data('estado');
        
        Swal.fire({
            title: '¿Cambiar estado?',
            text: `¿Estás seguro de cambiar el estado a ${nuevoEstado}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'cambiar_estado.php',
                    method: 'POST',
                    data: {
                        id_pedido: pedidoId,
                        estado: nuevoEstado
                    },
                    success: function(response) {
                        if(response.success) {
                            Swal.fire('¡Éxito!', 'Estado actualizado', 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error', response.error || 'Ocurrió un error', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo conectar al servidor', 'error');
                    }
                });
            }
        });
    });
});
</script>

<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>