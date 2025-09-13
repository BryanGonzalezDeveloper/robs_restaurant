<?php
// /app/Views/errors/404.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada | ROBS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle fa-5x text-warning mb-3"></i>
                    <h1 class="display-1 fw-bold text-primary">404</h1>
                    <h2 class="mb-3">Página no encontrada</h2>
                    <p class="lead text-muted mb-4">
                        La página que buscas no existe o ha sido movida.
                    </p>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="/" class="btn btn-primary btn-lg">
                        <i class="fas fa-home me-2"></i>
                        Ir al Inicio
                    </a>
                    <a href="/login" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </a>
                </div>
                
                <div class="mt-5">
                    <small class="text-muted">
                        © <?= date('Y') ?> ROBS - Sistema POS
                    </small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>