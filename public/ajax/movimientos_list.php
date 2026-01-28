<?php
// public/ajax/movimientos_list.php

require_once __DIR__.'/../../config/util.php';
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/auth.php';

header('Content-Type: application/json');

// ==========================
// Seguridad AJAX (SIN redirect)
// ==========================
if (!auth_is_logged()) {
  echo json_encode([
    'ok' => false,
    'data' => [],
    'total' => 0
  ]);
  exit;
}

if (!auth_is_admin()) {
  echo json_encode([
    'ok' => false,
    'data' => [],
    'total' => 0
  ]);
  exit;
}

// ==========================
// Parámetros
// ==========================
$q     = trim($_GET['q'] ?? '');
$tipo  = $_GET['tipo'] ?? '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = in_array((int)($_GET['limit'] ?? 25), [10,25,50,100], true)
  ? (int)$_GET['limit']
  : 25;

$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

if ($q !== '') {
  $where[] = '(i.nombre LIKE :q OR IFNULL(m.motivo,"") LIKE :q)';
  $params[':q'] = "%$q%";
}

if (in_array($tipo, ['ENTRADA','SALIDA'], true)) {
  $where[] = 'm.tipo = :tipo';
  $params[':tipo'] = $tipo;
}

$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// ==========================
// Total de registros
// ==========================
$stmt = $pdo->prepare("
  SELECT COUNT(*)
  FROM inventario_movimientos m
  JOIN items i ON i.id = m.item_id
  $whereSql
");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// ==========================
// Datos
// ==========================
$stmt = $pdo->prepare("
  SELECT
    m.tipo,
    m.cantidad,
    m.motivo,
    m.created_at,
    i.nombre AS item,
    u.usuario
  FROM inventario_movimientos m
  JOIN items i ON i.id = m.item_id
  LEFT JOIN usuarios u ON u.id = m.usuario_id
  $whereSql
  ORDER BY m.created_at DESC
  LIMIT :limit OFFSET :offset
");

foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

echo json_encode([
  'ok'    => true,
  'data'  => $stmt->fetchAll(),
  'total' => $total
]);
