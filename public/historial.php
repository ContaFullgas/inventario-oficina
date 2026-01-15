<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

// Filtros opcionales
$q = isset($_GET['q_hist']) ? trim($_GET['q_hist']) : '';

// Consulta historial
$sql = "
SELECT 
  h.id,
  h.tipo_movimiento,
  h.observaciones,
  h.fecha,
  i.nombre AS item_nombre,
  u.usuario AS usuario_nombre
FROM historial_items h
JOIN items i ON i.id = h.item_id
JOIN usuarios u ON u.id = h.usuario_id
WHERE 1
";

$params = [];

if ($q !== '') {
  $sql .= " AND (
    i.nombre LIKE :q
    OR u.usuario LIKE :q
    OR h.observaciones LIKE :q
  )";
  $params[':q'] = "%$q%";
}

$sql .= " ORDER BY h.fecha DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<!-- HEADER -->
<!-- <div class="stats-header mb-4">
  <h2>
    <i class="fas fa-clock"></i>
    Historial de movimientos
  </h2>
  <p>Uso y devolución de herramientas</p>
</div> -->

<!-- BUSCADOR -->
<form method="get" class="row g-3 mb-4">
  <input type="hidden" name="tab" value="hist">

  <div class="col-md-6">
    <input
      type="text"
      name="q_hist"
      class="form-control"
      placeholder="Buscar por herramienta, usuario u observación"
      value="<?= h($q) ?>">
  </div>

  <div class="col-md-3">
    <button class="btn btn-primary w-100">
      <i class="fas fa-search"></i> Buscar
    </button>
  </div>

  <div class="col-md-3">
    <a href="index.php?tab=hist#hist" class="btn btn-secondary w-100">
      <i class="fas fa-eraser"></i> Limpiar
    </a>
  </div>
</form>

<!-- TABLA -->
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-dark">
      <tr>
        <th>Fecha</th>
        <th>Herramienta</th>
        <th>Usuario</th>
        <th>Movimiento</th>
        <th>Observaciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="5" class="text-center text-muted py-4">
            No hay movimientos registrados
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <?php
            $isUso = $r['tipo_movimiento'] === 'uso';
            $badge = $isUso ? 'bg-warning text-dark' : 'bg-success';
            $icon  = $isUso ? 'fa-hand' : 'fa-rotate-left';
            $label = $isUso ? 'Uso' : 'Devolución';
          ?>
          <tr>
            <td><?= h(date('d/m/Y H:i', strtotime($r['fecha']))) ?></td>
            <td><strong><?= h($r['item_nombre']) ?></strong></td>
            <td><?= h($r['usuario_nombre']) ?></td>
            <td>
              <span class="badge <?= $badge ?>">
                <i class="fas <?= $icon ?>"></i>
                <?= $label ?>
              </span>
            </td>
            <td><?= h($r['observaciones'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
