<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

// Lee filtro de estado (reponer|bajo|ok)
$estado = isset($_GET['estado']) ? strtolower(trim($_GET['estado'])) : '';
$valid = ['reponer','bajo','ok'];
if (!in_array($estado, $valid, true)) { $estado = ''; }

// Paginación
$items_por_pagina = isset($_GET['per_page_mm']) && in_array($_GET['per_page_mm'], [10, 25, 50, 100]) ? (int)$_GET['per_page_mm'] : 25;
$pagina_actual = isset($_GET['page_mm']) ? max(1, (int)$_GET['page_mm']) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Contar total de registros con el filtro aplicado
$sqlCount = "SELECT COUNT(*) as total FROM (
  SELECT
    i.id,
    CASE
      WHEN i.cantidad <= i.min_stock THEN 'Reponer'
      WHEN i.cantidad <  i.max_stock THEN 'Bajo'
      ELSE 'OK'
    END AS estado_calc
  FROM items i
  WHERE 1
) AS subquery WHERE 1";

if ($estado === 'reponer') {
  $sqlCount .= " AND estado_calc = 'Reponer'";
} elseif ($estado === 'bajo') {
  $sqlCount .= " AND estado_calc = 'Bajo'";
} elseif ($estado === 'ok') {
  $sqlCount .= " AND estado_calc = 'OK'";
}

$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute([]);
$total_items = $stmtCount->fetch()['total'];
$total_paginas = ceil($total_items / $items_por_pagina);

// Consulta principal con paginación
$sql = "SELECT
          i.*,
          c1.nombre AS clase_nombre,
          c2.nombre AS condicion_nombre,
          c3.nombre AS ubicacion_nombre,
          CASE
            WHEN i.cantidad <= i.min_stock THEN 'Reponer'
            WHEN i.cantidad <  i.max_stock THEN 'Bajo'
            ELSE 'OK'
          END AS estado_calc
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE 1";

if ($estado !== '') {
  if ($estado === 'reponer') {
    $sql .= " HAVING estado_calc = 'Reponer'";
  } elseif ($estado === 'bajo') {
    $sql .= " HAVING estado_calc = 'Bajo'";
  } elseif ($estado === 'ok') {
    $sql .= " HAVING estado_calc = 'OK'";
  }
}

if ($estado === '') {
  $sql .= " ORDER BY
              (i.cantidad <= i.min_stock) DESC,
              (i.cantidad <  i.max_stock) DESC,
              i.nombre";
} else {
  $sql .= " ORDER BY i.nombre";
}

$sql .= " LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $items_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

// Función para construir URL con parámetros
function buildUrlMM($params) {
  $base = 'index.php?tab=mm';
  foreach ($params as $key => $val) {
    if ($val !== '' && $val !== null) {
      $base .= '&' . urlencode($key) . '=' . urlencode($val);
    }
  }
  return $base . '#mm';
}
?>

<style>
.minmax-container {
  background: #f8f9fa;
  padding: 2rem;
  border-radius: 12px;
}

.filter-bar {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  margin-bottom: 2rem;
}

.filter-select-wrapper {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.filter-select {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.filter-select:focus {
  border-color: #f39c12;
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(243,156,18,0.15);
}

.filter-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #ffc107;
  font-size: 1.2rem;
}

.btn-clean {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s;
  border: none;
}

.btn-clean:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.items-table-wrapper {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.items-table {
  margin: 0;
}

.items-table thead {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  color: white;
  border-bottom: 2px solid #dee2e6;
}

.items-table thead th {
  padding: 1rem;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  border: none;
}

.items-table tbody td {
  padding: 1.25rem 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f0f0f0;
}

.items-table tbody tr:last-child td {
  border-bottom: none;
}

.item-image {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  border: 3px solid #ffc107;
  object-fit: cover;
  background: #f8f9fa;
}

.item-name {
  font-weight: 600;
  font-size: 1rem;
  color: #212529;
  margin-bottom: 0.25rem;
}

.item-location {
  color: #6c757d;
  font-size: 0.85rem;
}

.badge-estado {
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.badge-reponer {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(231,76,60,0.3);
}

.badge-bajo {
  background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(243,156,18,0.3);
}

.badge-ok {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  color: white;
  box-shadow: 0 2px 6px rgba(39,174,96,0.3);
}

.stock-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #212529;
}

.tag-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #495057;
}

.tag-badge i {
  color: #f39c12;
}

.condition-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 0.85rem;
  color: #495057;
}

.condition-badge i {
  color: #f39c12;
}

.row-reponer {
  background: #ffebee !important;
}

.row-bajo {
  background: #fff9e6 !important;
}

.row-ok {
  background: #f1f8f4 !important;
}

.items-table tbody tr {
  transition: all 0.3s ease;
}

