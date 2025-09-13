<?php
// /app/Views/auth/login.php
?>
<div class="min-vh-100 d-flex align-items-center bg-gradient-primary" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo y Título -->
                        <div class="text-center mb-4">
                            <h1 class="h2 text-primary fw-bold">ROBS</h1>
                            <p class="text-muted">Restaurant Order, Billing & Sales</p>
                        </div>

                        <!-- Alertas -->
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= $success ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Formulario de Login -->
                        <form method="POST" action="/login" novalidate>
                            <!-- Token CSRF -->
                            <?= csrfField() ?>
                            
                            <!-- Sucursal -->
                            <div class="mb-3">
                                <label for="branch_id" class="form-label">
                                    <i class="fas fa-building me-2"></i>Sucursal
                                </label>
                                <select class="form-select" id="branch_id" name="branch_id" required>
                                    <option value="">Seleccionar sucursal...</option>
                                    <?php foreach ($branches as $branch): ?>
                                        <option value="<?= $branch['id'] ?>" 
                                                <?= ($old_input['branch_id'] ?? '') == $branch['id'] ? 'selected' : '' ?>>
                                            <?= e($branch['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Usuario -->
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Usuario
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Ingrese su usuario"
                                       value="<?= e($old_input['username'] ?? '') ?>"
                                       required>
                            </div>

                            <!-- Contraseña -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Contraseña
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Ingrese su contraseña"
                                           required>
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword"
                                            title="Mostrar/Ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Recordar sesión -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Recordar por 30 días
                                </label>
                            </div>

                            <!-- Botón de login -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </button>

                            <!-- Enlaces adicionales -->
                            <div class="text-center">
                                <a href="/forgot-password" class="text-decoration-none">
                                    <i class="fas fa-question-circle me-1"></i>
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-white-50">
                        © <?= date('Y') ?> ROBS - Sistema POS personalizado
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle para mostrar/ocultar contraseña
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Validación en tiempo real
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[required], select[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });

    // Auto-focus en el primer campo vacío
    const firstEmpty = Array.from(inputs).find(input => input.value.trim() === '');
    if (firstEmpty) firstEmpty.focus();
});
</script>