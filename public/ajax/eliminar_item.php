<?php

header('Content-Type: application/json');

require_once dirname(__DIR__, 2).'/config/db.php';
require_once dirname(__DIR__, 2).'/config/util.php';
require_once dirname(__DIR__, 2).'/config/auth.php';

auth_check();
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'message'=>'Método no permitido']);
  exit;
}

check_csrf();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  echo json_encode(['ok'=>false,'message'=>'ID inválido']);
  exit;
}

// 1️⃣ Obtener imagen
$stmt = $pdo->prepare("SELECT imagen FROM items WHERE id = :id");
$stmt->execute([':id' => $id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  echo json_encode(['ok'=>false,'message'=>'El registro no existe']);
  exit;
}

// 2️⃣ Borrar imagen
if (!empty($item['imagen'])) {
  $path = __DIR__ . '/../uploads/' . $item['imagen'];
  if (is_file($path)) {
    @unlink($path);
  }
}

// 3️⃣ Borrar registro
$del = $pdo->prepare("DELETE FROM items WHERE id = :id");
$del->execute([':id' => $id]);

echo json_encode([
  'ok' => true,
  'message' => 'Elemento eliminado correctamente'
]);

exit;
