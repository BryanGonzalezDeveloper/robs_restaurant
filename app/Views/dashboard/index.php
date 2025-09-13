<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['page_title'] ?? 'Dashboard - ROBS' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #4CAF50 !important;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        }
        .stat-card.info {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
        }
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: calc(100vh - 56px);
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            color: #4CAF50 !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-utensils me-2"></i>
                ROBS - Sistema POS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($data['user']['username'] ?? 'Usuario') ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-cog me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="/logout" class="d-inline">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/pos">
                                <i class="fas fa-cash-register me-2"></i>Punto de Venta
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/orders">
                                <i class="fas fa-list-alt me-2"></i>Órdenes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/menu">
                                <i class="fas fa-utensils me-2"></i>Menú
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/reports">
                                <i class="fas fa-chart-bar me-2"></i>Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/users">
                                <i class="fas fa-users me-2"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/branches">
                                <i class="fas fa-building me-2"></i>Sucursales
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download me-1"></i>Exportar
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i>Nueva Orden
                        </button>
                    </div>
                </div>

                <!-- Alertas -->
                <?php if (!empty($data['alerts'])): ?>
                    <?php foreach ($data['alerts'] as $alert): ?>
                        <div class="alert alert-<?= $alert['type'] === 'warning' ? 'warning' : 'info' ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?= $alert['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle' ?> me-2"></i>
                            <?= htmlspecialchars($alert['message']) ?>
                            <small class="text-muted ms-2">(<?= htmlspecialchars($alert['time']) ?>)</small>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Tarjetas de estadísticas -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card success">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Ventas Hoy</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            $<?= number_format($data['stats']['today_sales']['amount'] ?? 0, 2) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card info">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Órdenes Hoy</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            <?= number_format($data['stats']['today_sales']['orders'] ?? 0) ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-list-alt fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card warning">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Mesas Ocupadas</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            <?= $data['stats']['active_tables']['occupied'] ?? 0 ?>/<?= $data['stats']['active_tables']['total'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chair fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-uppercase mb-1">Órdenes Pendientes</div>
                                        <div class="h5 mb-0 font-weight-bold">
                                            <?= $data['stats']['pending_orders'] ?? 0 ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x opacity-75"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Órdenes Recientes -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Órdenes Recientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Mesa/Cliente</th>
                                                <th>Mesero</th>
                                                <th>Total</th>
                                                <th>Estado</th>
                                                <th>Hora</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($data['recent_orders'] ?? [] as $order): ?>
                                                <tr>
                                                    <td><?= $order['id'] ?></td>
                                                    <td><?= htmlspecialchars($order['table']) ?></td>
                                                    <td><?= htmlspecialchars($order['waiter']) ?></td>
                                                    <td>$<?= number_format($order['amount'], 2) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= 
                                                            $order['status'] === 'listo' ? 'success' : 
                                                            ($order['status'] === 'preparando' ? 'warning' : 'info') 
                                                        ?>">
                                                            <?= ucfirst($order['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $order['time'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users me-2"></i>
                                    Personal en Línea
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="fas fa-concierge-bell me-2"></i>Meseros:</span>
                                    <span class="badge bg-primary"><?= $data['stats']['staff_online']['waiters'] ?? 0 ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="fas fa-utensils me-2"></i>Cocina:</span>
                                    <span class="badge bg-success"><?= $data['stats']['staff_online']['kitchen'] ?? 0 ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="fas fa-cash-register me-2"></i>Cajeros:</span>
                                    <span class="badge bg-info"><?= $data['stats']['staff_online']['cashiers'] ?? 0 ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Resumen del Día
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Ticket Promedio:</span>
                                    <strong>$<?= number_format($data['stats']['today_sales']['avg_ticket'] ?? 0, 2) ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Mesas Disponibles:</span>
                                    <strong><?= $data['stats']['active_tables']['available'] ?? 0 ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Eficiencia:</span>
                                    <strong class="text-success">92%</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh stats cada 30 segundos
        setInterval(function() {
            fetch('/dashboard/stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar estadísticas en la página
                        console.log('Stats updated:', data);
                    }
                })
                .catch(error => console.error('Error updating stats:', error));
        }, 30000);
    </script>
</body>
</html>