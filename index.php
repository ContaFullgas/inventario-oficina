<?php
// Archivo index.php
require_once __DIR__.'/config/auth.php';
auth_check(); // obliga a iniciar sesión

ob_start(); 
require_once __DIR__.'/config/db.php'; 
require_once __DIR__.'/config/util.php'; 

// Evitar caché para que tablas y vistas se refresquen siempre
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Determinar pestaña activa desde la URL (?tab=...)
$tab = $_GET['tab'] ?? '';
$tabs = ['inv','mm','mov','gal','add','cclase','ccond','cubi','rot'];
if (!in_array($tab, $tabs, true)) { $tab = 'inv'; }

// ===== Permisos (admin / consulta) =====
$is_admin   = auth_is_admin();
$restricted = ['add','cclase','ccond','cubi'];
// Si no es admin y pidió tab restringida, fuerzo inv
if (!$is_admin && in_array($tab, $restricted, true)) {
  $tab = 'inv';
}

// Mensajes flash
$flash_ok = flash_get('ok') ?? null;
?>
<!doctype html>
<html lang="es" data-theme="<?= (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark' : '' ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Inventario - Fullgas</title>
    <!-- Fuentes y Estilos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
    :root {
        /* Nueva paleta moderna y cohesiva */
        --primary: #0d9488;
        /* Teal 600 */
        --primary-hover: #0f766e;
        /* Teal 700 */
        --primary-light: #f0fdfa;
        /* Teal 50 */
        --secondary: #0284c7;
        /* Light Blue 600 */

        --bg-body: #f8fafc;
        /* Slate 50 */
        --bg-card: #ffffff;

        --text-main: #334155;
        /* Slate 700 */
        --text-muted: #64748b;
        /* Slate 500 */
        --text-dark: #0f172a;
        /* Slate 900 */

        --border-color: #e2e8f0;
        /* Slate 200 */

        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    /* Variables Dark Mode */
    body.dark-mode {
        --bg-body: #0f172a;
        /* Slate 900 */
        --bg-card: #1e293b;
        /* Slate 800 */
        --text-main: #cbd5e1;
        /* Slate 300 */
        --text-muted: #94a3b8;
        /* Slate 400 */
        --text-dark: #f8fafc;
        /* Slate 50 */
        --border-color: #334155;
        /* Slate 700 */
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-main);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Estilos del Navbar (Incluido desde navbar.php) */
    .modern-navbar {
        background-color: #0f172a;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 0.75rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 3px solid var(--primary);
    }

    .navbar-brand-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1.25rem;
        font-weight: 700;
        color: #f8fafc;
        text-decoration: none;
        letter-spacing: -0.5px;
    }

    .navbar-brand-custom:hover {
        color: var(--primary-light);
    }

    .brand-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
    }

    .user-badge {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 6px 16px;
        border-radius: 50px;
        color: #cbd5e1;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .role-badge {
        background: var(--primary);
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Contenedor Principal */
    .main-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 20px;
        flex-grow: 1;
        width: 100%;
    }

    /* Flash Messages */
    .flash-message {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-left: 4px solid var(--success);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        animation: slideIn 0.3s ease forwards;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .flash-message i.fa-check-circle {
        font-size: 1.5rem;
        color: var(--success);
    }

    .flash-text {
        flex: 1;
        color: var(--text-dark);
        font-weight: 500;
    }

    .btn-close-custom {
        background: transparent;
        border: none;
        color: var(--text-muted);
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background 0.2s;
    }

    .btn-close-custom:hover {
        background: var(--bg-body);
        color: var(--text-dark);
    }

    /* Tarjeta Principal */
    .inventory-card {
        background: var(--bg-card);
        border-radius: 16px;
        border: 1px solid var(--border-color);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        overflow: hidden;
    }

    /* Pestañas (Tabs) */
    .modern-tabs {
        background: white;
        padding: 16px 24px;
        display: flex;
        gap: 12px;
        border-bottom: 1px solid var(--border-color);
        overflow-x: auto;
    }

    .modern-tabs::-webkit-scrollbar {
        height: 4px;
    }

    .modern-tabs::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 4px;
    }

    .tab-link {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: var(--bg-body);
        border: 1px solid transparent;
        border-radius: 8px;
        color: var(--text-muted);
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .tab-link:hover {
        color: var(--primary);
        background: var(--primary-light);
    }

    .tab-link.active {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
        transform: translateY(-1px);
    }

    /* Área de Contenido de Pestañas */
    .tab-content-area {
        padding: 2rem;
        min-height: 500px;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.4s ease;
    }

    .tab-pane.show.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Footer Styles (Incluido desde footer.php) */
    .footer-fullgas {
        background-color: #0f172a;
        color: #cbd5e1;
        padding: 3rem 0 2rem;
        margin-top: auto;
        border-top: 4px solid var(--primary);
    }

    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 3rem;
        align-items: center;
    }

    .footer-section h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        margin-bottom: 1.2rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .footer-logo img {
        max-width: 180px;
        opacity: 0.9;
    }

    .footer-contact-list {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding-left: 0;
    }

    .footer-contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .footer-icon {
        background: rgba(255, 255, 255, 0.1);
        color: var(--primary);
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .footer-btn {
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.95rem;
        transition: color 0.2s;
    }

    .footer-btn:hover {
        color: white;
    }

    .footer-btn-primary {
        background: var(--primary);
        color: white !important;
        padding: 0.6rem 1.5rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: all 0.2s;
        text-decoration: none;
    }

    .footer-btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-2px);
    }

    /* Modal Eliminación */
    .modal-content {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .modal-header {
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem;
    }

    .modal-body {
        padding: 2rem;
    }

    .modal-footer {
        border-top: none;
        padding: 1.5rem;
        background: var(--bg-body);
        border-radius: 0 0 16px 16px;
    }

    .delete-icon-wrapper {
        width: 64px;
        height: 64px;
        background: #fee2e2;
        color: var(--danger);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto 1rem;
    }

    /* Estilos extras Dark Mode para elementos específicos */
    body.dark-mode .modern-tabs {
        background: var(--bg-card);
    }

    body.dark-mode .tab-link {
        background: var(--bg-body);
        color: var(--text-muted);
    }

    body.dark-mode .tab-link:hover {
        color: var(--primary-light);
        background: var(--primary-hover);
    }

    body.dark-mode .tab-link.active {
        background: var(--primary);
        color: white;
    }

    body.dark-mode .modal-content,
    body.dark-mode .dropdown-menu {
        background-color: var(--bg-card);
        border-color: var(--border-color);
        color: var(--text-main);
    }

    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
        border-color: var(--border-color);
        background-color: var(--bg-card);
    }

    body.dark-mode .dropdown-item {
        color: var(--text-main);
    }

    body.dark-mode .dropdown-item:hover {
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    body.dark-mode .flash-message {
        background-color: var(--bg-card);
        border-left: 4px solid var(--success);
        color: var(--text-main);
    }

    body.dark-mode .flash-text {
        color: var(--text-dark);
    }

    body.dark-mode #deleteItemName,
    body.dark-mode .modal-title {
        color: var(--text-dark) !important;
    }

    /* Responsivo */
    @media (max-width: 768px) {
        .main-container {
            margin: 1rem auto;
            padding: 0 10px;
        }

        .tab-content-area {
            padding: 1rem;
        }

        .modern-tabs {
            padding: 12px;
        }

        .tab-link {
            padding: 8px 14px;
            font-size: 0.85rem;
        }

        .user-badge span:first-child {
            display: none;
        }

        /* Ocultar nombre en móvil, dejar rol */
        .footer-grid {
            gap: 2rem;
            text-align: center;
        }

        .footer-contact-item {
            justify-content: center;
        }
    }
    </style>
</head>

<body class="<?= (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '' ?>">

    <!-- NAVBAR DESDE INCLUDES -->
    <?php include __DIR__.'/includes/navbar.php'; ?>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="main-container">

        <!-- FLASH MESSAGES PHP -->
        <?php if ($flash_ok): ?>
        <div class="flash-message" role="alert">
            <i class="fas fa-check-circle"></i>
            <span class="flash-text"><?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" class="btn-close-custom" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endif; ?>

        <!-- TARJETA DEL INVENTARIO -->
        <div class="inventory-card">

            <!-- PESTAÑAS MODERNAS -->
            <nav class="modern-tabs" id="tabs" role="tablist">
                <a class="tab-link <?= $tab==='inv'?'active':'' ?>" href="#inv" role="tab">
                    <i class="fas fa-boxes"></i> <span>Inventario</span>
                </a>

                <a class="tab-link <?= $tab==='mov'?'active':'' ?>" href="#mov" role="tab">
                    <i class="fas fa-exchange-alt"></i> <span>Movimientos</span>
                </a>

                <a class="tab-link <?= $tab==='mm'?'active':'' ?>" href="#mm" role="tab">
                    <i class="fas fa-chart-line"></i> <span>Mín/Máx</span>
                </a>

                <a class="tab-link <?= $tab==='rot'?'active':'' ?>" href="#rot" role="tab">
                    <i class="fas fa-people-arrows"></i> <span>Rotación</span>
                </a>

                <?php if ($is_admin): ?>
        
                <a class="tab-link <?= $tab==='cusr'?'active':'' ?>" href="#cusr" role="tab">
                    <i class="fas fa-users-cog"></i> <span>Usuarios</span>
                </a>

                <a class="tab-link <?= $tab==='cclase'?'active':'' ?>" href="#cclase" role="tab">
                    <i class="fas fa-tags"></i> <span>Clases</span>
                </a>

                <a class="tab-link <?= $tab==='ccond'?'active':'' ?>" href="#ccond" role="tab">
                    <i class="fas fa-check-circle"></i> <span>Condición</span>
                </a>

                <a class="tab-link <?= $tab==='cubi'?'active':'' ?>" href="#cubi" role="tab">
                    <i class="fas fa-map-marker-alt"></i> <span>Ubicaciones</span>
                </a>
                <?php endif; ?>
            </nav>

            <!-- CONTENIDO DE PESTAÑAS -->
            <div class="tab-content-area">
                <div class="tab-pane <?= $tab==='inv'?'show active':'' ?>" id="inv" role="tabpanel">
                    <?php include __DIR__.'/public/inventario.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='mov'?'show active':'' ?>" id="mov" role="tabpanel">
                    <?php include __DIR__.'/public/movimientos.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='mm'?'show active':'' ?>" id="mm" role="tabpanel">
                    <?php include __DIR__.'/public/minmax.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='rot'?'show active':'' ?>" id="rot" role="tabpanel">
                    <?php include __DIR__.'/public/rotacion.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='gal'?'show active':'' ?>" id="gal" role="tabpanel">
                    <?php include __DIR__.'/public/galeria.php'; ?>
                </div>

                <?php if ($is_admin): ?>
               <div class="tab-pane <?= $tab==='cusr'?'show active':'' ?>" id="cusr" role="tabpanel">
                    <?php include __DIR__.'/public/cat_usuarios.php'; ?>
                </div>
                <div class="tab-pane <?= $tab==='cclase'?'show active':'' ?>" id="cclase" role="tabpanel">
                    <?php include __DIR__.'/public/cat_clases.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='ccond'?'show active':'' ?>" id="ccond" role="tabpanel">
                    <?php include __DIR__.'/public/cat_condiciones.php'; ?>
                </div>

                <div class="tab-pane <?= $tab==='cubi'?'show active':'' ?>" id="cubi" role="tabpanel">
                    <?php include __DIR__.'/public/cat_ubicaciones.php'; ?>
                </div>

                
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FOOTER DESDE INCLUDES -->
    <?php include __DIR__.'/includes/footer.php'; ?>

    <!-- MODAL ELIMINACIÓN -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">
                        Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="delete-icon-wrapper">
                        <i class="bi bi-trash3-fill"></i>
                    </div>
                    <div id="deleteItemName" class="fw-bold fs-5 mb-2 text-dark">Nombre del registro</div>
                    <p class="text-muted mb-0">
                        <span class="text-danger fw-semibold">⚠️ Esta acción no se puede deshacer.</span><br>
                        ¿Estás seguro que deseas eliminar este registro permanentemente?
                    </p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    (function() {
        'use strict';

        // 1. Delegación para Pestañas (Tabs)
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.tab-link');
            if (!link) return;

            e.preventDefault();
            const tab = link.getAttribute('href').slice(1);
            activateTab(tab);

            const url = new URL(location.href);
            url.search = '';
            url.searchParams.set('tab', tab);
            url.hash = tab;
            history.replaceState({}, '', url);
        });

        function activateTab(tab) {
            // Lógica segura de activación
            document.querySelectorAll('.tab-link').forEach(a => a.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));

            const activeLink = document.querySelector(`.tab-link[href="#${tab}"]`);
            const activePane = document.getElementById(tab);

            if (activeLink) activeLink.classList.add('active');
            if (activePane) activePane.classList.add('show', 'active');

            // Carga de movimientos si existe
            if (tab === 'mov' && typeof window.loadMovimientos === 'function') {
                window.loadMovimientos();
            }
        }

        // 2. Delegación para el Botón Eliminar (Esto soluciona tu error de 'null')
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-action-delete');
            if (!btn) return;

            e.preventDefault();
            const form = btn.closest('form');
            if (!form) return;

            // Obtener nombre del item de forma segura
            const row = btn.closest('tr');
            const name = row ? (row.querySelector('.item-nombre')?.textContent.trim() || 'este registro') :
                'este registro';

            const deleteItemName = document.getElementById('deleteItemName');
            if (deleteItemName) deleteItemName.textContent = name;

            // Guardar el formulario para después
            window.currentFormToDelete = form;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });

        // 3. Confirmación del Modal
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (window.currentFormToDelete) {
                    ajaxDelete(window.currentFormToDelete);
                    bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                }
            });
        }
    })();

    // Función AJAX centralizada
    async function ajaxDelete(form) {
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            if (data.ok) {
                form.closest('tr').remove();
                showFlash('Registro eliminado correctamente');
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) {
            console.error(err);
        }
    }
    </script>

    <script src="assets/js/dark-mode.js"></script>

</body>

</html>


