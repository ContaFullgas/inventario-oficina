<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
auth_check();
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php?tab=inv#inv', true, 302);
  exit;
}

check_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  flash_set('ok', 'ID inválido');
  header('Location: ../index.php?tab=inv#inv', true, 303);
  exit;
}

// 1) Obtener la imagen asociada
$stmt = $pdo->prepare("SELECT imagen FROM items WHERE id = :id");
$stmt->execute([':id' => $id]);
$row = $stmt->fetch();

if (!$row) {
  flash_set('ok', 'El registro no existe');
  header('Location: ../index.php?tab=inv#inv', true, 303);
  exit;
}

// 2) Intentar borrar el archivo físico (si hay)
if (!empty($row['imagen'])) {
  $path = __DIR__ . '/../uploads/' . $row['imagen'];
  if (is_file($path)) {
    @unlink($path); // si falla, seguimos de todos modos
  }
}

// 3) Borrar el registro
$del = $pdo->prepare("DELETE FROM items WHERE id = :id");
$del->execute([':id' => $id]);

flash_set('ok', 'Herramienta eliminada correctamente');
header('Location: ../index.php?tab=inv#inv', true, 303);
exit;
