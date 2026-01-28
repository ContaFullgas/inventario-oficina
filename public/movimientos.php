<?php
// Archivo: movimientos.php

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

auth_check();
auth_require_admin();

// =======================
// Parámetros de búsqueda
// =======================
$q    = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? '';

// =======================
// Paginación
// =======================
$per_page = isset($_GET['per_page_mov']) && in_array($_GET['per_page_mov'], [10,25,50,100])
  ? (int)$_GET['per_page_mov']
  : 25;

$page   = max(1, (int)($_GET['page_mov'] ?? 1));
$offset = ($page - 1) * $per_page;

// =======================
// Construcción WHERE
// =======================
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

// =======================
// Conteo total
// =======================
$stmtCount = $pdo->prepare("
  SELECT COUNT(*)
  FROM inventario_movimientos m
  JOIN items i ON i.id = m.item_id
  $whereSql
");
$stmtCount->execute($params);
$total_rows   = (int)$stmtCount->fetchColumn();
$total_pages  = max(1, ceil($total_rows / $per_page));

// =======================
// Consulta principal
// =======================
$stmt = $pdo->prepare("
  SELECT
    m.id,
    m.tipo,
    m.cantidad,
    m.motivo,
    m.created_at,
    i.nombre AS item,
    u.usuario AS usuario
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
$stmt->bindValue(':limit',  $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);
$stmt->execute();

$movs = $stmt->fetchAll();

// =======================
// Helper URL
// =======================
function movUrl(array $extra = []) {
  $base = 'index.php?tab=mov';
  foreach ($extra as $k => $v) {
    if ($v !== '' && $v !== null) {
      $base .= '&'.urlencode($k).'='.urlencode($v);
    }
  }
  return $base.'#mov';
}
?>

<!-- ======================= -->
<!--  ENCABEZADO / FILTROS  -->
<!-- ======================= -->

<!-- <div class="stats-header mb-4">
  <h2>
    <i class="fas fa-exchange-alt"></i>
    Movimientos de Inventario
  </h2>
  <p>Historial completo de entradas y salidas (auditoría)</p>
</div> -->

<form class="row g-3 mb-4" method="get" action="index.php#mov">
  <input type="hidden" name="tab" value="mov">
  <input type="hidden" name="page_mov" value="1">
  <input type="hidden" name="per_page_mov" value="<?=$per_page?>">

  <div class="col-md-5">
    <div class="input-group">
      <span class="input-group-text">
        <i class="bi bi-search"></i>
      </span>
      <input
        type="text"
        name="q"
        class="form-control"
        placeholder="Producto o motivo"
        value="<?=h($q)?>">
    </div>
  </div>

  <div class="col-md-3">
    <select name="tipo" class="form-select">
      <option value="">Todos los tipos</option>
      <option value="ENTRADA" <?=$tipo==='ENTRADA'?'selected':''?>>Entrada</option>
      <option value="SALIDA"  <?=$tipo==='SALIDA'?'selected':''?>>Salida</option>
    </select>
  </div>

  <div class="col-md-2">
    <button class="btn btn-primary w-100">
      <i class="bi bi-funnel-fill"></i> Filtrar
    </button>
  </div>

  <div class="col-md-2">
    <a class="btn btn-success w-100" href="index.php?tab=mov#mov">
      <i class="bi bi-arrow-clockwise"></i> Limpiar
    </a>
  </div>
</form>

<!-- ======================= -->
<!--        TABLA           -->
<!-- ======================= -->

<div class="table-container">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th><i class="bi bi-clock-history"></i> Fecha</th>
          <th><i class="bi bi-box-seam"></i> Producto</th>
          <th><i class="bi bi-arrow-left-right"></i> Tipo</th>
          <th><i class="bi bi-hash"></i> Cantidad</th>
          <th><i class="bi bi-chat-left-text"></i> Motivo</th>
          <th><i class="bi bi-person"></i> Usuario</th>
        </tr>
      </thead>
      <tbody>

      <?php if (!$movs): ?>
        <tr>
          <td colspan="6" class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Sin movimientos registrados
          </td>
        </tr>
      <?php else: foreach ($movs as $m): ?>
        <tr>
          <td><?=date('d/m/Y H:i', strtotime($m['created_at']))?></td>
          <td><?=h($m['item'])?></td>
          <td>
            <span class="badge-custom <?=$m['tipo']==='ENTRADA'?'badge-ok':'badge-reponer'?>">
              <i class="bi <?=$m['tipo']==='ENTRADA'
                ? 'bi-plus-circle-fill'
                : 'bi-dash-circle-fill' ?>"></i>
              <?=$m['tipo']?>
            </span>
          </td>
          <td class="fw-bold"><?=$m['cantidad']?></td>
          <td><?=h($m['motivo']) ?: '—'?></td>
          <td><?=h($m['usuario'] ?? 'Sistema')?></td>
        </tr>
      <?php endforeach; endif; ?>

      </tbody>
    </table>
  </div>
</div>

<!-- ======================= -->
<!--      PAGINACIÓN        -->
<!-- ======================= -->

<?php if ($total_pages > 1): ?>
<div class="pagination-wrapper mt-4">
  <div class="pagination-info">
    <div class="results-count">
      <i class="bi bi-list-ul"></i>
      Mostrando
      <?=min($offset+1, $total_rows)?> -
      <?=min($offset+$per_page, $total_rows)?>
      de <?=$total_rows?>
    </div>

    <div class="per-page-selector">
      <label>Por página:</label>
      <select id="perPageMov">
        <?php foreach ([10,25,50,100] as $n): ?>
          <option value="<?=$n?>" <?=$per_page==$n?'selected':''?>><?=$n?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="pagination-controls">
    <a class="pagination-btn arrow <?=$page==1?'disabled':''?>"
       href="<?=movUrl(['q'=>$q,'tipo'=>$tipo,'page_mov'=>1,'per_page_mov'=>$per_page])?>">
      <i class="bi bi-chevron-double-left"></i>
    </a>

    <a class="pagination-btn arrow <?=$page==1?'disabled':''?>"
       href="<?=movUrl(['q'=>$q,'tipo'=>$tipo,'page_mov'=>$page-1,'per_page_mov'=>$per_page])?>">
      <i class="bi bi-chevron-left"></i>
    </a>

    <?php for ($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
      <a class="pagination-btn <?=$i==$page?'active':''?>"
         href="<?=movUrl(['q'=>$q,'tipo'=>$tipo,'page_mov'=>$i,'per_page_mov'=>$per_page])?>">
        <?=$i?>
      </a>
    <?php endfor; ?>

    <a class="pagination-btn arrow <?=$page==$total_pages?'disabled':''?>"
       href="<?=movUrl(['q'=>$q,'tipo'=>$tipo,'page_mov'=>$page+1,'per_page_mov'=>$per_page])?>">
      <i class="bi bi-chevron-right"></i>
    </a>

    <a class="pagination-btn arrow <?=$page==$total_pages?'disabled':''?>"
       href="<?=movUrl(['q'=>$q,'tipo'=>$tipo,'page_mov'=>$total_pages,'per_page_mov'=>$per_page])?>">
      <i class="bi bi-chevron-double-right"></i>
    </a>
  </div>
</div>
<?php endif; ?>

<script>
document.getElementById('perPageMov')?.addEventListener('change', function(){
  const url = new URL(location.href);
  url.searchParams.set('tab','mov');
  url.searchParams.set('per_page_mov', this.value);
  url.searchParams.set('page_mov', 1);
  location.href = url.toString() + '#mov';
});
</script>
