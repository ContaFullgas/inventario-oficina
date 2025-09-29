<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

// Lee filtro de estado (reponer|bajo|ok)
$estado = isset($_GET['estado']) ? strtolower(trim($_GET['estado'])) : '';
$valid = ['reponer','bajo','ok'];
if (!in_array($estado, $valid, true)) { $estado = ''; }

$sql = "SELECT
          i.*,
          c1.nombre AS clase_nombre,
          c2.nombre AS condicion_nombre,
          c3.nombre AS ubicacion_nombre,
          CASE
            WHEN i.cantidad <= i.min_stock THEN 'Reponer'
            WHEN i.cantidad <  i.max_stock THEN 'Bajo'
            ELSE 'OK'
          END AS estado_calc
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE 1";

if ($estado !== '') {
  if ($estado === 'reponer') {
    $sql .= " AND (i.cantidad <= i.min_stock)";
  } elseif ($estado === 'bajo') {
    $sql .= " AND (i.cantidad > i.min_stock AND i.cantidad < i.max_stock)";
  } elseif ($estado === 'ok') {
    $sql .= " AND (i.cantidad >= i.max_stock)";
  }
}

$sql .= " ORDER BY
            (i.cantidad <= i.min_stock) DESC,
            (i.cantidad <  i.max_stock) DESC,
            i.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute([]);
$items = $stmt->fetchAll();
?>
<!-- Filtro de Estado (auto-submit) -->
<form id="minmax-form" class="row gy-2 gx-2 align-items-end mb-3" method="get" action="index.php#mm">
  <input type="hidden" name="tab" value="mm">
  <div class="col-md-4">
    <label class="form-label">Estado</label>
    <select name="estado" class="form-select">
      <option value="" <?= $estado===''?'selected':'' ?>>(Todos)</option>
      <option value="reponer" <?= $estado==='reponer'?'selected':'' ?>>Reponer</option>
      <option value="bajo" <?= $estado==='bajo'?'selected':'' ?>>Bajo</option>
      <option value="ok" <?= $estado==='ok'?'selected':'' ?>>OK</option>
    </select>
  </div>
  <div class="col-md-4 d-flex gap-2">
    <!-- <button class="btn btn-primary w-100" type="submit">Aplicar</button> -->
    <a class="btn btn-outline-secondary w-100" href="index.php?tab=mm#mm">Limpiar</a>
  </div>
</form>

<!-- <div class="alert alert-info">Prioriza los artículos en rojo (reposición urgente) y amarillo (stock bajo).</div> -->

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead class="table-light">
    <tr>
      <th>Nombre</th>
      <th>Clase</th>
      <th>Condición</th>
      <th>Stock</th>
      <th>Mín</th>
      <th>Máx</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it):
      $cls = 'table-success';
      if ($it['estado_calc'] === 'Reponer')      $cls = 'table-danger';
      elseif ($it['estado_calc'] === 'Bajo')     $cls = 'table-warning';
    ?>
    <tr class="<?=$cls?>">
      <td><strong><?=h($it['nombre'])?></strong> <small class="text-muted"><?=h($it['ubicacion_nombre']??'')?></small></td>
      <td><?=h($it['clase_nombre']??'')?></td>
      <td><?=h($it['condicion_nombre']??'')?></td>
      <td><?=intval($it['cantidad'])?></td>
      <td><?=intval($it['min_stock'])?></td>
      <td><?=intval($it['max_stock'])?></td>
      <td><?=h($it['estado_calc'])?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<script>
(function(){
  const form = document.getElementById('minmax-form');
  const sel  = form.querySelector('select[name="estado"]');
  sel.addEventListener('change', function(){ form.submit(); });
})();
</script>
