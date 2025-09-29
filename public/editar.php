<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = :id");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch();

if (!$item) { http_response_code(404); echo "No encontrado"; exit; }

$clases = $pdo->query("SELECT nombre FROM cat_clases ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
$conds  = $pdo->query("SELECT nombre FROM cat_condiciones ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);
$ubis   = $pdo->query("SELECT nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll(PDO::FETCH_COLUMN);

$errors = []; $ok=false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $nombre    = trim($_POST['nombre'] ?? '');
  $clase     = trim($_POST['clase'] ?? '');
  $cantidad  = (int)($_POST['cantidad'] ?? 0);
  $condicion = trim($_POST['condicion'] ?? '');
  $notas     = trim($_POST['notas'] ?? '');
  $ubicacion = trim($_POST['ubicacion'] ?? '');
  $min_stock = (int)($_POST['min_stock'] ?? 0);
  $max_stock = (int)($_POST['max_stock'] ?? 0);

  if ($nombre === '') $errors[] = 'El nombre es obligatorio';
  if ($min_stock < 0 || $max_stock < 0 || $cantidad < 0) $errors[] = 'Cantidades/stock no pueden ser negativos';
  if ($max_stock < $min_stock) $errors[] = 'El máximo no puede ser menor que el mínimo';

  $imgName = $item['imagen'];
  if (!empty($_FILES['imagen']['name'])) {
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
      $errors[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP)';
    } else if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Error al subir imagen';
    } else {
      if (!empty($imgName)) {
        $old = __DIR__.'/../uploads/'.$imgName;
        if (is_file($old)) @unlink($old);
      }
      $imgName = uniqid('img_', true).'.'.$ext;
      $dest = __DIR__.'/../uploads/'.$imgName;
      if (!is_dir(__DIR__.'/../uploads')) @mkdir(__DIR__.'/../uploads', 0775, true);
      move_uploaded_file($_FILES['imagen']['tmp_name'], $dest);
    }
  }

  if (!$errors) {
    $sql = "UPDATE items SET
              nombre=:nombre, clase=:clase, cantidad=:cantidad, condicion=:condicion,
              notas=:notas, ubicacion=:ubicacion, min_stock=:min_stock, max_stock=:max_stock,
              imagen=:imagen
            WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nombre'=>$nombre, ':clase'=>$clase?:null, ':cantidad'=>$cantidad,
      ':condicion'=>$condicion?:null, ':notas'=>$notas?:null, ':ubicacion'=>$ubicacion?:null,
      ':min_stock'=>$min_stock, ':max_stock'=>$max_stock, ':imagen'=>$imgName, ':id'=>$id
    ]);
    flash_set('ok','Cambios guardados correctamente');
    $dest = "index.php?tab=inv#inv";
    if (!headers_sent()) { header("Location: $dest", true, 303); exit; }
    echo '<!doctype html><html><head><meta http-equiv="refresh" content="0;url='.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'"></head><body><script>location.replace("'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'");</script><a href="'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'">Continuar</a></body></html>'; exit;
  } else {
    $item['nombre']=$nombre; $item['clase']=$clase; $item['cantidad']=$cantidad;
    $item['condicion']=$condicion; $item['notas']=$notas; $item['ubicacion']=$ubicacion;
    $item['min_stock']=$min_stock; $item['max_stock']=$max_stock; $item['imagen']=$imgName;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar producto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
  <a href="index.php?tab=inv#inv" class="btn btn-link mb-3">← Volver a Inventario</a>
  <h3>Editar: <?=h($item['nombre'])?></h3>

  <?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="row g-3" action="editar.php?id=<?=intval($id)?>">
    <?=csrf_field()?>
    <div class="col-md-6">
      <label class="form-label">Nombre *</label>
      <input type="text" name="nombre" class="form-control" required value="<?=h($item['nombre'])?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Clase</label>
      <select name="clase" class="form-select">
        <option value="">(Sin clase)</option>
        <?php foreach($clases as $c): ?><option value="<?=h($c)?>" <?=$c===$item['clase']?'selected':''?>><?=h($c)?></option><?php endforeach; ?>
      </select>
      <small><a href="index.php?tab=cclase#cclase">Gestionar clases</a></small>
    </div>

    <div class="col-md-3">
      <label class="form-label">Stock total</label>
      <input type="number" name="cantidad" class="form-control" min="0" value="<?=intval($item['cantidad'])?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Condición/Estado</label>
      <select name="condicion" class="form-select">
        <option value="">(Sin condición)</option>
        <?php foreach($conds as $c): ?><option value="<?=h($c)?>" <?=$c===$item['condicion']?'selected':''?>><?=h($c)?></option><?php endforeach; ?>
      </select>
      <small><a href="index.php?tab=ccond#ccond">Gestionar condiciones</a></small>
    </div>
    <div class="col-md-3">
      <label class="form-label">Mínimo</label>
      <input type="number" name="min_stock" class="form-control" min="0" value="<?=intval($item['min_stock'])?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Máximo</label>
      <input type="number" name="max_stock" class="form-control" min="0" value="<?=intval($item['max_stock'])?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Ubicación</label>
      <select name="ubicacion" class="form-select">
        <option value="">(Sin ubicación)</option>
        <?php foreach($ubis as $u): ?><option value="<?=h($u)?>" <?=$u===$item['ubicacion']?'selected':''?>><?=h($u)?></option><?php endforeach; ?>
      </select>
      <small><a href="index.php?tab=cubi#cubi">Gestionar ubicaciones</a></small>
    </div>

    <div class="col-md-6">
      <label class="form-label">Imagen (opcional)</label>
      <input type="file" name="imagen" class="form-control" accept="image/*">
      <?php if (!empty($item['imagen'])): ?>
        <div class="mt-2">
          <img src="../uploads/<?=h($item['imagen'])?>" style="height:100px;object-fit:cover;" class="rounded border" alt="">
        </div>
      <?php endif; ?>
    </div>

    <div class="col-12">
      <label class="form-label">Notas</label>
      <textarea name="notas" class="form-control" rows="3"><?=h($item['notas'])?></textarea>
    </div>

    <div class="col-12">
      <button class="btn btn-primary">Guardar cambios</button>
      <a class="btn btn-secondary" href="index.php?tab=inv#inv">Cancelar</a>
    </div>
  </form>
</body>
</html>
