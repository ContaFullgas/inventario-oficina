<?php ob_start(); require_once __DIR__.'/../config/db.php'; ?>
<?php require_once __DIR__.'/../config/util.php'; ?>
<?php
// Evitar caché para que tablas y vistas se refresquen siempre
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Determinar pestaña activa desde la URL (?tab=...)
$tab = $_GET['tab'] ?? '';
$tabs = ['inv','mm','gal','add','cclase','ccond','cubi'];
if (!in_array($tab, $tabs, true)) { $tab = 'inv'; }

// Mensajes flash
$flash_ok = flash_get('ok') ?? null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inventario de Oficina</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark" style="background:#3B1C32;">
  <div class="container-fluid">
    <span class="navbar-brand mb-0 h1 text-warning">Inventario de Oficina</span>
  </div>
</nav>

<div class="container py-4">
  <?php if ($flash_ok): ?> 
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?=htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8')?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- NAV TABS como <a href="#..."> -->
  <ul class="nav nav-tabs" id="tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='inv'?'active':'' ?>" href="#inv" role="tab">Inventario</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='mm'?'active':'' ?>" href="#mm" role="tab">Mín/Máx</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='gal'?'active':'' ?>" href="#gal" role="tab">Galería</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='add'?'active':'' ?>" href="#add" role="tab">Agregar</a>
    </li>

    <li class="nav-item ms-3"><span class="nav-link disabled text-muted">Catálogos</span></li>

    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='cclase'?'active':'' ?>" href="#cclase" role="tab">Clases</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='ccond'?'active':'' ?>" href="#ccond" role="tab">Condición/Estado</a>
    </li>
    <li class="nav-item" role="presentation">
      <a class="nav-link <?= $tab==='cubi'?'active':'' ?>" href="#cubi" role="tab">Ubicaciones</a>
    </li>
  </ul>

  <div class="tab-content border border-top-0 p-3">
    <div class="tab-pane fade <?= $tab==='inv'?'show active':'' ?>" id="inv" role="tabpanel">
      <?php include __DIR__.'/inventario.php'; ?>
    </div>
    <div class="tab-pane fade <?= $tab==='mm'?'show active':'' ?>" id="mm" role="tabpanel">
      <?php include __DIR__.'/minmax.php'; ?>
    </div>
    <div class="tab-pane fade <?= $tab==='gal'?'show active':'' ?>" id="gal" role="tabpanel">
      <?php include __DIR__.'/galeria.php'; ?>
    </div>
    <div class="tab-pane fade <?= $tab==='add'?'show active':'' ?>" id="add" role="tabpanel">
      <?php include __DIR__.'/agregar.php'; ?>
    </div>

    <div class="tab-pane fade <?= $tab==='cclase'?'show active':'' ?>" id="cclase" role="tabpanel">
      <?php include __DIR__.'/cat_clases.php'; ?>
    </div>
    <div class="tab-pane fade <?= $tab==='ccond'?'show active':'' ?>" id="ccond" role="tabpanel">
      <?php include __DIR__.'/cat_condiciones.php'; ?>
    </div>
    <div class="tab-pane fade <?= $tab==='cubi'?'show active':'' ?>" id="cubi" role="tabpanel">
      <?php include __DIR__.'/cat_ubicaciones.php'; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Control de tabs sin depender de data-bs-toggle (evita conflictos con formularios) -->
<script>
(function(){
  const links = document.querySelectorAll('#tabs a.nav-link');
  const panes = document.querySelectorAll('.tab-content .tab-pane');

  function activate(tab) {
    // Desactivar todo
    links.forEach(a => a.classList.remove('active'));
    panes.forEach(p => p.classList.remove('show','active'));

    // Activar link + pane objetivo
    const link = document.querySelector('#tabs a.nav-link[href="#'+tab+'"]');
    const pane = document.getElementById(tab);
    if (link) link.classList.add('active');
    if (pane) pane.classList.add('show','active');
  }

  // Click en tabs: evitar submit/navegación y activar
  links.forEach(a => {
    a.addEventListener('click', (ev) => {
      ev.preventDefault();
      const tab = a.getAttribute('href').slice(1); // "#inv" -> "inv"
      activate(tab);
      // Mantener ?tab= y #hash
      const url = new URL(location.href);
      url.searchParams.set('tab', tab);
      url.hash = tab;
      history.replaceState({}, '', url);
    });
  });

  // Activación inicial por ?tab= o #hash (default inv)
  const params = new URLSearchParams(location.search);
  const initial = params.get('tab') || (location.hash ? location.hash.slice(1) : 'inv');
  activate(initial);
})();
</script>
</body>
</html>
