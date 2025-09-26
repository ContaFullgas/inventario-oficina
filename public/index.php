<?php ob_start(); require_once __DIR__.'/../config/db.php'; ?>
<?php require_once __DIR__.'/../config/util.php'; ?>
<?php
// Evitar cache del navegador para que la tabla siempre se renderice fresca
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Tab activa y flash
$tab = $_GET['tab'] ?? '';
$tabs = ['inv','mm','gal','add'];
if (!in_array($tab, $tabs, true)) { $tab = 'inv'; }
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
      <?=htmlspecialchars($flash_ok)?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <ul class="nav nav-tabs" id="tabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tab==='inv'?'active':'' ?>" id="inv-tab" data-bs-toggle="tab" data-bs-target="#inv" type="button">Inventario</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tab==='mm'?'active':'' ?>" id="mm-tab" data-bs-toggle="tab" data-bs-target="#mm" type="button">Mín/Máx</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tab==='gal'?'active':'' ?>" id="gal-tab" data-bs-toggle="tab" data-bs-target="#gal" type="button">Galería</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?= $tab==='add'?'active':'' ?>" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button">Agregar</button>
    </li>
  </ul>

  <div class="tab-content border border-top-0 p-3">
    <div class="tab-pane fade <?= $tab==='inv'?'show active':'' ?>" id="inv"><?php include __DIR__.'/inventario.php'; ?></div>
    <div class="tab-pane fade <?= $tab==='mm'?'show active':'' ?>" id="mm"><?php include __DIR__.'/minmax.php'; ?></div>
    <div class="tab-pane fade <?= $tab==='gal'?'show active':'' ?>" id="gal"><?php include __DIR__.'/galeria.php'; ?></div>
    <div class="tab-pane fade <?= $tab==='add'?'show active':'' ?>" id="add"><?php include __DIR__.'/agregar.php'; ?></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Fuerza la activación de pestaña por ?tab= -->
<script>
(function(){
  var params = new URLSearchParams(window.location.search);
  var tab = params.get('tab');
  if (tab) {
    var btn = document.querySelector('button[data-bs-target="#'+tab+'"]');
    if (btn && window.bootstrap && bootstrap.Tab) {
      new bootstrap.Tab(btn).show();
    } else {
      document.querySelectorAll('.nav-link').forEach(el=>el.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(el=>el.classList.remove('show','active'));
      var pane = document.querySelector('#'+tab);
      if (pane) {
        var b = document.querySelector('button[data-bs-target="#'+tab+'"]');
        if (b) b.classList.add('active');
        pane.classList.add('show','active');
      }
    }
  }
})();
</script>
</body>
</html>
