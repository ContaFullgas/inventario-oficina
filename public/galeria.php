<?php
require_once __DIR__.'/../config/db.php';
$items = $pdo->query("SELECT * FROM items ORDER BY nombre")->fetchAll();
?>
<div class="row g-3">
<?php foreach ($items as $it): ?>
  <div class="col-12 col-sm-6 col-md-4 col-lg-3">
    <div class="card h-100 shadow-sm">
      <?php if (!empty($it['imagen'])): ?>
        <img src="../uploads/<?=htmlspecialchars($it['imagen'])?>" class="card-img-top" style="height:200px;object-fit:cover;" alt="">
      <?php else: ?>
        <div class="card-img-top bg-secondary" style="height:200px;"></div>
      <?php endif; ?>
      <div class="card-body">
        <h6 class="card-title mb-1"><?=htmlspecialchars($it['nombre'])?></h6>
        <p class="card-text"><small class="text-muted"><?=htmlspecialchars($it['clase']??'')?> • <?=htmlspecialchars($it['condicion']??'')?> • <?=htmlspecialchars($it['ubicacion']??'')?></small></p>
        <p class="card-text"><small>Stock: <?=intval($it['cantidad'])?> | Min <?=intval($it['min_stock'])?> | Max <?=intval($it['max_stock'])?></small></p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>