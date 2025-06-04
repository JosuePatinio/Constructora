<?php
include('../includes/conexion.php');

$titulo = "Agregar Unidad";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modelo = $_POST['modelo'];
    $placas = $_POST['placas'];
    $fecha = $_POST['fecha_ultimo_mantenimiento'];

    $query = "INSERT INTO Unidades_de_transporte (modelo, placas, fecha_ultimo_mantenimiento)
              VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("sss", $modelo, $placas, $fecha);
    
    if ($stmt->execute()) {
        header("Location: mantenimiento.php");
        exit;
    } else {
        $error = "Error al agregar la unidad.";
    }
}

ob_start();
?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Registrar Nueva Unidad</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Modelo</label>
                <input type="text" name="modelo" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Placas</label>
                <input type="text" name="placas" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Fecha del último mantenimiento</label>
                <input type="date" name="fecha_ultimo_mantenimiento" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</div>
<?php
$contenido = ob_get_clean();
include('../includes/layout.php');
?>
