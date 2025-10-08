<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
auth_check();
auth_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM items WHERE id=:id");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); echo "No encontrado"; exit; }

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
$conds  = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll();
$ubis   = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $nombre    = trim($_POST['nombre'] ?? '');
  $cantidad  = (int)($_POST['cantidad'] ?? 0);
  $notas     = trim($_POST['notas'] ?? '');
  $min_stock = (int)($_POST['min_stock'] ?? 0);
  $max_stock = (int)($_POST['max_stock'] ?? 0);

  $clase_id     = ($_POST['clase_id'] !== '')     ? (int)$_POST['clase_id']     : null;
  $condicion_id = ($_POST['condicion_id'] !== '') ? (int)$_POST['condicion_id'] : null;
  $ubicacion_id = ($_POST['ubicacion_id'] !== '') ? (int)$_POST['ubicacion_id'] : null;

  if ($nombre === '') $errors[] = 'El nombre es obligatorio';
  if ($min_stock < 0 || $max_stock < 0 || $cantidad < 0) $errors[] = 'Cantidades/stock no pueden ser negativos';
  if ($max_stock < $min_stock) $errors[] = 'El máximo no puede ser menor que el mínimo';

  $imgName = $item['imagen'];
  if (!empty($_FILES['imagen']['name'])) {
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
      $errors[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP)';
    } elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
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
              nombre=:nombre,
              clase_id=:clase_id,
              cantidad=:cantidad,
              condicion_id=:condicion_id,
              notas=:notas,
              ubicacion_id=:ubicacion_id,
              min_stock=:min_stock,
              max_stock=:max_stock,
              imagen=:imagen
            WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nombre'       => $nombre,
      ':clase_id'     => $clase_id,
      ':cantidad'     => $cantidad,
      ':condicion_id' => $condicion_id,
      ':notas'        => $notas ?: null,
      ':ubicacion_id' => $ubicacion_id,
      ':min_stock'    => $min_stock,
      ':max_stock'    => $max_stock,
      ':imagen'       => $imgName,
      ':id'           => $id
    ]);

    flash_set('ok','Cambios guardados correctamente');
    $dest = "../index.php?tab=inv#inv";
    if (!headers_sent()) { header("Location: $dest", true, 303); exit; }
    echo '<!doctype html><html><head><meta http-equiv="refresh" content="0;url='.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'"></head><body><script>location.replace("'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'");</script><a href="'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'">Continuar</a></body></html>';
    exit;
  } else {
    // reinyecta al form
    $item['nombre']=$nombre; $item['cantidad']=$cantidad; $item['notas']=$notas;
    $item['min_stock']=$min_stock; $item['max_stock']=$max_stock; $item['imagen']=$imgName;
    $item['clase_id']=$clase_id; $item['condicion_id']=$condicion_id; $item['ubicacion_id']=$ubicacion_id;
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
  <a href="../index.php?tab=inv#inv" class="btn btn-link mb-3">← Volver a Inventario</a>
  <h3>Editar: <?=h($item['nombre'])?></h3>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="row g-3" action="editar.php?id=<?=intval($id)?>">
    <?=csrf_field()?>

    <div class="col-md-6">
      <label class="form-label">Nombre *</label>
      <input type="text" name="nombre" class="form-control" required value="<?=h($item['nombre'])?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Clase</label>
      <select name="clase_id" class="form-select">
        <option value="">(Sin clase)</option>
        <?php foreach($clases as $c): ?>
          <option value="<?=$c['id']?>" <?=$c['id']==($item['clase_id']??null)?'selected':''?>><?=h($c['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <small><a href="../index.php?tab=cclase#cclase">Gestionar clases</a></small>
    </div>

    <div class="col-md-3">
      <label class="form-label">Stock total</label>
      <input type="number" name="cantidad" class="form-control" min="0" value="<?=intval($item['cantidad'])?>">
    </div>

    <div class="col-md-3">
      <label class="form-label">Condición/Estado</label>
      <select name="condicion_id" class="form-select">
        <option value="">(Sin condición)</option>
        <?php foreach($conds as $c): ?>
          <option value="<?=$c['id']?>" <?=$c['id']==($item['condicion_id']??null)?'selected':''?>><?=h($c['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <small><a href="../index.php?tab=ccond#ccond">Gestionar condiciones</a></small>
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
      <select name="ubicacion_id" class="form-select">
        <option value="">(Sin ubicación)</option>
        <?php foreach($ubis as $u): ?>
          <option value="<?=$u['id']?>" <?=$u['id']==($item['ubicacion_id']??null)?'selected':''?>><?=h($u['nombre'])?></option>
        <?php endforeach; ?>
      </select>
      <small><a href="../index.php?tab=cubi#cubi">Gestionar ubicaciones</a></small>
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
      <a class="btn btn-secondary" href="../index.php?tab=inv#inv">Cancelar</a>
    </div>
  </form>
</body>
</html>
