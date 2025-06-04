<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Constructora - Pedidos' ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- jQuery + UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- BootStrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
    :root {
        --primary: #1E3A8A;
        --secondary: #3B82F6;
        --success: #10B981;
        --danger: #EF4444;
        --warning:rgb(97, 66, 12);
        --light: #F9FAFB;
        --dark: #1F2937;
        --gray: #6B7280;
    }
    
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        margin: 0;
        background-color: #F3F4F6;
    }
    
    /* Layout NetSuite */
    .app-container {
        display: flex;
        min-height: 100vh;
    }
    
    .sidebar {
        background-color: #1E3A8A; 
        color: rgba(255, 255, 255, 0.9); 
        width: 250px;
        min-width: 250px;
    }

    /* Color principal de los links */
    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8); 
        text-decoration: none;
        transition: color 0.3s;
        padding: 10px 15px;
        display: block;
        border-left: 3px solid transparent;
    }

    /* Color al pasar el mouse (hover) */
    .sidebar .nav-link:hover {
        color: #ffffff !important; 
        background-color: rgba(255,255,255,0.1);
    }

    /* Color cuando está activo (página actual) */
    .sidebar .nav-link.active {
        color: #ffffff !important; 
        font-weight: 600;
        border-left: 3px solid var(--success);
        background-color: rgba(255,255,255,0.15);
    }
    
    .sidebar-header {
        background-color: rgba(0,0,0,0.1);
        padding: 25px;
        margin-bottom: 10px;
    }
    
    .sidebar-header h2 {
        margin: 0;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
    }
    
    .sidebar-header i {
        margin-right: 10px;
        color: var(--success);
    }
    
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    /* Top Bar */
    .top-bar {
        background-color: white;
        padding: 15px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .breadcrumbs {
        font-size: 0.9rem;
        color: var(--dark);
    }
    
    .breadcrumbs a {
        color: var(--secondary);
        text-decoration: none;
    }
    
    /* Contenedor Principal */
    .content-container {
        padding: 25px;
        flex: 1;
        background-color: #F3F4F6;
    }
    
    /* Cards */
    .card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        border: none;
    }
    
    .card-header {
        padding: 15px 20px;
        background-color: var(--light);
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0 !important;
    }
    
    .card-title {
        margin: 0;
        font-size: 1.1rem;
        color: var(--primary);
        font-weight: 600;
    }
    
    .card-body {
        padding: 20px;
    }
    
    /* Botones */
    .btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn i {
        margin-right: 8px;
    }
    
    .btn-primary {
        background-color: var(--primary);
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #1A237E;
    }
    
    .btn-secondary {
        background-color: var(--gray);
        color: white;
        border: none;
    }
    
    /* Tablas */
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    
    .table th, .table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #E5E7EB;
    }
    
    .table th {
        background-color: var(--light);
        color: var(--dark);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
    }
    
    .table tr:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    /* Badges */
    .badge {
        padding: 0.35em 0.65em;
        font-size: 0.75em;
        font-weight: 700;
        border-radius: 0.25rem;
    }

    .estado-pendiente {
        background-color:rgba(255, 193, 7, 0.77);
        color: #000;
    }

    .estado-proceso {
        background-color:rgb(140, 147, 222);
        color: #000;
    }

    .estado-finalizado {
        background-color: #198754;
        color: #fff;
    }

    .estado-cancelado {
        background-color: #dc3545;
        color: #fff;
    }

    .estado-default {
        background-color: #6c757d;
        color: #fff;
    }
        
    /* Formularios */
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #E5E7EB;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.25);
    }
    
    .full-width {
        grid-column: 1 / -1;
    }

    /* Mejoras para el menú de navegación */
    .nav-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin-bottom: 5px;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .user-name {
        font-weight: 500;
    }
    </style>
</head>
<body>
<div class="app-container">
    <!-- Sidebar simplificado -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-building"></i> Constructora</h2>
        </div>
        <!-- Sidebar corregido -->
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="../index.php" class="nav-link">
                    <i class="fas fa-home"></i> Inicio
                </a>
            </li>
            <li class="nav-item">
                <a href="../pedidos/listar.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'listar.php' ? 'active' : '' ?>">
                    <i class="fas fa-truck"></i> Pedidos
                </a>
            </li>
            <li class="nav-item">
                <a href="../pedidos/agregar.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'agregar.php' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i> Nuevo Pedido
                </a>
            </li>
            <li class="nav-item">
                <a href="../mantenimiento_unidades/mantenimiento.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'mantenimiento.php' ? 'active' : '' ?>">
                    <i class="fas fa-tools"></i> Mantenimiento
                </a>
            </li>
        </ul>

        </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div class="breadcrumbs">
                <a href="../index.php">Inicio</a> / <span><?= $titulo ?? 'Panel' ?></span>
            </div>
            <div class="user-menu">
                <span class="user-name"><?= $_SESSION['usuario']['nombre'] ?? 'Usuario' ?></span>
                <a href="../includes/logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
        
        <div class="content-container">
            <?= $contenido ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        width: '100%',
        placeholder: 'Selecciona una opción',
        dropdownParent: $('.content-container')
    });
    
    // Datepicker
    $("input[name='fecha_pedido']").datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        yearRange: '2023:2025'
    });

    // Mostrar el nombre del archivo en inputs de tipo file
    $('input[type="file"]').change(function(e) {
        var fileName = e.target.files[0].name;
        $(this).next('.custom-file-label').html(fileName);
    });
});
</script>
</body>
</html>