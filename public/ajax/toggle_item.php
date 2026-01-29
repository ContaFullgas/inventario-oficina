<?php
//toggle_items.php

//Para activar y desactivar items
header('Content-Type: application/json');

require_once dirname(__DIR__, 2).'/config/db.php';
require_once dirname(__DIR__, 2).'/config/util.php';
require_once dirname(__DIR__, 2).'/config/auth.php';

/* ========= SEGURIDAD AJAX ========= */

if (!auth_is_logged()) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Sesión expirada']);
  exit;
}

if (!auth_is_admin()) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'message'=>'Sin permisos']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'message'=>'Método no permitido']);
  exit;
}

check_csrf();

/* ========= DATOS ========= */

$id     = (int)($_POST['id'] ?? 0);
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : null;

if ($id <= 0 || !in_array($estado, [0,1], true)) {
  echo json_encode(['ok'=>false,'message'=>'Datos inválidos']);
  exit;
}

/* ========= VALIDAR ITEM ========= */

$stmt = $pdo->prepare("SELECT cantidad FROM items WHERE id = :id");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
  echo json_encode(['ok'=>false,'message'=>'Artículo no existe']);
  exit;
}

/* ========= REGLA DE NEGOCIO ========= */
/* SOLO permitir desactivar si stock = 0 */
if ($estado === 0 && (int)$item['cantidad'] > 0) {
  echo json_encode([
    'ok'=>false,
    'message'=>'No se puede desactivar un artículo con stock'
  ]);
  exit;
}

/* ========= ACTUALIZAR ========= */

$upd = $pdo->prepare("UPDATE items SET activo = :estado WHERE id = :id");
$upd->execute([
  ':estado' => $estado,
  ':id'     => $id
]);

echo json_encode([
  'ok' => true,
  'estado' => $estado
]);
exit;
