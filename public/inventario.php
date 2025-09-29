<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase_id = (isset($_GET['clase_id']) && $_GET['clase_id'] !== '') ? (int)$_GET['clase_id'] : null;

$sql = "SELECT i.*,
               c1.nombre AS clase_nombre,
               c2.nombre AS condicion_nombre,
               c3.nombre AS ubicacion_nombre
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE 1";
$params = [];

if ($q !== '') {
  $sql .= " AND (i.nombre LIKE :q OR c1.nombre LIKE :q OR c2.nombre LIKE :q OR c3.nombre LIKE :q)";
  $params[':q'] = "%$q%";
}
if (!is_null($clase_id)) {
  $sql .= " AND i.clase_id = :cid";
  $params[':cid'] = $clase_id;
}
$sql .= " ORDER BY i.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
?>
<form id="inventario-form" class="row gy-2 gx-2 align-items-end mb-3" method="get" action="index.php#inv">
  <!-- Mantener la pestaña activa al enviar -->
  <input type="hidden" name="tab" value="inv">
  <div class="col-md-5">
    <label class="form-label">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, clase, condición o ubicación" value="<?=h($q)?>">
    <!-- <small class="text-muted">La búsqueda se aplica automáticamente.</small> -->
  </div>
  <div class="col-md-5">
    <label class="form-label">Clase</label>
    <select name="clase_id" class="form-select">
      <option value="">(Todas)</option>
      <?php foreach ($clases as $c): ?>
        <option value="<?=$c['id']?>" <?=$clase_id===$c['id']?'selected':''?>><?=h($c['nombre'])?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex gap-2">
    <!-- Botón opcional por accesibilidad, pero ya no es necesario usarlo -->
    <!-- <button class="btn btn-primary w-100" type="submit">Filtrar</button> -->
    <a class="btn btn-outline-secondary w-100" href="index.php?tab=inv#inv">Limpiar</a>
  </div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th>Imagen</th>
      <th>Nombre</th>
      <th>Clase</th>
      <th>Ubicación</th>
      <th>Condición</th>
      <th>Stock</th>
      <th>Mín</th>
      <th>Máx</th>
      <th>Estado</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it):
      $estado = 'OK'; $badge = 'bg-success';
      if ((int)$it['cantidad'] <= (int)$it['min_stock']) { $estado='Reponer'; $badge='bg-danger'; }
      elseif ((int)$it['cantidad'] < (int)$it['max_stock']) { $estado='Bajo'; $badge='bg-warning text-dark'; }
    ?>
    <tr>
      <td style="width:72px;">
        <?php if (!empty($it['imagen'])): ?>
          <img src="../uploads/<?=h($it['imagen'])?>" class="img-thumbnail" style="width:64px;height:64px;object-fit:cover;">
        <?php else: ?>
          <div class="bg-secondary" style="width:64px;height:64px;border-radius:.5rem;"></div>
        <?php endif; ?>
      </td>
      <td><strong><?=h($it['nombre'])?></strong><br><small class="text-muted"><?=h($it['notas'] ?? '')?></small></td>
      <td><?=h($it['clase_nombre'] ?? '')?></td>
      <td><?=h($it['ubicacion_nombre'] ?? '')?></td>
      <td><?=h($it['condicion_nombre'] ?? '')?></td>
      <td><?=intval($it['cantidad'])?></td>
      <td><?=intval($it['min_stock'])?></td>
      <td><?=intval($it['max_stock'])?></td>
      <td><span class="badge <?=$badge?>"><?=$estado?></span></td>
      <td>
        <a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?=intval($it['id'])?>">Editar</a>
        <form action="eliminar.php" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este registro?');">
          <?=csrf_field()?>
          <input type="hidden" name="id" value="<?=intval($it['id'])?>">
          <button class="btn btn-sm btn-outline-danger">Eliminar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<script>
(function(){
  const form = document.getElementById('inventario-form');
  const q = form.querySelector('input[name="q"]');
  const clase = form.querySelector('select[name="clase_id"]');
  let t;

  // Auto-submit con debounce al escribir
  q.addEventListener('input', function(){
    clearTimeout(t);
    t = setTimeout(function(){ form.submit(); }, 400);
  });

  // Auto-submit al cambiar la clase
  clase.addEventListener('change', function(){
    form.submit();
  });
})();
</script>
