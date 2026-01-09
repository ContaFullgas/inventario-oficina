<?php

//Archivo index.php

require_once __DIR__.'/config/auth.php';
auth_check(); // obliga a iniciar sesión ?>

<?php ob_start(); require_once __DIR__.'/config/db.php'; ?>
<?php require_once __DIR__.'/config/util.php'; ?>
<?php
// Evitar caché para que tablas y vistas se refresquen siempre
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Determinar pestaña activa desde la URL (?tab=...)
$tab = $_GET['tab'] ?? '';
$tabs = ['inv','mm','gal','add','cclase','ccond','cubi'];
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
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Inventario - Fullgas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    :root {
      --primary: #0f766e;
      --primary-dark: #115e59;
      --primary-light: #ccfbf1;
      --secondary: #0284c7;
      --dark: #1e293b;
      --darker: #0f172a;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --success: #059669;
      --warning: #f59e0b;
      --danger: #dc2626;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #0f766e 0%, #0284c7 100%);
      min-height: 100vh;
    }

    /* ===== NAVBAR ===== */
    .modern-navbar {
      /*background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);*/
      background-color: #000;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 3px solid var(--primary);
    }

    .navbar-brand-custom {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
      text-decoration: none;
    }

    .navbar-brand-custom:hover {
      color: var(--primary-light);
    }

    .brand-icon {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: white;
      box-shadow: 0 4px 12px rgba(15, 118, 110, 0.4);
    }

    .user-badge {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 8px 20px;
      border-radius: 50px;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    .user-badge i {
      font-size: 1.1rem;
    }

    .role-badge {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-logout {
      background: rgba(220, 38, 38, 0.1);
      border: 2px solid rgba(220, 38, 38, 0.3);
      color: #fca5a5;
      padding: 8px 24px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-logout:hover {
      background: var(--danger);
      border-color: var(--danger);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
    }

    /* ===== CONTAINER ===== */
    .main-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 20px;
    }

    /* ===== FLASH MESSAGES ===== */
    .flash-message {
      background: white;
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 4px solid var(--success);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease;
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

    .flash-message i {
      font-size: 1.5rem;
      color: var(--success);
    }

    .flash-message .flash-text {
      flex: 1;
      color: var(--gray-800);
      font-weight: 500;
    }

    .btn-close-custom {
      background: transparent;
      border: none;
      color: var(--gray-700);
      font-size: 1.2rem;
      cursor: pointer;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s ease;
    }

    .btn-close-custom:hover {
      background: var(--gray-200);
    }

    /* ===== CARD CONTAINER ===== */
    .inventory-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }

    /* ===== MODERN TABS ===== */
    .modern-tabs {
      background: linear-gradient(to right, var(--gray-100), var(--gray-50));
      padding: 12px 20px;
      display: flex;
      gap: 8px;
      border-bottom: 2px solid var(--gray-200);
      overflow-x: auto;
    }

    .modern-tabs::-webkit-scrollbar {
      height: 6px;
    }

    .modern-tabs::-webkit-scrollbar-thumb {
      background: var(--gray-300);
      border-radius: 10px;
    }

    .tab-link {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      background: transparent;
      border: none;
      border-radius: 10px;
      color: var(--gray-700);
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      transition: all 0.3s ease;
      white-space: nowrap;
      cursor: pointer;
    }

    .tab-link:hover {
      background: rgba(15, 118, 110, 0.1);
      color: var(--primary);
      transform: translateY(-2px);
    }

    .tab-link.active {
      background: white;
      color: var(--primary);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    .tab-link i {
      font-size: 1.1rem;
    }

    /* ===== TAB CONTENT ===== */
    .tab-content-area {
      padding: 2rem;
      min-height: 400px;
      background: var(--gray-50);
    }

    .tab-pane {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .tab-pane.show.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ===== STATS SECTION ===== */
    .stats-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 8px 20px rgba(15, 118, 110, 0.3);
    }

    .stats-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .stats-header p {
      margin: 8px 0 0 0;
      opacity: 0.9;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .main-container {
        padding: 0 10px;
        margin: 1rem auto;
      }

      .tab-content-area {
        padding: 1rem;
      }

      .user-badge {
        padding: 6px 12px;
        font-size: 0.85rem;
      }

      .btn-logout {
        padding: 6px 16px;
        font-size: 0.85rem;
      }

      .modern-tabs {
        padding: 8px 10px;
      }

      .tab-link {
        padding: 10px 16px;
        font-size: 0.875rem;
      }

      .navbar-brand-custom {
        font-size: 1.2rem;
      }

      .brand-icon {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
      }
    }

    /* ===== UTILITIES ===== */
    .icon-gradient {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

/* Estilos del footer mejorado */
.footer-fullgas {
  background-color: #000;
  color: white;
  padding: 2rem 0;
  margin-top: 3rem;
}

.footer-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
}

.footer-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  align-items: start;
}

.footer-section {
  text-align: center;
}

.footer-section h3 {
  font-size: 1.25rem;
  margin-bottom: 1rem;
  color: #0f766e;
}

.footer-logo {
  display: flex;
  justify-content: center;
  align-items: center;
}

.footer-logo img {
  max-width: 200px;
  height: auto;
}

.footer-contact-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.footer-contact-item {
  display: flex;
  flex-direction: row;
  gap: 0.75rem;
}

.footer-icon {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.footer-btn {
  background: transparent;
  border: 2px solid #0f766e;
  color: white;
  padding: 0.5rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  display: inline-block;
}

.footer-btn:hover {
  background: #0f766e;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(15, 118, 110, 0.4);
}

.footer-btn-primary {
  background: linear-gradient(135deg, #0f766e, #0284c7);
  border: none;
  padding: 0.65rem 1.75rem;
  font-weight: 600;
}

.footer-btn-primary:hover {
  background: linear-gradient(135deg, #115e59, #0369a1);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(15, 118, 110, 0.5);
}

@media (max-width: 768px) {
  .footer-grid {
    grid-template-columns: 1fr;
    gap: 2.5rem;
  }
  
  .footer-fullgas {
    padding: 1.5rem 0;
  }
}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="modern-navbar">
  <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
      <a href="index.php" class="navbar-brand-custom">
        <div class="brand-icon">
          <i class="bi bi-fuel-pump-fill"></i>
        </div>
        <!-- <img src="assets\logo_fg_nav2.png" alt=""> -->
        <span>Fullgas Inventario</span>
      </a>

      <div class="d-flex align-items-center gap-3">
        <?php $u = auth_user(); ?>
        <?php if ($u): ?>
          <div class="user-badge">
            <i class="fas fa-user-circle"></i>
            <span><?=h($u['usuario'])?></span>
            <span class="role-badge"><?=h($u['rol'])?></span>
          </div>
          <a class="btn btn-logout" href="public/logout.php">
            <i class="fas fa-sign-out-alt"></i> Salir
          </a>
        <?php else: ?>
          <a class="btn btn-outline-light" href="login.php">
            <i class="fas fa-sign-in-alt"></i> Ingresar
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- MAIN CONTAINER -->
<div class="main-container">
  
  <!-- FLASH MESSAGES -->
  <?php if ($flash_ok): ?>
    <div class="flash-message" role="alert">
      <i class="fas fa-check-circle"></i>
      <span class="flash-text"><?=htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8')?></span>
      <button type="button" class="btn-close-custom" onclick="this.parentElement.remove()">
        <i class="fas fa-times"></i>
      </button>
    </div>
  <?php endif; ?>

  <!-- INVENTORY CARD -->
  <div class="inventory-card">
    
    <!-- MODERN TABS -->
    <nav class="modern-tabs" id="tabs" role="tablist">
      <a class="tab-link <?= $tab==='inv'?'active':'' ?>" href="#inv" role="tab">
        <i class="fas fa-boxes"></i>
        <span>Inventario</span>
      </a>
      
      <a class="tab-link <?= $tab==='mm'?'active':'' ?>" href="#mm" role="tab">
        <i class="fas fa-chart-line"></i>
        <span>Mín/Máx</span>
      </a>

      <?php if ($is_admin): ?>
        <a class="tab-link <?= $tab==='add'?'active':'' ?>" href="#add" role="tab">
          <i class="fas fa-plus-circle"></i>
          <span>Agregar</span>
        </a>

        <a class="tab-link <?= $tab==='cclase'?'active':'' ?>" href="#cclase" role="tab">
          <i class="fas fa-tags"></i>
          <span>Clases</span>
        </a>
        
        <a class="tab-link <?= $tab==='ccond'?'active':'' ?>" href="#ccond" role="tab">
          <i class="fas fa-check-circle"></i>
          <span>Condición/Estado</span>
        </a>
        
        <a class="tab-link <?= $tab==='cubi'?'active':'' ?>" href="#cubi" role="tab">
          <i class="fas fa-map-marker-alt"></i>
          <span>Ubicaciones</span>
        </a>
      <?php endif; ?>
    </nav>

    <!-- Aquí van las pestañas -->
    <div class="tab-content-area">
      <div class="tab-pane <?= $tab==='inv'?'show active':'' ?>" id="inv" role="tabpanel">
        <?php include __DIR__.'/public/inventario.php'; ?>
      </div>
      
      <div class="tab-pane <?= $tab==='mm'?'show active':'' ?>" id="mm" role="tabpanel">
        <?php include __DIR__.'/public/minmax.php'; ?>
      </div>
      
      <div class="tab-pane <?= $tab==='gal'?'show active':'' ?>" id="gal" role="tabpanel">
        <?php include __DIR__.'/public/galeria.php'; ?>
      </div>

      <?php if ($is_admin): ?>
        <div class="tab-pane <?= $tab==='add'?'show active':'' ?>" id="add" role="tabpanel">
          <?php include __DIR__.'/public/agregar.php'; ?>
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

      <!--Aquí empieza el footer -->
<footer class="footer-fullgas">
  <div class="footer-container">
    <div class="footer-grid">
      
      <!-- Jefe Directo -->
      <div class="footer-section">
        <h3>Jefe Directo</h3>
        <p style="margin-bottom: 1.5rem;"><strong>Ing. Ismael Santiago</strong></p>
        <a href="mailto:isantiago@fullgas.com.mx" class="footer-btn footer-btn-primary">
          <i class="bi bi-envelope-at-fill"></i> Enviar correo
        </a>
      </div>

      <!-- Logo -->
      <div class="footer-section footer-logo">
        <img src="assets/logo_footer.jpeg" alt="Fullgas Logo">
      </div>

      <!-- Equipo de soporte -->
      <div class="footer-section">
        <h3>Equipo de Soporte</h3>
        <ul class="footer-contact-list">
          <li class="footer-contact-item">
            <span class="footer-icon"><i class="bi bi-envelope"></i></span>
            <a href="mailto:contabsistemas3@fullgas.com.mx" class="footer-btn">
              M. E. Sergio Leon
            </a>
          </li>
          <li class="footer-contact-item">
            <span class="footer-icon"><i class="bi bi-envelope"></i></span>
            <a href="mailto:contabsistemas7@fullgas.com.mx" class="footer-btn">
              Ing. Yaneli Cel
            </a>
          </li>
          <li class="footer-contact-item">
            <span class="footer-icon"><i class="bi bi-envelope"></i></span>
            <a href="mailto:contabsistemas1@fullgas.com.mx" class="footer-btn">
              Miguel Alonso
            </a>
          </li>
          <li class="footer-contact-item">
            <span class="footer-icon"><i class="bi bi-envelope"></i></span>
            <a href="mailto:contabsistemas8@fullgas.com.mx" class="footer-btn">
              I.S.C. Isaac Varguez
            </a>
          </li>
          <li class="footer-contact-item">
            <span class="footer-icon"><i class="bi bi-envelope"></i></span>
            <a href="mailto:contabsistemas5@fullgas.com.mx" class="footer-btn">
              Ing. Leonardo Dzul
            </a>
          </li>
        </ul>
      </div>

    </div>
  </div>
</footer>

 <!--Modal eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">

        <div class="delete-icon-wrapper mb-3">
          <i class="bi bi-trash-fill"></i>
        </div>

        <div id="deleteItemName" class="fw-bold fs-5 mb-2">Nombre del registro</div>

        <p class="delete-warning-text">
          <span class="delete-warning-highlight text-danger fw-semibold">⚠️ Esta acción no se puede deshacer.</span><br>
          ¿Estás seguro que deseas eliminar este registro permanentemente?
        </p>

      </div>

      <div class="modal-footer justify-content-between">

        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          <span>Cancelar</span>
        </button>

        <button type="button" class="btn btn-delete" id="confirmDeleteBtn">
          <i class="bi bi-trash-fill"></i>
          <span>Eliminar</span>
        </button>

      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Control de tabs -->
<script>
(function(){
  const links = document.querySelectorAll('#tabs .tab-link');
  const panes = document.querySelectorAll('.tab-content-area .tab-pane');

  // Permisos desde PHP
  const IS_ADMIN = <?= $is_admin ? 'true' : 'false' ?>;
  const RESTRICTED = ['add','cclase','ccond','cubi'];
  
  function safeTab(tab){ 
    return (!IS_ADMIN && RESTRICTED.includes(tab)) ? 'inv' : tab; 
  }

  function activate(tab) {
    // Desactivar todo
    links.forEach(a => a.classList.remove('active'));
    panes.forEach(p => p.classList.remove('show','active'));

    // Activar link + pane objetivo
    const link = document.querySelector('#tabs .tab-link[href="#'+tab+'"]');
    const pane = document.getElementById(tab);
    
    if (link) link.classList.add('active');
    if (pane) {
      pane.classList.add('show','active');
    }
  }

  // Click en tabs
links.forEach(a => {
  a.addEventListener('click', (ev) => {
    ev.preventDefault();
    let tab = a.getAttribute('href').slice(1);
    tab = safeTab(tab);
    activate(tab);

    // ❗ Limpiar la query para no arrastrar filtros de otras pestañas, especificamente de la paginación de inventario a min/max
    const url = new URL(location.href);
    url.search = '';                 // <- limpia todos los parámetros
    url.searchParams.set('tab', tab);
    url.hash = tab;
    history.replaceState({}, '', url);
  });
});


  // Activación inicial por ?tab= o #hash (default inv)
  const params = new URLSearchParams(location.search);
  let initial = params.get('tab') || (location.hash ? location.hash.slice(1) : 'inv');
  initial = safeTab(initial);
  activate(initial);
})();
</script>

<!-- Agregar esto ANTES del cierre de </body> en index.php -->

<script>
// ========================================
// SCRIPT UNIVERSAL PARA MODAL DE ELIMINACIÓN
// Cargado UNA SOLA VEZ desde index.php
// ========================================

(function() {
  'use strict';
  
  console.log('🗑️ Sistema de eliminación cargado desde index.php');
  
  // Esperar a que todo esté listo
  function init() {
    const deleteModal = document.getElementById('deleteModal');
    
    if (!deleteModal) {
      console.log('⚠️ Modal #deleteModal no encontrado en esta pestaña');
      return;
    }
    
    console.log('✅ Modal encontrado, configurando...');
    
    const deleteItemName = document.getElementById('deleteItemName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    let currentForm = null;
    let bsDeleteModal = null;
    
    // Obtener o crear instancia del modal
    function getModalInstance() {
      if (!bsDeleteModal && window.bootstrap && bootstrap.Modal) {
        bsDeleteModal = new bootstrap.Modal(deleteModal, {
          backdrop: true,
          keyboard: true,
          focus: true
        });
      }
      return bsDeleteModal;
    }
    
    // Limpiar backdrops
    function cleanBackdrops() {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      backdrops.forEach(backdrop => backdrop.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }
    
    // Interceptar clicks en botones de eliminar
    document.body.addEventListener('click', function(e) {
      const deleteBtn = e.target.closest('.btn-action-delete');
      
      if (!deleteBtn) return;
      
      console.log('🔴 Click detectado');
      e.preventDefault();
      e.stopPropagation();
      
      // Limpiar backdrops previos
      cleanBackdrops();
      
      // Obtener formulario
      currentForm = deleteBtn.closest('form');
      
      if (!currentForm) {
        console.error('❌ Sin formulario');
        return;
      }
      
      console.log('✅ Formulario OK');
      
      // Obtener nombre del item
      const row = deleteBtn.closest('tr');
      let itemName = 'este registro';
      
      if (row) {
        const nameEl = row.querySelector('.item-nombre') || 
                      row.querySelector('td:first-child');
        
        if (nameEl) {
          itemName = nameEl.textContent.trim();
        }
      }
      
      console.log('📝 Eliminando:', itemName);
      
      // Actualizar modal
      if (deleteItemName) {
        deleteItemName.textContent = itemName;
      }
      
      // Mostrar modal
      const modal = getModalInstance();
      if (modal) {
        try {
          modal.show();
          console.log('✅ Modal abierto');
        } catch (error) {
          console.error('❌ Error:', error);
        }
      }
    }, true);
    
    // Confirmar eliminación
    if (confirmDeleteBtn) {
      confirmDeleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('✅ Confirmado');
        
        if (!currentForm) return;
        
        const modal = getModalInstance();
        if (modal) {
          modal.hide();
        }
        
        setTimeout(function() {
          cleanBackdrops();
          // currentForm.submit();
          // llamada a metodo ajax para eliminar en lugar de post
          ajaxDelete(currentForm);
        }, 300);
      });
    }
    
    // Cancelar
    const cancelBtn = deleteModal.querySelector('.btn-cancel');
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function() {
        console.log('❌ Cancelado');
        currentForm = null;
        
        const modal = getModalInstance();
        if (modal) {
          modal.hide();
        }
        
        setTimeout(cleanBackdrops, 300);
      });
    }
    
    // Eventos del modal
    deleteModal.addEventListener('hidden.bs.modal', function() {
      console.log('🔒 Modal cerrado');
      currentForm = null;
      cleanBackdrops();
    });
    
    console.log('✅ Sistema configurado');
  }
  
  // Inicializar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  
  // Re-inicializar cuando cambie el contenido (por si las pestañas se cargan dinámicamente)
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.addedNodes.length) {
        mutation.addedNodes.forEach(function(node) {
          if (node.id === 'deleteModal') {
            console.log('🔄 Modal detectado, re-inicializando');
            setTimeout(init, 100);
          }
        });
      }
    });
  });
  
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
  
  //Funcion ajax para eliminar articulos sin recargar la pagina y perder los filtros
  async function ajaxDelete(form) {
  const url = form.getAttribute('action');
  const formData = new FormData(form);

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const text = await response.text();

    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      console.error('Respuesta no JSON:', text);
      alert('Sesión expirada o error de permisos');
      return;
    }

    if (!data.ok) {
      alert(data.message || 'Error al eliminar');
      return;
    }

    // 🔥 Quitar fila
    const row = form.closest('tr');
    if (row) {
      row.style.transition = 'all 0.3s ease';
      row.style.opacity = '0';
      row.style.transform = 'scale(0.95)';
      setTimeout(() => row.remove(), 300);
    }

    showFlash('Registro eliminado correctamente');

  } catch (error) {
    console.error(error);
    alert('Error de comunicación con el servidor');
  }
}


function showFlash(message) {
  const container = document.querySelector('.main-container');
  if (!container) return;

  const div = document.createElement('div');
  div.className = 'flash-message';
  div.innerHTML = `
    <i class="fas fa-check-circle"></i>
    <span class="flash-text">${message}</span>
    <button class="btn-close-custom"><i class="fas fa-times"></i></button>
  `;

  container.prepend(div);

  div.querySelector('.btn-close-custom')
    .addEventListener('click', () => div.remove());

  setTimeout(() => div.remove(), 4000);
}


})();
</script>

</body>
</html>