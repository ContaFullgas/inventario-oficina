<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php?tab=add#add', true, 302);
  exit;
}

check_csrf();

$nombre    = trim($_POST['nombre'] ?? '');
$cantidad  = (int)($_POST['cantidad'] ?? 0);
$notas     = trim($_POST['notas'] ?? '');
$min_stock = (int)($_POST['min_stock'] ?? 0);
$max_stock = (int)($_POST['max_stock'] ?? 0);

$clase_id     = ($_POST['clase_id'] !== '')     ? (int)$_POST['clase_id']     : null;
$condicion_id = ($_POST['condicion_id'] !== '') ? (int)$_POST['condicion_id'] : null;
$ubicacion_id = ($_POST['ubicacion_id'] !== '') ? (int)$_POST['ubicacion_id'] : null;

$errors = [];
if ($nombre === '') $errors[] = 'El nombre es obligatorio';
if ($min_stock < 0 || $max_stock < 0 || $cantidad < 0) $errors[] = 'Cantidades/stock no pueden ser negativos';
if ($max_stock < $min_stock) $errors[] = 'El máximo no puede ser menor que el mínimo';

$imgName = null;
if (!empty($_FILES['imagen']['name'])) {
  $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
    $errors[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP)';
  } elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Error al subir imagen';
  } else {
    $imgName = uniqid('img_', true).'.'.$ext;
    $dest = __DIR__.'/../uploads/'.$imgName;
    if (!is_dir(__DIR__.'/../uploads')) @mkdir(__DIR__.'/../uploads', 0775, true);
    move_uploaded_file($_FILES['imagen']['tmp_name'], $dest);
  }
}

if ($errors) {
  flash_set('ok', 'Errores: ' . implode(' | ', $errors));
  header('Location: ../index.php?tab=add#add', true, 303);
  exit;
}

$sql = "INSERT INTO items
          (nombre, clase_id, cantidad, condicion_id, notas, ubicacion_id, min_stock, max_stock, imagen)
        VALUES
          (:nombre,:clase_id,:cantidad,:condicion_id,:notas,:ubicacion_id,:min_stock,:max_stock,:imagen)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':nombre'        => $nombre,
  ':clase_id'      => $clase_id,
  ':cantidad'      => $cantidad,
  ':condicion_id'  => $condicion_id,
  ':notas'         => $notas ?: null,
  ':ubicacion_id'  => $ubicacion_id,
  ':min_stock'     => $min_stock,
  ':max_stock'     => $max_stock,
  ':imagen'        => $imgName
]);

flash_set('ok', '¡Producto agregado correctamente!');
$dest = '../index.php?tab=inv#inv';

if (!headers_sent()) {
  header("Location: $dest", true, 303);
  exit;
}
echo '<!doctype html><html><head><meta http-equiv="refresh" content="0;url='.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'"></head><body><script>location.replace("'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'");</script><a href="'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'">Continuar</a></body></html>';
exit;
