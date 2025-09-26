<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase = isset($_GET['clase']) ? trim($_GET['clase']) : '';

$sql = "SELECT * FROM items WHERE 1";
$params = [];
if ($q !== '') {
  $sql .= " AND (nombre LIKE :q OR ubicacion LIKE :q OR condicion LIKE :q)";
  $params[':q'] = "%$q%";
}
if ($clase !== '') {
  $sql .= " AND clase = :c";
  $params[':c'] = $clase;
}
$sql .= " ORDER BY nombre";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$clases = $pdo->query("SELECT DISTINCT clase FROM items WHERE clase IS NOT NULL AND clase<>'' ORDER BY clase")->fetchAll(PDO::FETCH_COLUMN);
?>
<form class="row gy-2 gx-2 align-items-end mb-3" method="get">
  <div class="col-md-5">
    <label class="form-label">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, ubicación o condición" value="<?=h($q)?>">
  </div>
  <div class="col-md-5">
    <label class="form-label">Clase</label>
    <select name="clase" class="form-select">
      <option value="">(Todas)</option>
      <?php foreach ($clases as $c): ?>
        <option value="<?=h($c)?>" <?= $c===$clase? 'selected':'' ?>><?=h($c)?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <button class="btn btn-primary w-100">Filtrar</button>
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
      <td><?=h($it['clase'] ?? '')?></td>
      <td><?=h($it['ubicacion'] ?? '')?></td>
      <td><?=h($it['condicion'] ?? '')?></td>
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