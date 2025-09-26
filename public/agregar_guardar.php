<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: index.php?tab=add#add', true, 302);
  exit;
}

check_csrf();

$nombre    = trim($_POST['nombre'] ?? '');
$clase     = trim($_POST['clase'] ?? '');
$cantidad  = (int)($_POST['cantidad'] ?? 0);
$condicion = trim($_POST['condicion'] ?? '');
$notas     = trim($_POST['notas'] ?? '');
$ubicacion = trim($_POST['ubicacion'] ?? '');
$min_stock = (int)($_POST['min_stock'] ?? 0);
$max_stock = (int)($_POST['max_stock'] ?? 0);

$errors = [];
if ($nombre === '') $errors[] = 'El nombre es obligatorio';
if ($min_stock < 0 || $max_stock < 0 || $cantidad < 0) $errors[] = 'Cantidades/stock no pueden ser negativos';
if ($max_stock < $min_stock) $errors[] = 'El máximo no puede ser menor que el mínimo';

$imgName = null;
if (!empty($_FILES['imagen']['name'])) {
  $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
    $errors[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP)';
  } else if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
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
  header('Location: index.php?tab=add#add', true, 303);
  exit;
}

$sql = "INSERT INTO items (nombre, clase, cantidad, condicion, notas, ubicacion, min_stock, max_stock, imagen)
        VALUES (:nombre,:clase,:cantidad,:condicion,:notas,:ubicacion,:min_stock,:max_stock,:imagen)";
$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':nombre'    => $nombre,
  ':clase'     => $clase ?: null,
  ':cantidad'  => $cantidad,
  ':condicion' => $condicion ?: null,
  ':notas'     => $notas ?: null,
  ':ubicacion' => $ubicacion ?: null,
  ':min_stock' => $min_stock,
  ':max_stock' => $max_stock,
  ':imagen'    => $imgName
]);

flash_set('ok', '¡Producto agregado correctamente!');
$dest = 'index.php?tab=inv#inv';

// Redirect 303 (PRG) + fallback
if (!headers_sent()) {
  header("Location: $dest", true, 303);
  exit;
} else {
  echo '<!doctype html><html><head>';
  echo '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($dest, ENT_QUOTES, 'UTF-8').'">';
  echo '</head><body>';
  echo '<script>location.replace("'.htmlspecialchars($dest, ENT_QUOTES, 'UTF-8').'");</script>';
  echo '<a href="'.htmlspecialchars($dest, ENT_QUOTES, 'UTF-8').'">Continuar</a>';
  echo '</body></html>';
  exit;
}
