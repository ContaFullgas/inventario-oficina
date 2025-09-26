<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
?>
<form method="post" enctype="multipart/form-data" class="row g-3" action="agregar_guardar.php">
  <?=csrf_field()?>
  <div class="col-md-6">
    <label class="form-label">Nombre *</label>
    <input type="text" name="nombre" class="form-control" required>
  </div>
  <div class="col-md-6">
    <label class="form-label">Clase</label>
    <input type="text" name="clase" class="form-control">
  </div>
  <div class="col-md-3">
    <label class="form-label">Stock total</label>
    <input type="number" name="cantidad" class="form-control" value="0" min="0">
  </div>
  <div class="col-md-3">
    <label class="form-label">Condición</label>
    <input type="text" name="condicion" class="form-control" placeholder="Bueno/Regular/Malo">
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
    <input type="text" name="ubicacion" class="form-control">
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
