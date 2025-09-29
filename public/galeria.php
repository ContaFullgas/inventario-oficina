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
        ORDER BY i.nombre";
$items = $pdo->query($sql)->fetchAll();
?>
<div class="row g-3">
<?php foreach ($items as $it): ?>
  <div class="col-12 col-sm-6 col-md-4 col-lg-3">
    <div class="card h-100 shadow-sm">
      <?php if (!empty($it['imagen'])): ?>
        <img src="../uploads/<?=h($it['imagen'])?>" class="card-img-top" style="height:200px;object-fit:cover;" alt="">
      <?php else: ?>
        <div class="card-img-top bg-secondary" style="height:200px;"></div>
      <?php endif; ?>
      <div class="card-body">
        <h6 class="card-title mb-1"><?=h($it['nombre'])?></h6>
        <p class="card-text"><small class="text-muted"><?=h($it['clase_nombre']??'')?> • <?=h($it['condicion_nombre']??'')?> • <?=h($it['ubicacion_nombre']??'')?></small></p>
        <p class="card-text"><small>Stock: <?=intval($it['cantidad'])?> | Min <?=intval($it['min_stock'])?> | Max <?=intval($it['max_stock'])?></small></p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
