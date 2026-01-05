<?php

//Archivo galeria.php

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

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
  $sql .= " AND (i.nombre LIKE :q
             OR IFNULL(i.notas,'') LIKE :q
             OR IFNULL(c1.nombre,'') LIKE :q
             OR IFNULL(c2.nombre,'') LIKE :q
             OR IFNULL(c3.nombre,'') LIKE :q)";
  $params[':q'] = "%$q%";
}

$sql .= " ORDER BY i.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();
?>

<form id="galeria-form" class="row gy-2 gx-2 align-items-end mb-3" method="get" action="index.php#gal">
  <input type="hidden" name="tab" value="gal">
  <div class="col-md-10">
    <label class="form-label">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, clase, condición, ubicación o notas" value="<?=h($q)?>">
    <!-- <small class="text-muted">La búsqueda se aplica automáticamente.</small> -->
  </div>
  <div class="col-md-2 d-flex gap-2">
    <!-- <button class="btn btn-primary w-100" type="submit">Filtrar</button> -->
    <a class="btn btn-outline-secondary w-100" href="index.php?tab=gal#gal">Limpiar</a>
  </div>
</form>

<?php if (!$items): ?>
  <div class="alert alert-warning">No hay resultados para “<?=h($q)?>”.</div>
<?php endif; ?>

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
        <p class="card-text mb-1">
          <small class="text-muted">
            <?=h($it['clase_nombre']??'')?><?=($it['clase_nombre']&&$it['condicion_nombre'])?' • ':''?>
            <?=h($it['condicion_nombre']??'')?><?=($it['ubicacion_nombre'] && ($it['clase_nombre']||$it['condicion_nombre']))?' • ':''?>
            <?=h($it['ubicacion_nombre']??'')?>
          </small>
        </p>
        <?php if (!empty($it['notas'])): ?>
          <p class="card-text"><small><?=h($it['notas'])?></small></p>
        <?php endif; ?>
        <p class="card-text mt-auto"><small>Stock: <?=intval($it['cantidad'])?> | Min <?=intval($it['min_stock'])?> | Max <?=intval($it['max_stock'])?></small></p>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
(function(){
  const form = document.getElementById('galeria-form');
  const q    = form.querySelector('input[name="q"]');
  let t;
  q.addEventListener('input', function(){
    clearTimeout(t);
    t = setTimeout(function(){ form.submit(); }, 400);
  });
})();
</script>
