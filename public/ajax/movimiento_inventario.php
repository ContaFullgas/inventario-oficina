<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/util.php';

auth_check();
auth_require_admin();
check_csrf();

$item_id  = (int)($_POST['item_id'] ?? 0);
$tipo     = $_POST['tipo'] ?? '';
$cantidad = (int)($_POST['cantidad'] ?? 0);
$motivo   = trim($_POST['motivo'] ?? '');

if (!$item_id || !in_array($tipo, ['ENTRADA','SALIDA']) || $cantidad <= 0) {
  echo json_encode(['ok'=>false,'error'=>'Datos inválidos']);
  exit;
}

$pdo->beginTransaction();

$item = $pdo->prepare("SELECT cantidad FROM items WHERE id=:id FOR UPDATE");
$item->execute([':id'=>$item_id]);
$actual = $item->fetchColumn();

if ($actual === false) {
  $pdo->rollBack();
  echo json_encode(['ok'=>false,'error'=>'Producto no existe']);
  exit;
}

$nueva = $tipo === 'ENTRADA'
  ? $actual + $cantidad
  : $actual - $cantidad;

if ($nueva < 0) {
  $pdo->rollBack();
  echo json_encode(['ok'=>false,'error'=>'Stock insuficiente']);
  exit;
}

$pdo->prepare("
  INSERT INTO inventario_movimientos
  (item_id, tipo, cantidad, motivo, usuario_id)
  VALUES (:item, :tipo, :cantidad, :motivo, :usuario)
")->execute([
  ':item'=>$item_id,
  ':tipo'=>$tipo,
  ':cantidad'=>$cantidad,
  ':motivo'=>$motivo,
  ':usuario'=>$_SESSION['user_id'] ?? null
]);

$pdo->prepare("
  UPDATE items SET cantidad=:cantidad WHERE id=:id
")->execute([
  ':cantidad'=>$nueva,
  ':id'=>$item_id
]);

$pdo->commit();

echo json_encode(['ok'=>true,'stock'=>$nueva]);
