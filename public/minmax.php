<?php
require_once __DIR__.'/../config/db.php';
$items = $pdo->query("SELECT * FROM items ORDER BY (cantidad <= min_stock) DESC, (cantidad < max_stock) DESC, nombre")->fetchAll();
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
      <td><strong><?=htmlspecialchars($it['nombre'])?></strong> <small class="text-muted"><?=htmlspecialchars($it['ubicacion']??'')?></small></td>
      <td><?=htmlspecialchars($it['clase']??'')?></td>
      <td><?=htmlspecialchars($it['condicion']??'')?></td>
      <td><?=intval($it['cantidad'])?></td>
      <td><?=intval($it['min_stock'])?></td>
      <td><?=intval($it['max_stock'])?></td>
      <td><?=$estado?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>