<?php
// /app/Views/home/index.php
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <h1 class="display-4 text-primary fw-bold">ROBS</h1>
                <p class="lead text-muted">Restaurant Order, Billing & Sales System</p>
                <p class="text-muted">Sistema POS personalizado para restaurantes</p>
            </div>

            <div class="row g-4 mb-5">
                <!-- Características del Sistema -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h5 class="card-title">Gestión de Usuarios</h5>
                            <p class="card-text">Control completo de usuarios con roles y permisos específicos (BOSS, MANAGER, CAJERO, MESERO)</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-store fa-3x text-success mb-3"></i>
                            <h5 class="card-title">Multi-Sucursal</h5>
                            <p class="card-text">Manejo independiente de múltiples restaurantes desde un solo sistema</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-utensils fa-3x text-warning mb-3"></i>
                            <h5 class="card-title">Gestión de Menú</h5>
                            <p class="card-text">Administración completa de productos, categorías y modificadores por sucursal</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-receipt fa-3x text-info mb-3"></i>
                            <h5 class="card-title">Sistema de Órdenes</h5>
                            <p class="card-text">Toma de órdenes optimizada para tablets, computadoras y QR</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estado del Sistema -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Estado del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Información del Sistema:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Versión:</strong> <?= config('app.version', '1.0.0') ?></li>
                                <li><strong>Ambiente:</strong> 
                                    <span class="badge bg-<?= config('app.env') === 'development' ? 'warning' : 'success' ?>">
                                        <?= ucfirst(config('app.env', 'production')) ?>
                                    </span>
                                </li>
                                <li><strong>PHP:</strong> <?= PHP_VERSION ?></li>
                                <li><strong>Zona Horaria:</strong> <?= config('app.timezone', 'UTC') ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Estado de Servicios:</h6>
                            <ul class="list-unstyled">
                                <li>
                                    <i class="fas fa-database me-2"></i>
                                    <strong>Base de Datos:</strong> 
                                    <span class="badge bg-success">Conectada</span>
                                </li>
                                <li>
                                    <i class="fas fa-folder me-2"></i>
                                    <strong>Storage:</strong> 
                                    <span class="badge bg-<?= is_writable(STORAGE_PATH) ? 'success' : 'danger' ?>">
                                        <?= is_writable(STORAGE_PATH) ? 'Escribible' : 'Solo Lectura' ?>
                                    </span>
                                </li>
                                <li>
                                    <i class="fas fa-upload me-2"></i>
                                    <strong>Uploads:</strong> 
                                    <span class="badge bg-<?= is_writable(PUBLIC_PATH . '/assets') ? 'success' : 'warning' ?>">
                                        <?= is_writable(PUBLIC_PATH . '/assets') ? 'Disponible' : 'Limitado' ?>
                                    </span>
                                </li>
                                <li>
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <strong>Seguridad:</strong> 
                                    <span class="badge bg-success">Activa</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="text-center">
                <a href="<?= url('robs/public/login') ?>" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </a>
                <a href="<?= url('about') ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-info-circle me-2"></i>
                    Acerca de ROBS
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-5 pt-4 border-top">
                <p class="text-muted mb-0">
                    <small>© <?= date('Y') ?> ROBS - Restaurant Order, Billing & Sales System</small>
                </p>
                <p class="text-muted">
                    <small>Sistema POS desarrollado a medida para restaurantes</small>
                </p>
            </div>
        </div>
    </div>
</div>