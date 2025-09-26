<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
check_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo "ID inválido";
  exit;
}
$stmt = $pdo->prepare("SELECT imagen FROM items WHERE id = :id");
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch();

$pdo->prepare("DELETE FROM items WHERE id=:id")->execute([':id'=>$id]);

if ($row && !empty($row['imagen'])) {
  $path = __DIR__.'/../uploads/'.$row['imagen'];
  if (is_file($path)) @unlink($path);
}

header("Location: index.php#inv");
exit;