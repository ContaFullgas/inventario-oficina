<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
$conds  = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll();
$ubis   = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll();
?>
<form method="post" enctype="multipart/form-data" class="row g-3" action="agregar_guardar.php">
  <?=csrf_field()?>

  <div class="col-md-6">
    <label class="form-label">Nombre *</label>
    <input type="text" name="nombre" class="form-control" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Clase</label>
    <select name="clase_id" class="form-select">
      <option value="">(Sin clase)</option>
      <?php foreach($clases as $c): ?>
        <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
      <?php endforeach; ?>
    </select>
    <small><a href="index.php?tab=cclase#cclase">Gestionar clases</a></small>
  </div>

  <div class="col-md-3">
    <label class="form-label">Stock total</label>
    <input type="number" name="cantidad" class="form-control" value="0" min="0">
  </div>

  <div class="col-md-3">
    <label class="form-label">Condición/Estado</label>
    <select name="condicion_id" class="form-select">
      <option value="">(Sin condición)</option>
      <?php foreach($conds as $c): ?>
        <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
      <?php endforeach; ?>
    </select>
    <small><a href="index.php?tab=ccond#ccond">Gestionar condiciones</a></small>
  </div>

  <div class="col-md-3">
    <label class="form-label">Mínimo</label>
    <input type="number" name="min_stock" class="form-control" value="0" min="0">
  </div>

  <div class="col-md-3">
    <label class="form-label">Máximo</label>
    <input type="number" name="max_stock" class="form-control" value="0" min="0">
  </div>

  <div class="col-md-6">
    <label class="form-label">Ubicación</label>
    <select name="ubicacion_id" class="form-select">
      <option value="">(Sin ubicación)</option>
      <?php foreach($ubis as $u): ?>
        <option value="<?=$u['id']?>"><?=h($u['nombre'])?></option>
      <?php endforeach; ?>
    </select>
    <small><a href="index.php?tab=cubi#cubi">Gestionar ubicaciones</a></small>
  </div>

  <div class="col-md-6">
    <label class="form-label">Imagen</label>
    <input type="file" name="imagen" class="form-control" accept="image/*">
  </div>

  <div class="col-12">
    <label class="form-label">Notas</label>
    <textarea name="notas" class="form-control" rows="3"></textarea>
  </div>

  <div class="col-12">
    <button class="btn btn-success">Guardar</button>
  </div>
</form>
