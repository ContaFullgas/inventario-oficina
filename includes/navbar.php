<?php 
// navbar.php
$u = auth_user(); 
?>
<nav class="modern-navbar">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">

            <!-- LOGO Y MARCA -->
            <a href="index.php" class="navbar-brand-custom">
                <!-- Reemplaza el src con la ruta real de tu logo -->
                <img src="assets/logo_fg_nav2.png" alt="Logo" class="d-inline-block align-text-top"
                    style="height: 40px; border-radius: 8px;"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <!-- Icono de respaldo (si la imagen falla) -->
                <div class="brand-icon" style="display: none;">
                    <i class="bi bi-fuel-pump-fill"></i>
                </div>
                <span>Fullgas Inventario</span>
            </a>

            <!-- OPCIONES DERECHAS -->
            <div class="d-flex align-items-center gap-3">

                <!-- BOTÓN DARK MODE -->
                <button id="themeToggle"
    class="btn btn-outline-light btn-sm rounded-circle d-flex align-items-center justify-content-center"
    style="width: 36px; height: 36px; border-color: rgba(255,255,255,0.2); transition: all 0.3s ease;">
    <i class="fas fa-moon" id="themeIcon"></i>
</button>

                <?php if ($u): ?>
                <!-- MENÚ DESPLEGABLE DE PERFIL -->
                <div class="dropdown">
                    <div class="user-badge dropdown-toggle" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false" style="cursor: pointer;">
                        <i class="fas fa-user-circle fs-5"></i>
                        <span class="d-none d-md-inline fw-semibold"><?=h($u['usuario'])?></span>
                        <span class="role-badge ms-1"><?=h($u['rol'])?></span>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end shadow mt-2 border-0"
                        style="border-radius: 12px; min-width: 220px;">
                        <li class="px-3 py-2 text-muted border-bottom mb-1">
                            <small class="d-block">Conectado como:</small>
                            <strong class="text-dark d-block text-truncate"
                                style="color: var(--text-dark) !important;"><?=h($u['usuario'])?></strong>
                        </li>
                 
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <a class="dropdown-item text-danger py-2 d-flex align-items-center gap-2 fw-semibold"
                                href="public/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <a class="btn btn-outline-light" href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Ingresar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

