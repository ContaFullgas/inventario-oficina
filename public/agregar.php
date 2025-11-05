<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
auth_check();
auth_require_admin();

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
$conds  = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll();
$ubis   = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll();
?>
<style>
/* Formulario de búsqueda mejorado */
#inventario-form2 .input-group-text {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  border: none;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(243,156,18,0.3);
}

#inventario-form2 .form-control:focus,
#inventario-form2 .form-select:focus {
  border-color: #f39c12;
  box-shadow: 0 0 0 0.2rem rgba(243,156,18,0.15);
}

#inventario-form2 .btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
}

#inventario-form2 .btn-success {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  box-shadow: 0 4px 12px rgba(39,174,96,0.3);
}

#inventario-form2 .btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(39,174,96,0.4);
}
</style>
<form id="inventario-form2" method="post" enctype="multipart/form-data" class="row g-3" action="public/agregar_guardar.php">
  <?=csrf_field()?>

  <!-- Grupo 1: Nombre y Clase (2 inputs) -->
  <div class="col-md-6">
    <div class="input-group" >
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-clipboard2-fill"></i></label>
      <input type="text" name="nombre" class="form-control" placeholder="Nombre del articulo/Producto" required>
    </div>
  </div>

  <div class="col-md-6">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-tag-fill"></i></label>
      <select name="clase_id" class="form-select">
        <option value="">Selecciona una Clase</option>
        <?php foreach($clases as $c): ?>
          <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <span class="input-group-text btn btn-success"><a href="index.php?tab=cclase#cclase" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
    </div>
  </div>

  <!-- Grupo 2: Cantidad, Min Stock y Max Stock (3 inputs) -->
  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-layers-fill"></i></label>
      <input type="number" name="cantidad" class="form-control" value="0" min="0" placeholder="Stock">
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-arrow-down-circle-fill"></i></label>
      <input type="number" name="min_stock" class="form-control" value="0" min="0" placeholder="Cantidad Minima">
    </div>
  </div>

  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-arrow-up-circle-fill"></i></label>
      <input type="number" name="max_stock" class="form-control" value="0" min="0" placeholder="Cantidad Maxima">
    </div>
  </div>

  <!-- Grupo 3: Condición, Ubicación e Imagen (3 inputs) -->
  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-check-circle-fill"></i></label>
      <select name="condicion_id" class="form-select">
        <option value="">Indica el estado</option>
        <?php foreach($conds as $c): ?>
          <option value="<?=$c['id']?>"><?=h($c['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <span class="input-group-text btn btn-success"><a href="index.php?tab=ccond#ccond" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-geo-alt-fill"></i></label>
      <select name="ubicacion_id" class="form-select">
        <option value="">Elegir Ubicación</option>
        <?php foreach($ubis as $u): ?>
          <option value="<?=$u['id']?>"><?=h($u['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <span class="input-group-text btn btn-success"><a href="index.php?tab=cubi#cubi" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
    </div>
  </div>

  <div class="col-md-4">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;"><i class="bi bi-image-fill"></i></label>
      <input type="file" name="imagen" class="form-control" accept="image/*">
    </div>
  </div>

  <!-- Grupo 4: Notas (textarea solo) -->
  <div class="col-12">
    <div class="list-group">
      <label class="list-group-item text-white input-group-text" style="background-color: #F59E0B;"><i class="bi bi-journal-text"></i> Notas</label>
      <textarea name="notas" class="list-group-item mt-2" rows="3"></textarea>
    </div>
  </div>

  <!-- Botón de guardar -->
  <div class="col-12">
    <button class="btn btn-success"><i class="bi bi-floppy2-fill"></i> Guardar</button>
  </div>
</form>