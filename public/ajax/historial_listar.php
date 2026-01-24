<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

auth_check();

// Filtros
$q    = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? '';

// Query base
$sql = "
SELECT 
  h.fecha,
  h.tipo_movimiento,
  h.observaciones,
  i.nombre AS item,
  u.usuario
FROM historial_items h
JOIN items i ON i.id = h.item_id
JOIN usuarios u ON u.id = h.usuario_id
WHERE 1=1
";

$params = [];

// Filtro por texto
if ($q !== '') {
  $sql .= " AND i.nombre LIKE ? ";
  $params[] = "%$q%";
}

// Filtro por tipo
if (in_array($tipo, ['uso','devolucion'], true)) {
  $sql .= " AND h.tipo_movimiento = ? ";
  $params[] = $tipo;
}

$sql .= " ORDER BY h.fecha DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<table class="table table-hover table-sm align-middle">
  <thead class="table-light">
    <tr>
      <th>Fecha</th>
      <th>Herramienta</th>
      <th>Usuario</th>
      <th>Tipo</th>
      <th>Observaciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$rows): ?>
      <tr>
        <td colspan="5" class="text-center text-muted">
          Sin movimientos registrados
        </td>
      </tr>
    <?php endif; ?>

    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['fecha']) ?></td>
        <td><?= htmlspecialchars($r['item']) ?></td>
        <td><?= htmlspecialchars($r['usuario']) ?></td>
        <td>
          <span class="badge bg-<?= $r['tipo_movimiento']==='uso'?'warning':'success' ?>">
            <?= ucfirst($r['tipo_movimiento']) ?>
          </span>
        </td>
        <td><?= htmlspecialchars($r['observaciones'] ?? '-') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