.items-table tbody tr:hover {
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* ESTILOS DE PAGINACIÓN */
.pagination-wrapper {
  background: white;
  padding: 1.5rem;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  margin-top: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #2c3e50;
  font-weight: 500;
}

.pagination-info .results-count {
  background: linear-gradient(135deg, #fef9e7 0%, #fcf3cf 100%);
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-weight: 600;
  color: #f39c12;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-selector label {
  margin: 0;
  font-size: 0.9rem;
  color: #7f8c8d;
}

.per-page-selector select {
  border: 2px solid #f8f9fa;
  border-radius: 8px;
  padding: 0.4rem 0.8rem;
  font-weight: 600;
  color: #2c3e50;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
}

.per-page-selector select:focus {
  border-color: #f39c12;
  outline: none;
  box-shadow: 0 0 0 0.2rem rgba(243,156,18,0.15);
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.pagination-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  border: 2px solid #f8f9fa;
  background: white;
  color: #2c3e50;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
}

.pagination-btn:hover:not(.disabled) {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  color: white;
  border-color: #f39c12;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(243,156,18,0.3);
}

.pagination-btn.active {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  color: white;
  border-color: #f39c12;
  box-shadow: 0 4px 12px rgba(243,156,18,0.3);
}

.pagination-btn.disabled {
  opacity: 0.4;
  cursor: not-allowed;
  pointer-events: none;
}

.pagination-btn.arrow {
  width: auto;
  padding: 0 1rem;
  font-size: 1.2rem;
}

.pagination-ellipsis {
  color: #7f8c8d;
  padding: 0 0.5rem;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .minmax-container {
    padding: 1rem;
  }
  
  .filter-bar {
    padding: 1rem;
  }
  
  .items-table thead th,
  .items-table tbody td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }
  
  .stock-number {
    font-size: 1.2rem;
  }

  .pagination-wrapper {
    flex-direction: column;
    text-align: center;
  }

  .pagination-info {
    flex-direction: column;
  }

  .pagination-controls {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>

<div class="minmax-container">
  <!-- Barra de Filtros -->
  <form id="minmax-form" method="get" action="index.php#mm">
    <input type="hidden" name="tab" value="mm">
    <input type="hidden" name="page_mm" value="1">
    <input type="hidden" name="per_page_mm" value="<?=$items_por_pagina?>">
    
    <div class="filter-bar">
      <div class="d-flex gap-3 align-items-center flex-wrap">
        <div class="filter-select-wrapper">
          <i class="bi bi-funnel-fill filter-icon"></i>
          <select name="estado" class="filter-select">
            <option value="" <?= $estado===''?'selected':'' ?>>Todos los Estados</option>
            <option value="reponer" <?= $estado==='reponer'?'selected':'' ?>>🔴 Reponer</option>
            <option value="bajo" <?= $estado==='bajo'?'selected':'' ?>>🟡 Bajo</option>
            <option value="ok" <?= $estado==='ok'?'selected':'' ?>>🟢 OK</option>
          </select>
        </div>
        <a class="btn btn-success btn-clean" href="index.php?tab=mm#mm">
          <i class="bi bi-arrow-clockwise"></i> Limpiar
        </a>
        <!-- <button class="btn btn-danger"><i class="bi bi-file-earmark-arrow-down-fill"></i> Generar Requerimiento</button> -->
      </div>
    </div>
  </form>

  <!-- Tabla de Items -->
  <div class="items-table-wrapper">
    <table class="items-table table table-hover">
      <thead>
        <tr>
          <th><i class="bi bi-box"></i> PRODUCTO</th>
          <th><i class="bi bi-tag"></i> CLASE</th>
          <th><i class="bi bi-geo-alt"></i> UBICACIÓN</th>
          <th><i class="bi bi-shield-check"></i> CONDICIÓN</th>
          <th><i class="bi bi-stack"></i> STOCK</th>
          <th><i class="bi bi-graph-up"></i> ESTADO</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($items)): ?>
        <tr>
          <td colspan="6" class="text-center py-5">
            <div style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;">
              <i class="bi bi-inbox"></i>
            </div>
            <p class="text-muted mb-0">No se encontraron resultados</p>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($items as $it):
          $rowClass = '';
          if ($it['estado_calc'] === 'Reponer') $rowClass = 'row-reponer';
          elseif ($it['estado_calc'] === 'Bajo') $rowClass = 'row-bajo';
          else $rowClass = 'row-ok';
          
          $badgeClass = '';
          if ($it['estado_calc'] === 'Reponer') $badgeClass = 'badge-reponer';
          elseif ($it['estado_calc'] === 'Bajo') $badgeClass = 'badge-bajo';
          else $badgeClass = 'badge-ok';
        ?>
        <tr class="<?=$rowClass?>">
          <td>
            <div class="item-name"><?=h($it['nombre'])?></div>
            <?php if (!empty($it['notas'])): ?>
              <small class="text-muted"><i class="bi bi-chat-left-text"></i> <?=h($it['notas'])?></small>
            <?php endif; ?>
          </td>
          <td>
            <span class="tag-badge">
              <i class="bi bi-tag-fill"></i>
              <?=h($it['clase_nombre']??'Sin clase')?>
            </span>
          </td>
          <td>
            <span class="tag-badge">
              <i class="bi bi-pin-map-fill"></i>
              <?=h($it['ubicacion_nombre']??'Sin ubicación')?>
            </span>
          </td>
          <td>
            <span class="condition-badge">
              <i class="bi bi-shield-fill-check"></i>
              <?=h($it['condicion_nombre']??'N/A')?>
            </span>
          </td>
          <td>
            <div class="stock-number"><?=intval($it['cantidad'])?></div>
            <small class="text-muted">Mín: <?=intval($it['min_stock'])?> | Máx: <?=intval($it['max_stock'])?></small>
          </td>
          <td>
            <span class="badge-estado <?=$badgeClass?>">
              <?php if ($it['estado_calc'] === 'Reponer'): ?>
                <i class="bi bi-exclamation-circle-fill"></i>
              <?php elseif ($it['estado_calc'] === 'Bajo'): ?>
                <i class="bi bi-exclamation-triangle-fill"></i>
              <?php else: ?>
                <i class="bi bi-check-circle-fill"></i>
              <?php endif; ?>
              <?=h($it['estado_calc'])?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_paginas > 1): ?>
  <!-- Paginación -->
  <div class="pagination-wrapper">
    <div class="pagination-info">
      <div class="results-count">
        <i class="bi bi-list-ul"></i>
        <span>Mostrando <?=min($offset + 1, $total_items)?> - <?=min($offset + $items_por_pagina, $total_items)?> de <?=$total_items?> resultados</span>
      </div>
      <div class="per-page-selector">
        <label>Por página:</label>
        <select id="perPageSelect">
          <option value="10" <?=$items_por_pagina==10?'selected':''?>>10</option>
          <option value="25" <?=$items_por_pagina==25?'selected':''?>>25</option>
          <option value="50" <?=$items_por_pagina==50?'selected':''?>>50</option>
          <option value="100" <?=$items_por_pagina==100?'selected':''?>>100</option>
        </select>
      </div>
    </div>

    <div class="pagination-controls">
      <!-- Botón Primera Página -->
      <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>1, 'per_page_mm'=>$items_por_pagina])?>" 
         class="pagination-btn arrow <?=$pagina_actual==1?'disabled':''?>">
        <i class="bi bi-chevron-double-left"></i>
      </a>

      <!-- Botón Anterior -->
      <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>max(1,$pagina_actual-1), 'per_page_mm'=>$items_por_pagina])?>" 
         class="pagination-btn arrow <?=$pagina_actual==1?'disabled':''?>">
        <i class="bi bi-chevron-left"></i>
      </a>

      <?php
      // Lógica para mostrar páginas
      $rango = 2; // Cuántas páginas mostrar a cada lado de la actual
      $inicio = max(1, $pagina_actual - $rango);
      $fin = min($total_paginas, $pagina_actual + $rango);

      // Mostrar primera página si no está en el rango
      if ($inicio > 1) {
        ?>
        <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>1, 'per_page_mm'=>$items_por_pagina])?>" 
           class="pagination-btn">1</a>
        <?php if ($inicio > 2): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
      <?php }

      // Páginas en el rango
      for ($i = $inicio; $i <= $fin; $i++): ?>
        <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>$i, 'per_page_mm'=>$items_por_pagina])?>" 
           class="pagination-btn <?=$i==$pagina_actual?'active':''?>"><?=$i?></a>
      <?php endfor;

      // Mostrar última página si no está en el rango
      if ($fin < $total_paginas) {
        if ($fin < $total_paginas - 1): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
        <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>$total_paginas, 'per_page_mm'=>$items_por_pagina])?>" 
           class="pagination-btn"><?=$total_paginas?></a>
      <?php } ?>

      <!-- Botón Siguiente -->
      <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>min($total_paginas,$pagina_actual+1), 'per_page_mm'=>$items_por_pagina])?>" 
         class="pagination-btn arrow <?=$pagina_actual==$total_paginas?'disabled':''?>">
        <i class="bi bi-chevron-right"></i>
      </a>

      <!-- Botón Última Página -->
      <a href="<?=buildUrlMM(['estado'=>$estado, 'page_mm'=>$total_paginas, 'per_page_mm'=>$items_por_pagina])?>" 
         class="pagination-btn arrow <?=$pagina_actual==$total_paginas?'disabled':''?>">
        <i class="bi bi-chevron-double-right"></i>
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
(function(){
  // 👇 Scope: solo dentro de la pestaña Min/Máx
  const root = document.getElementById('mm');
  if (!root) return;

  const form = root.querySelector('#minmax-form');
  if (!form) return;

  const sel            = form.querySelector('select[name="estado"]');
  const perPageInput = form.querySelector('input[name="per_page_mm"]');
  const perPageSelect  = root.querySelector('#perPageSelect'); // 👈 ya no usamos document.getElementById

  // Auto-submit al cambiar filtro de estado
  if (sel) {
    sel.addEventListener('change', function(){ form.submit(); });
  }

  // Cambio de "Por página"
  if (perPageSelect) {
    perPageSelect.addEventListener('change', function(){
      if (perPageInput) perPageInput.value = this.value;
      form.submit();
    });
  }
})();
</script>
