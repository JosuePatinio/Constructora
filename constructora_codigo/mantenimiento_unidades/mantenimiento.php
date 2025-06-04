<?php
include('../includes/conexion.php');

$titulo = "Mantenimiento de Unidades";

$query = "SELECT idunidad, modelo, placas, fecha_ultimo_mantenimiento,
DATEDIFF(DATE_ADD(fecha_ultimo_mantenimiento, INTERVAL 90 DAY), CURDATE()) AS dias_restantes
FROM Unidades_de_transporte";

$resultado = mysqli_query($conexion, $query);

ob_start();
?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Unidades de Transporte</h5>
        <a href="mantenimiento_agregar.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Agregar Unidad</a>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Placas</th>
                    <th>Modelo</th>
                    <th>Último Mantenimiento</th>
                    <th>Días para el próximo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($unidad = mysqli_fetch_assoc($resultado)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($unidad['placas']) ?></td>
                        <td><?= htmlspecialchars($unidad['modelo']) ?></td>
                        <td><?= htmlspecialchars($unidad['fecha_ultimo_mantenimiento']) ?></td>
                        <td>
                            <?php
                            $dias = $unidad['dias_restantes'];
                            if ($dias <= 0) {
                                echo "<span class='badge estado-cancelado'>¡Urgente!</span>";
                            } else {
                                echo "<span class='badge estado-pendiente'>{$dias} días</span>";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>
