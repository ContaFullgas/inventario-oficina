<?php
require_once __DIR__.'/../config/db.php';

$sql = "SELECT i.*,
               c1.nombre AS clase_nombre,
               c2.nombre AS condicion_nombre,
               c3.nombre AS ubicacion_nombre
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        ORDER BY (i.cantidad <= i.min_stock) DESC,
                 (i.cantidad <  i.max_stock) DESC,
                 i.nombre";
$items = $pdo->query($sql)->fetchAll();
?>
<div class="alert alert-info">Prioriza los artículos en rojo (reposición urgente) y amarillo (stock bajo).</div>
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
      $estado='OK'; $cls='table-success';
      if ((int)$it['cantidad'] <= (int)$it['min_stock']) { $estado='Reponer'; $cls='table-danger'; }
      elseif ((int)$it['cantidad'] < (int)$it['max_stock']) { $estado='Bajo'; $cls='table-warning'; }
    ?>
    <tr class="<?=$cls?>">
      <td><strong><?=h($it['nombre'])?></strong> <small class="text-muted"><?=h($it['ubicacion_nombre']??'')?></small></td>
      <td><?=h($it['clase_nombre']??'')?></td>
      <td><?=h($it['condicion_nombre']??'')?></td>
      <td><?=intval($it['cantidad'])?></td>
      <td><?=intval($it['min_stock'])?></td>
      <td><?=intval($it['max_stock'])?></td>
      <td><?=$estado?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
