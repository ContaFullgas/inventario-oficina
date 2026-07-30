<?php
// Archivo inventario.php


require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';


// Permisos (index ya cargó auth.php; usamos helper)
$is_admin = function_exists('auth_is_admin') ? auth_is_admin() : false;


$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase_id = (isset($_GET['clase_id']) && $_GET['clase_id'] !== '') ? (int)$_GET['clase_id'] : null;
$solo_inactivos = isset($_GET['inactivos']) && $_GET['inactivos'] === '1';




// Paginación
$items_por_pagina = isset($_GET['per_page_inv']) && in_array($_GET['per_page_inv'], [10, 25, 50, 100]) ? (int)$_GET['per_page_inv'] : 25;
$pagina_actual = isset($_GET['page_inv']) ? max(1, (int)$_GET['page_inv']) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;


// Contar total de registros
$sqlCount = "SELECT COUNT(*) as total
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE 1";
$paramsCount = [];


if ($q !== '') {
  $sqlCount .= " AND (i.nombre LIKE :q
              OR c1.nombre LIKE :q
              OR c2.nombre LIKE :q
              OR c3.nombre LIKE :q
              OR IFNULL(i.notas,'') LIKE :q)";
  $paramsCount[':q'] = "%$q%";
}
if (!is_null($clase_id)) {
  $sqlCount .= " AND i.clase_id = :cid";
  $paramsCount[':cid'] = $clase_id;
}


if ($solo_inactivos) {
  $sqlCount .= " AND i.activo = 0";
}




$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($paramsCount);
$total_items = $stmtCount->fetch()['total'];
$total_paginas = ceil($total_items / $items_por_pagina);


// Consulta principal con límite
$sql = "SELECT i.*,
               c1.nombre AS clase_nombre,
               c2.nombre AS condicion_nombre,
               c3.nombre AS ubicacion_nombre
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE 1";
$params = [];


if ($q !== '') {
  $sql .= " AND (i.nombre LIKE :q
              OR c1.nombre LIKE :q
              OR c2.nombre LIKE :q
              OR c3.nombre LIKE :q
              OR IFNULL(i.notas,'') LIKE :q)";
  $params[':q'] = "%$q%";
}
if (!is_null($clase_id)) {
  $sql .= " AND i.clase_id = :cid";
  $params[':cid'] = $clase_id;
}


if ($solo_inactivos) {
  $sql .= " AND i.activo = 0";
}


$sql .= " ORDER BY c3.nombre, i.nombre LIMIT :limit OFFSET :offset";


$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
  $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $items_por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();


$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();


// Función para construir URL con parámetros
function buildUrl($params) {
  $base = 'index.php?tab=inv';
  foreach ($params as $key => $val) {
    if ($val !== '' && $val !== null) {
      $base .= '&' . urlencode($key) . '=' . urlencode($val);
    }
  }
  return $base . '#inv';
}
?>


<style>
/* ==========================================================================
   1. CONTENEDORES Y BÚSQUEDA
   ========================================================================== */
.inventario-wrapper {
    padding: 0;
}


#inventario-form {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
}


#inventario-form .input-group-text {
    background: #175027;
    border: none;
    color: white;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(243, 156, 18, 0.3);
}


#inventario-form .form-control,
#inventario-form .form-select {
    border: 2px solid #f8f9fa;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}


#inventario-form .form-control:focus,
#inventario-form .form-select:focus {
    border-color: #f39c12;
    box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.15);
}


.capsule-focus:focus-within {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 148, 136, 0.25) !important;
    background-color: #ffffff !important;
}


.capsule-focus:focus-within i {
    color: var(--primary) !important;
}


/* ==========================================================================
   2. BOTONES GENERALES
   ========================================================================== */
#inventario-form .btn {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}


#inventario-form .btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}


#inventario-form .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(39, 174, 96, 0.4);
}


.btn-action-group {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.5rem;
    max-width: 90px;
}


.btn-action {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid;
    transition: all 0.3s ease;
    font-size: 1rem;
}


.btn-action-edit {
    border-color: #3498db;
    color: #3498db;
    background: white;
}


.btn-action-edit:hover {
    background: #3498db;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}


.btn-action-delete {
    border-color: #e74c3c;
    color: #e74c3c;
    background: white;
}


.btn-action-delete:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}


.btn-action-view {
    border-color: #f39c12;
    color: #f39c12;
    background: white;
}


.btn-action-view:hover {
    background: #f39c12;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}


/* Botón de Salida: siempre en rojo, independiente del tema */
.btn-action[data-tipo="SALIDA"] {
    border-color: #e74c3c;
    color: #e74c3c;
}


.btn-action[data-tipo="SALIDA"]:hover {
    background: #e74c3c;
    color: white;
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}


/* ==========================================================================
   3. TABLA E ITEMS
   ========================================================================== */
.table-container {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}


#tabla-inventario {
    margin-bottom: 0;
}


/* MODIFICACIÓN: Encabezado de tabla verde sólido siempre */
#tabla-inventario thead,
#tabla-inventario thead th {
    background-color: #27ae60 !important; /* Color verde sólido */
    color: white !important;
}


#tabla-inventario thead th {
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border: none;
    white-space: nowrap;
}


#tabla-inventario tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f8f9fa;
}


@media (hover: hover) and (pointer: fine) {
    #tabla-inventario.table-hover > tbody > tr:hover > * {
        background-color: #d4f4dd !important;
        color: #1e293b !important;
    }
}


#tabla-inventario tbody td {
    padding: 1rem;
    vertical-align: middle;
    border: none;
}


.item-nombre {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    transition: color 0.3s ease;
}


.item-nombre:hover {
    color: #f39c12;
}


.item-notas {
    font-size: 0.85rem;
    color: #7f8c8d;
    font-style: italic;
}


.stock-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 50px;
}


/* Imágenes en tabla */
.img-thumbnail {
    border: 3px solid #f4d03f !important;
    border-radius: 12px !important;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}


.img-thumbnail:hover {
    transform: scale(1.1) rotate(2deg);
    box-shadow: 0 6px 16px rgba(244, 208, 63, 0.4);
}


.img-placeholder {
    background: linear-gradient(135deg, #e8e8e8 0%, #d5d5d5 100%);
    width: 64px;
    height: 64px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 1.5rem;
}


/* ==========================================================================
   4. ETIQUETAS (BADGES)
   ========================================================================== */
.badge-custom {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}


.badge-ok {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    color: white;
    width: 100px;
    text-align: center;
}


.badge-bajo {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
    width: 100px;
}


.badge-reponer {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    width: 100px;
}


.category-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #495057;
    font-weight: 500;
}


.category-tag i {
    color: #2abb4f;
}


/* ==========================================================================
   5. PAGINACIÓN
   ========================================================================== */
.pagination-wrapper {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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


.results-count {
    background: linear-gradient(135deg, #fef9e7 0%, #fcf3cf 100%);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    color: #2abb4f;
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
    border-color: #2abb4f;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.15);
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
    background: linear-gradient(135deg, #2abb4f 0%, #2abb4f 100%);
    color: white;
    border-color: #2abb4f;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}


.pagination-btn.active {
    background: linear-gradient(135deg, #2abb4f 0%, #2abb4f 100%);
    color: white;
    border-color: #2abb4f;
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
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


/* ==========================================================================
   6. MODALES Y EXTRAS
   ========================================================================== */
.modal-input-group {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: all 0.2s ease;
}


.modal-input-group:focus-within {
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 148, 136, 0.15) !important;
    background-color: #ffffff !important;
}


.modal-input-group:focus-within i {
    color: var(--primary) !important;
}


/* Modal Imagen Zoom */
.img-modal-close {
    z-index: 2;
    width: 40px;
    height: 40px;
    line-height: 1;
    text-align: center;
    font-weight: bold;
    font-size: 20px;
    padding: 0;
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}


.img-modal-close:hover {
    transform: scale(1.1);
}


.zoom-container {
    position: relative;
    overflow: hidden;
    background: #000;
    cursor: default;
    user-select: none;
    -webkit-user-select: none;
    height: 80vh;
    display: block;
}


.zoom-container.can-pan {
    cursor: grab;
}


.zoom-container.can-pan.grabbing {
    cursor: grabbing;
}


#imgCanvas {
    display: block;
    width: 100%;
    height: 100%;
    image-rendering: auto;
}


#imgModal .modal-content {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.4);
}


/* Modal Eliminación */
#deleteModal {
    z-index: 99999 !important;
    display: none;
}


#deleteModal.show {
    display: block !important;
}


#deleteModal .modal-dialog {
    z-index: 100000 !important;
    pointer-events: auto !important;
}


#deleteModal .modal-content {
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5) !important;
    border: none;
    background: white;
    position: relative;
    z-index: 100001 !important;
}


#deleteModal .modal-backdrop,
.modal-backdrop[data-delete-backdrop] {
    z-index: 99998 !important;
    background-color: rgba(0, 0, 0, 0.75) !important;
}


#deleteModal .modal-header {
    background: linear-gradient(135deg, #fff5f5 0%, #ffe4e4 100%);
    padding: 2rem 2rem 1rem;
    border: none;
    position: relative;
}


#deleteModal .modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #dc2626;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}


#deleteModal .btn-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    opacity: 0.5;
    transition: all 0.3s ease;
}


#deleteModal .btn-close:hover {
    opacity: 1;
    transform: rotate(90deg);
}


#deleteModal .modal-body {
    padding: 2.5rem 2rem;
    background: white;
    text-align: center;
}


#deleteItemName {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    border: 2px solid #e2e8f0;
}


.delete-warning-text {
    color: #64748b;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}


.delete-warning-highlight {
    color: #dc2626;
    font-weight: 600;
}


#deleteModal .modal-footer {
    background: linear-gradient(to bottom, #f8fafc 0%, #f1f5f9 100%);
    padding: 1.5rem 2rem;
    border: none;
    display: flex;
    gap: 1rem;
    justify-content: center;
}


#deleteModal .btn {
    border-radius: 12px;
    padding: 0.875rem 2.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    position: relative;
    overflow: hidden;
}


#deleteModal .btn-cancel {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}


#deleteModal .btn-cancel:hover {
    background: linear-gradient(135deg, #475569 0%, #334155 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
}


#deleteModal .btn-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}


#deleteModal .btn-delete:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
}


#deleteModal.show .modal-dialog {
    animation: modalSlideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}


.delete-icon-wrapper {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
    animation: pulseDelete 2s ease-in-out infinite;
    position: relative;
}


.delete-icon-wrapper::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    opacity: 0.3;
    animation: ripple 2s ease-out infinite;
}


.delete-icon-wrapper i {
    font-size: 3rem;
    color: white;
    position: relative;
    z-index: 1;
}


/* Control de z-index para modales */
.modal {
    z-index: 1055 !important;
}


.modal-backdrop {
    z-index: 1050 !important;
}


#deleteModal {
    z-index: 1060 !important;
}


#deleteModal+.modal-backdrop {
    z-index: 1059 !important;
}


#imgModal {
    z-index: 1055 !important;
}


#imgModal+.modal-backdrop {
    z-index: 1054 !important;
}


/* Animaciones */
@keyframes pulseDelete {


    0%,
    100% {
        transform: scale(1);
        box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
    }


    50% {
        transform: scale(1.05);
        box-shadow: 0 15px 40px rgba(220, 38, 38, 0.4);
    }
}


@keyframes ripple {
    0% {
        transform: scale(1);
        opacity: 0.3;
    }


    100% {
        transform: scale(1.5);
        opacity: 0;
    }
}


@keyframes modalSlideDown {
    from {
        transform: translateY(-100px) scale(0.8);
        opacity: 0;
    }


    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}


/* ==========================================================================
   7. MEDIA QUERIES (Responsive)
   ========================================================================== */
@media (max-width: 768px) {
    .inventario-wrapper {
        padding: 1rem;
    }


    #inventario-form {
        padding: 1rem;
    }


    #tabla-inventario thead th,
    #tabla-inventario tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }


    .stock-display {
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


@media (max-width: 576px) {
    #deleteModal .modal-body {
        padding: 2rem 1.5rem;
    }


    #deleteModal .modal-footer {
        padding: 1rem 1.5rem;
        flex-direction: column;
    }


    #deleteModal .btn {
        width: 100%;
        justify-content: center;
    }


    .delete-icon-wrapper {
        width: 80px;
        height: 80px;
    }


    .delete-icon-wrapper i {
        font-size: 2.5rem;
    }
}


/* ==========================================================================
   8. MODO OSCURO (inventario)
   ========================================================================== */


body.dark-mode #inventario-form,
body.dark-mode .table-container,
body.dark-mode .pagination-wrapper {
    background: var(--bg-card);
}


/* Los íconos de botones con fondo de color sólido deben seguir blancos */
body.dark-mode #inventario-form button[type="submit"] i.bi,
body.dark-mode #inventario-form a[href*="inventario_pdf"] i.bi {
    color: white !important;
}


/* El botón "Limpiar" mantiene fondo blanco fijo; necesita texto oscuro */
body.dark-mode #inventario-form .btn-light.text-muted {
    color: #495057 !important;
}


/* MODIFICACIÓN: Encabezado de la tabla: verde sólido en modo oscuro */
body.dark-mode #tabla-inventario thead,
body.dark-mode #tabla-inventario thead th {
    background-color: #1e8449 !important; /* Verde un poco más oscuro para dark mode */
    color: white !important;
}


body.dark-mode #tabla-inventario thead th i {
    color: white !important;
}


/* Botones de acción (editar, entrada/salida, activar/desactivar) */
body.dark-mode .btn-action-edit,
body.dark-mode .btn-action-view,
body.dark-mode .btn-action-delete {
    background: var(--bg-card);
}


/* Filas inactivas (table-secondary) — Bootstrap las pinta con variables propias */
body.dark-mode #tabla-inventario tbody tr.table-secondary {
    --bs-table-bg: rgba(148, 163, 184, 0.08);
    --bs-table-color: var(--text-muted);
    --bs-table-border-color: var(--border-color);
    --bs-table-striped-bg: rgba(148, 163, 184, 0.08);
    --bs-table-hover-bg: rgba(148, 163, 184, 0.14);
    --bs-table-hover-color: var(--text-main);
    background-color: rgba(148, 163, 184, 0.08);
    color: var(--text-muted);
}


body.dark-mode #tabla-inventario tbody tr.table-secondary .item-nombre,
body.dark-mode #tabla-inventario tbody tr.table-secondary .stock-display {
    color: var(--text-muted) !important;
}


/* Buscador y filtros (capsulas bg-light) */
body.dark-mode .capsule-focus,
body.dark-mode .bg-light {
    background-color: var(--bg-body) !important;
}


body.dark-mode #inventario-form .form-control,
body.dark-mode #inventario-form .form-select,
body.dark-mode #inventario-form input,
body.dark-mode #inventario-form select {
    color: var(--text-main) !important;
}


body.dark-mode #inventario-form .text-dark {
    color: var(--text-main) !important;
}


body.dark-mode #inventario-form .text-muted {
    color: var(--text-muted) !important;
}


/* Tabla */
body.dark-mode #tabla-inventario tbody tr {
    border-bottom-color: var(--border-color);
}
@media (hover: hover) and (pointer: fine) {
    body.dark-mode #tabla-inventario.table-hover > tbody > tr:hover > * {
        background-color: #1f4d33 !important;
        color: #4ade80 !important;
    }
}
body.dark-mode .item-nombre {
    color: var(--text-dark) !important;
}


body.dark-mode .item-notas {
    color: var(--text-muted);
}


body.dark-mode .stock-display {
    color: var(--text-dark) !important;
}


body.dark-mode .category-tag {
    background: var(--bg-body);
    color: var(--text-main);
}


body.dark-mode .img-placeholder {
    background: var(--bg-body);
    color: var(--text-muted);
}


/* Paginación */
body.dark-mode .pagination-info,
body.dark-mode .per-page-selector label {
    color: var(--text-main);
}


body.dark-mode .per-page-selector select {
    background: var(--bg-body);
    color: var(--text-main);
    border-color: var(--border-color);
}


body.dark-mode .pagination-btn {
    background: var(--bg-body);
    color: var(--text-main);
    border-color: var(--border-color);
}


body.dark-mode .pagination-btn:hover:not(.disabled) {
    background: linear-gradient(135deg, #2abb4f 0%, #2abb4f 100%);
    color: white;
}


body.dark-mode .pagination-btn.active {
    color: white;
}


/* Badge "Filtros activos" */
body.dark-mode .badge.bg-light {
    background: var(--bg-body) !important;
    color: var(--text-main) !important;
}


/* Modal de eliminación (tiene fondos blancos fijos, más específicos que las reglas de index.php) */
body.dark-mode #deleteModal .modal-content,
body.dark-mode #deleteModal .modal-body,
body.dark-mode #deleteModal .modal-footer {
    background: var(--bg-card);
    color: var(--text-main);
}


body.dark-mode #deleteModal .modal-header {
    background: rgba(220, 38, 38, 0.1);
}


body.dark-mode #deleteItemName {
    background: var(--bg-body);
    color: var(--text-dark);
    border-color: var(--border-color);
}


body.dark-mode .delete-warning-text {
    color: var(--text-muted);
}


/* Modal Entrada/Salida de inventario */
body.dark-mode #movModal .modal-content {
    background: var(--bg-card);
    color: var(--text-main);
}


body.dark-mode #movModal .form-control,
body.dark-mode #movModal input {
    background: var(--bg-body);
    color: var(--text-main);
    border-color: var(--border-color);
}


body.dark-mode #movModal .modal-footer {
    background: transparent;
    border-color: var(--border-color);
}


/* Botón "Cancelar" (btn-light, fondo blanco fijo de Bootstrap) — faltaba este override */
body.dark-mode #movModal .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
}

/* Modal de confirmación (Activar/Desactivar) */
body.dark-mode #confirmToggleModal .modal-content {
    background: var(--bg-card);
    color: var(--text-main);
}

body.dark-mode #confirmToggleModal .text-muted {
    color: var(--text-muted) !important;
}

body.dark-mode #confirmToggleModal .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
    color: var(--text-main) !important;
}

/* Modal imagen zoom ya usa fondo negro, no necesita cambios */


/* --- Refuerzo de contraste: buscador, nombres, stock --- */


body.dark-mode #inventario-form input::placeholder {
    color: var(--text-muted) !important;
    opacity: 1;
}


body.dark-mode #inventario-form i.text-muted,
body.dark-mode #inventario-form i.bi {
    color: var(--text-muted) !important;
}


/* --- Anular el fondo blanco que Bootstrap pone a la tabla vía variables CSS --- */
body.dark-mode #tabla-inventario {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: transparent;
    --bs-table-hover-bg: #1f4d33;
    --bs-table-hover-color: #4ade80;
    --bs-table-color: var(--text-main);
    --bs-table-border-color: var(--border-color);
    background-color: transparent;
    color: var(--text-main);
}


body.dark-mode #tabla-inventario tbody td {
    color: var(--text-main);
}


/* Las opciones del <select> las pinta el sistema operativo, no CSS normal;
   esto ayuda en navegadores que sí lo respetan (Chrome/Edge) */
body.dark-mode #inventario-form select option {
    background: var(--bg-card);
    color: var(--text-main);
}


/* Caja "Mostrando X-Y de Z resultados" tenía fondo crema fijo */
body.dark-mode .results-count {
    background: rgba(243, 156, 18, 0.12);
    color: #2abb4f;
}


/* Texto "No se encontraron resultados" */
body.dark-mode .tab-pane .text-muted {
    color: var(--text-muted) !important;
}


/* Switch "Ver inactivos" más grande, a juego con el resto de los controles */
#chkInactivos {
    font-size: 1.6rem;
    cursor: pointer;
}


.form-check.form-switch:has(#chkInactivos) {
    gap: 0.75rem !important;
}


.form-check.form-switch:has(#chkInactivos) .form-check-label {
    font-size: 1rem;
    font-weight: 500;
}
</style>


<!-- ==========================================================================
     HTML: CONTENEDOR PRINCIPAL
     ========================================================================== -->
<div class="inventario-wrapper">


    <!-- BÚSQUEDA Y FILTROS -->


    <form id="inventario-form" method="get" action="index.php#inv" class="m-0">
        <input type="hidden" name="tab" value="inv">
        <input type="hidden" name="page_inv" value="1">
        <input type="hidden" name="per_page_inv" value="<?=$items_por_pagina?>">


        <div class="row g-3 align-items-center mb-3">
            <!-- Búsqueda Principal -->
            <div class="col-12 col-md-5 col-lg-6">
                <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus"
                    style="border-color: var(--border-color) !important; transition: all 0.2s;">
                    <i class="bi bi-search text-muted transition-colors"></i>
                    <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none ms-2 text-dark"
                        placeholder="Buscar artículo, código o condición..." value="<?=h($q)?>">
                </div>
            </div>


            <!-- Filtro de Clases -->
            <div class="col-12 col-md-4 col-lg-3">
                <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus"
                    style="border-color: var(--border-color) !important; transition: all 0.2s;">
                    <i class="bi bi-tags text-muted transition-colors"></i>
                    <select name="clase_id" class="form-select border-0 bg-transparent shadow-none ms-1 text-muted"
                        style="cursor: pointer;">
                        <option value="">Todas las Clases</option>
                        <?php foreach ($clases as $c): ?>
                        <option value="<?=$c['id']?>" <?=$clase_id===$c['id']?'selected':''?>><?=h($c['nombre'])?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>


        <!-- Switch Inactivos -->
<div class="col-12 col-md-3 col-lg-3 d-flex justify-content-md-end">
    <div class="form-check form-switch m-0 d-flex align-items-center gap-3">
        <input type="hidden" name="inactivos" value="0">
        <input class="form-check-input m-0 shadow-none" type="checkbox" role="switch" name="inactivos"
            value="1" id="chkInactivos" style="cursor: pointer;" <?= $solo_inactivos ? 'checked' : '' ?>>
        <label class="form-check-label text-muted" for="chkInactivos"
            style="cursor: pointer; user-select: none;">Ver inactivos</label>
    </div>
</div>
        </div>


        <!-- Botones Búsqueda -->
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-3 border-top">
            <div class="text-muted small">
                <?php if(!empty($_GET['q']) || !empty($_GET['clase_id'])): ?>
                <span class="badge bg-light text-primary border fw-normal px-2 py-1"><i class="fas fa-filter"></i>
                    Filtros activos</span>
                <?php endif; ?>
            </div>


            <div class="d-flex flex-wrap gap-2">
                <button type="submit"
                    class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
                    style="background-color: var(--primary); border: none;">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <a href="index.php?tab=inv#inv"
                    class="btn btn-light border rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 text-muted">
                    <i class="fas fa-eraser"></i> Limpiar
                </a>
                <a href="public/inventario_pdf.php?q=<?=urlencode($q)?><?= !is_null($clase_id) ? '&clase_id='.$clase_id : '' ?>"
                    class="btn btn-danger rounded-pill px-3 shadow-sm d-flex align-items-center gap-2"
                    style="background-color: var(--danger); border: none;">
                    <i class="bi bi-filetype-pdf"></i> PDF
                </a>
                <?php if (isset($is_admin) && $is_admin): ?>
                <button type="button" data-bs-toggle="modal" data-bs-target="#modalAgregar"
                    class="btn btn-success rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
                    style="background-color: var(--success); border: none;">
                    <i class="fas fa-plus"></i> Nuevo
                </button>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <?php if (isset($is_admin) && $is_admin): ?>
    <?php include __DIR__ . '/agregar.php'; ?>
    <?php endif; ?>




    <!-- TABLA DE INVENTARIO -->
    <div class="table-container mt-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabla-inventario">
                <thead>
                    <tr>
                        <th><i class="bi bi-image"></i> Imagen</th>
                        <th><i class="bi bi-box-seam"></i> Producto</th>
                        <th><i class="bi bi-tag"></i> Clase</th>
                        <th><i class="bi bi-geo-alt"></i> Ubicación</th>
                        <th><i class="bi bi-shield-check"></i> Condición</th>
                        <th><i class="bi bi-stack"></i> Stock</th>
                        <th><i class="bi bi-graph-up"></i> Estado</th>
                        <?php if ($is_admin): ?>
                        <th><i class="bi bi-gear"></i> Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="<?=$is_admin ? 8 : 7?>" class="text-center py-5">
                            <div style="font-size: 3rem; color: #2abb4f; margin-bottom: 1rem;"><i
                                    class="bi bi-inbox"></i>
                            </div>
                            <p class="text-muted mb-0">No se encontraron resultados</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $it):


                            $isInactive = isset($it['activo']) && !$it['activo'];
                            $rowClass   = $isInactive ? 'table-secondary opacity-75' : '';


                            if (!$it['activo']) {
                                $estado = 'Inactivo';
                                $badgeClass = 'bg-secondary';
                                $iconoEstado = 'pause-circle-fill';
                            } else {
                                if ((int)$it['cantidad'] <= (int)$it['min_stock']) {
                                    $estado = 'Reponer';
                                    $badgeClass = 'badge-reponer';
                                    $iconoEstado = 'exclamation-triangle-fill';
                                } elseif ((int)$it['cantidad'] < (int)$it['max_stock']) {
                                    $estado = 'Bajo';
                                    $badgeClass = 'badge-bajo';
                                    $iconoEstado = 'exclamation-circle-fill';
                                } else {
                                    $estado = 'OK';
                                    $badgeClass = 'badge-ok';
                                    $iconoEstado = 'check-circle-fill';
                                }
                            }


                            $rowDataAttr = (!empty($it['imagen'])) ? ' data-img="'.h($it['imagen']).'"' : '';
                            $rowStyle    = (!empty($it['imagen'])) ? ' style="cursor:pointer;"' : '';


                            $editDataAttrs =
                                ' data-item-id="'.intval($it['id']).'"'.
                                ' data-item-nombre="'.h($it['nombre']).'"'.
                                ' data-item-clase="'.(!empty($it['clase_id']) ? intval($it['clase_id']) : '').'"'.
                                ' data-item-cantidad="'.intval($it['cantidad']).'"'.
                                ' data-item-min="'.intval($it['min_stock']).'"'.
                                ' data-item-max="'.intval($it['max_stock']).'"'.
                                ' data-item-condicion="'.(!empty($it['condicion_id']) ? intval($it['condicion_id']) : '').'"'.
                                ' data-item-ubicacion="'.(!empty($it['ubicacion_id']) ? intval($it['ubicacion_id']) : '').'"'.
                                ' data-item-notas="'.h($it['notas'] ?? '').'"'.
                                ' data-item-imagen="'.h($it['imagen'] ?? '').'"';
                        ?>
                    <tr class="<?=$rowClass?>" <?=$rowDataAttr?><?=$rowStyle?><?=$editDataAttrs?>>
                        <td>
                            <?php if (!empty($it['imagen'])): ?>
                            <a href="#" class="img-thumb d-inline-block" data-img="<?=h($it['imagen'])?>"
                                title="Ver imagen">
                                <img src="uploads/<?=h($it['imagen'])?>" class="img-thumbnail"
                                    style="width:64px;height:64px;object-fit:cover;" alt="">
                            </a>
                            <?php else: ?>
                            <div class="img-placeholder"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>


                        <td>
                            <div class="item-nombre <?=!empty($it['imagen'])?'nombre':''?>"
                                <?=!empty($it['imagen'])?'style="cursor:pointer;"':''?>>
                                <?=h($it['nombre'])?>
                            </div>
                            <?php if (!empty($it['notas'])): ?>
                            <div class="item-notas"><i class="bi bi-chat-left-text"></i> <?=h($it['notas'])?></div>
                            <?php endif; ?>
                        </td>


                        <td>
                            <?php if (!empty($it['clase_nombre'])): ?>
                            <span class="category-tag"><i class="bi bi-tag-fill"></i>
                                <?=h($it['clase_nombre'])?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>


                        <td>
                            <?php if (!empty($it['ubicacion_nombre'])): ?>
                            <span class="category-tag"><i class="bi bi-pin-map-fill"></i>
                                <?=h($it['ubicacion_nombre'])?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>


                        <td>
                            <?php if (!empty($it['condicion_nombre'])): ?>
                            <span class="category-tag"><i class="bi bi-shield-fill-check"></i>
                                <?=h($it['condicion_nombre'])?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>


                        <td><span class="stock-display"><?=intval($it['cantidad'])?></span></td>


                        <td>
                            <span class="badge-custom <?=$badgeClass?>"><i class="bi bi-<?=$iconoEstado?>"></i>
                                <?=$estado?></span>
                        </td>


                        <?php if ($is_admin): ?>
                        <td>
                            <div class="btn-action-group">
                                <?php if ($it['activo']): ?>
                                <button type="button" class="btn-action btn-action-view btn-mov"
                                    data-id="<?=intval($it['id'])?>" data-tipo="ENTRADA" title="Entrada"><i
                                        class="bi bi-plus-circle-fill"></i></button>
                                <button type="button" class="btn-action btn-action-view btn-mov"
                                    data-id="<?=intval($it['id'])?>" data-tipo="SALIDA" title="Salida"><i
                                        class="bi bi-dash-circle-fill"></i></button>
                                <button type="button" class="btn-action btn-action-edit btn-edit-item"
                                    title="Editar"><i class="bi bi-pencil-square"></i></button>
                                <button type="button" class="btn-action btn-action-view btn-toggle"
                                    data-id="<?=intval($it['id'])?>" data-estado="0" title="Desactivar"><i
                                        class="bi bi-pause-circle-fill"></i></button>
                                <?php else: ?>
                                <button type="button" class="btn-action btn-action-edit btn-toggle"
                                    data-id="<?=intval($it['id'])?>" data-estado="1" title="Activar"><i
                                        class="bi bi-play-circle-fill"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- PAGINACIÓN -->
    <?php if ($total_paginas > 1): ?>
    <div class="pagination-wrapper">
        <div class="pagination-info">
            <div class="results-count">
                <i class="bi bi-list-ul"></i>
                <span>Mostrando <?=min($offset + 1, $total_items)?> -
                    <?=min($offset + $items_por_pagina, $total_items)?> de <?=$total_items?> resultados</span>
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
            <a href="<?=buildUrl(['q' => $q, 'clase_id' => $clase_id, 'inactivos' => $solo_inactivos ? '1' : null, 'page_inv' => 1, 'per_page_inv' => $items_por_pagina])?>"
                class="pagination-btn arrow <?=$pagina_actual==1?'disabled':''?>"><i
                    class="bi bi-chevron-double-left"></i></a>
            <a href="<?=buildUrl(['q' => $q, 'clase_id' => $clase_id, 'inactivos' => $solo_inactivos ? '1' : null, 'page_inv' => max(1, $pagina_actual - 1), 'per_page_inv' => $items_por_pagina])?>"
                class="pagination-btn arrow <?=$pagina_actual==1?'disabled':''?>"><i class="bi bi-chevron-left"></i></a>


            <?php
                $rango = 2;
                $inicio = max(1, $pagina_actual - $rango);
                $fin    = min($total_paginas, $pagina_actual + $rango);


                if ($inicio > 1): ?>
            <a href="<?=buildUrl(['q'=>$q, 'clase_id'=>$clase_id, 'inactivos'=>$solo_inactivos ? '1' : null, 'page_inv'=>1, 'per_page_inv'=>$items_por_pagina])?>"
                class="pagination-btn">1</a>
            <?php if ($inicio > 2): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <?php endif; ?>


            <?php for ($i = $inicio; $i <= $fin; $i++): ?>
            <a href="<?=buildUrl(['q'=>$q, 'clase_id'=>$clase_id, 'inactivos'=>$solo_inactivos ? '1' : null, 'page_inv'=>$i, 'per_page_inv'=>$items_por_pagina])?>"
                class="pagination-btn <?=$i==$pagina_actual?'active':''?>"><?=$i?></a>
            <?php endfor; ?>


            <?php if ($fin < $total_paginas): ?>
            <?php if ($fin < $total_paginas - 1): ?><span class="pagination-ellipsis">...</span><?php endif; ?>
            <a href="<?=buildUrl(['q'=>$q, 'clase_id'=>$clase_id, 'inactivos'=>$solo_inactivos ? '1' : null, 'page_inv'=>$total_paginas, 'per_page_inv'=>$items_por_pagina])?>"
                class="pagination-btn"><?=$total_paginas?></a>
            <?php endif; ?>


            <a href="<?=buildUrl(['q'=>$q, 'clase_id'=>$clase_id, 'inactivos'=>$solo_inactivos ? '1' : null, 'page_inv'=>min($total_paginas, $pagina_actual + 1), 'per_page_inv'=>$items_por_pagina])?>"
                class="pagination-btn arrow <?=$pagina_actual==$total_paginas?'disabled':''?>"><i
                    class="bi bi-chevron-right"></i></a>
            <a href="<?=buildUrl(['q'=>$q, 'clase_id'=>$clase_id, 'inactivos'=>$solo_inactivos ? '1' : null, 'page_inv'=>$total_paginas, 'per_page_inv'=>$items_por_pagina])?>"
                class="pagination-btn arrow <?=$pagina_actual==$total_paginas?'disabled':''?>"><i
                    class="bi bi-chevron-double-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- ==========================================================================
         MODALES
         ========================================================================== -->


<!-- Modal de imagen con CANVAS -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content position-relative">
            <button type="button"
                class="btn btn-danger btn-sm rounded-circle position-absolute end-0 m-3 img-modal-close d-flex align-items-center justify-content-center"
                data-bs-dismiss="modal" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" aria-hidden="true">
                    <path fill="currentColor"
                        d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z" />
                </svg>
            </button>
            <div class="modal-body p-0 zoom-container" id="imgZoomContainer">
                <canvas id="imgCanvas"></canvas>
                <img id="imgPreloader" alt="" style="display:none;">
            </div>
        </div>
    </div>
</div>


<!-- Modal Entradas/Salidas de inventario -->
<div class="modal fade" id="movModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form id="movForm">
                <?= csrf_field() ?>
                <input type="hidden" name="item_id" id="movItemId">
                <input type="hidden" name="tipo" id="movTipo">


                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3">
                        <div id="movIconWrap" class="rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 45px; height: 45px;">
                            <i id="movIcon" class="fs-5"></i>
                        </div>
                        <span id="movTitle"></span>
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>


                <div class="modal-body p-4 pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="custom-form-label"><i class="bi bi-hash text-primary"></i> Cantidad
                                <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad" id="movCantidad" class="form-control custom-input"
                                min="1" required placeholder="Ingresa la cantidad">
                        </div>


                        <div class="col-12">
                            <label class="custom-form-label"><i class="bi bi-chat-left-text text-primary"></i>
                                Comentario</label>
                            <input type="text" name="motivo" id="movMotivo" class="form-control custom-input"
                                placeholder="Describe el motivo del movimiento (opcional)">
                        </div>


                        <div class="col-12">
                            <div class="alert alert-danger d-none mb-0" id="movError" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <span id="movErrorText"></span>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="modal-footer border-top pt-3 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted"
                        data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" id="movSubmitBtn"
                        class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
                        style="border: none; transition: transform 0.2s;"
                        onmouseover="this.style.transform='translateY(-2px)'"
                        onmouseout="this.style.transform='translateY(0)'">
                        <i class="bi bi-check-circle"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Modal de confirmación (Activar/Desactivar) -->
<div class="modal fade" id="confirmToggleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body p-4 text-center">
                <div id="confirmToggleIconWrap" class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                    <i id="confirmToggleIcon" class="fs-3"></i>
                </div>
                <h5 class="fw-bold mb-2" id="confirmToggleTitle"></h5>
                <p class="text-muted mb-0" id="confirmToggleText"></p>
            </div>
            <div class="modal-footer border-top-0 pt-0 pb-4 d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" id="confirmToggleBtn" class="btn rounded-pill px-4 fw-semibold text-white" style="border: none;">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="errorToggleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-body text-center p-4">

                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width:72px;height:72px;background:rgba(231,76,60,.12);">
                    <i class="bi bi-exclamation-triangle-fill fs-1 text-danger"></i>
                </div>

                <h5 id="errorToggleTitle" class="fw-bold mb-2">
                    No se puede desactivar
                </h5>

                <p id="errorToggleText" class="text-muted mb-4">
                    Este artículo todavía tiene existencias.
                </p>

                <button type="button"
                        class="btn btn-danger px-4"
                        data-bs-dismiss="modal">
                    Entendido
                </button>

            </div>

        </div>
    </div>
</div>

<script>
// Movimientos de inventario (Entrada/Salida)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-mov');
    if (!btn) return;

    const tipo = btn.dataset.tipo;
    const esEntrada = tipo === 'ENTRADA';

    document.getElementById('movItemId').value = btn.dataset.id;
    document.getElementById('movTipo').value = tipo;
    document.getElementById('movTitle').innerText = esEntrada ?
        'Entrada de inventario' : 'Salida de inventario';

    const iconWrap = document.getElementById('movIconWrap');
    const icon = document.getElementById('movIcon');
    const submitBtn = document.getElementById('movSubmitBtn');
    const color = esEntrada ? '#10b981' : '#ef4444';
    const colorLight = esEntrada ? 'rgba(16, 185, 129, 0.12)' : 'rgba(239, 68, 68, 0.12)';

    iconWrap.style.backgroundColor = colorLight;
    icon.className = esEntrada ? 'fs-5 bi bi-plus-circle-fill' : 'fs-5 bi bi-dash-circle-fill';
    icon.style.color = color;
    submitBtn.style.backgroundColor = color;

    document.getElementById('movForm').reset();
    document.getElementById('movError').classList.add('d-none');

    // Reset por si quedó bloqueado de un envío anterior interrumpido
    const form = document.getElementById('movForm');
    delete form.dataset.enviando;
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar';

    bootstrap.Modal.getOrCreateInstance(document.getElementById('movModal')).show();
});

document.getElementById('movForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;

    // Blindaje anti doble-envío / doble clic: si ya hay un envío en curso, ignorar
    if (form.dataset.enviando === '1') return;
    form.dataset.enviando = '1';

    const submitBtn = document.getElementById('movSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

    const data = new FormData(form);

    let json;
    try {
        const res = await fetch('public/ajax/movimiento_inventario.php', {
            method: 'POST',
            body: data
        });
        json = await res.json();
    } catch (err) {
        const errBox = document.getElementById('movError');
        errBox.innerText = 'Error de conexión, intenta de nuevo.';
        errBox.classList.remove('d-none');
        delete form.dataset.enviando;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar';
        return;
    }

    if (!json.ok) {
        const err = document.getElementById('movError');
        err.innerText = json.error || 'Error';
        err.classList.remove('d-none');
        delete form.dataset.enviando;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar';
        return;
    }

    const row = document.querySelector(`.btn-mov[data-id="${data.get('item_id')}"]`).closest('tr');
    row.querySelector('.stock-display').innerText = json.stock;

    delete form.dataset.enviando;
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Confirmar';

    bootstrap.Modal.getInstance(document.getElementById('movModal')).hide();
});
</script>

<script>
// Activar / desactivar items SIN recargar (con modal de confirmación)
let pendingToggle = null;

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-toggle');
    if (!btn) return;

    const id = btn.dataset.id;
    const estado = parseInt(btn.dataset.estado, 10);
    const esDesactivar = estado === 0;

    pendingToggle = { id, estado, btn };

    document.getElementById('confirmToggleTitle').innerText = esDesactivar ? 'Desactivar artículo' : 'Activar artículo';
    document.getElementById('confirmToggleText').innerText = esDesactivar ?
        '¿Seguro que deseas desactivar este artículo?' : '¿Seguro que deseas activar este artículo?';

    const iconWrap = document.getElementById('confirmToggleIconWrap');
    const icon = document.getElementById('confirmToggleIcon');
    const confirmBtn = document.getElementById('confirmToggleBtn');
    const color = esDesactivar ? '#e74c3c' : '#27ae60';
    const colorLight = esDesactivar ? 'rgba(231, 76, 60, 0.12)' : 'rgba(39, 174, 96, 0.12)';

    iconWrap.style.backgroundColor = colorLight;
    icon.className = esDesactivar ? 'fs-3 bi bi-pause-circle-fill' : 'fs-3 bi bi-play-circle-fill';
    icon.style.color = color;
    confirmBtn.style.backgroundColor = color;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmToggleModal')).show();
});

document.getElementById('confirmToggleBtn').addEventListener('click', async function() {
    if (!pendingToggle) return;
    const { id, estado, btn } = pendingToggle;

    const data = new FormData();
    data.append('id', id);
    data.append('estado', estado);
    data.append('csrf', document.querySelector('input[name="csrf"]')?.value);

    const res = await fetch('public/ajax/toggle_item.php', {
        method: 'POST',
        body: data
    });
    const json = await res.json();

    bootstrap.Modal.getInstance(document.getElementById('confirmToggleModal')).hide();

    if (!json.ok) {

        document.getElementById('errorToggleTitle').innerText = 'No se pudo completar la acción';

        document.getElementById('errorToggleText').innerText =
            json.message || 'Ocurrió un error.';

        bootstrap.Modal
            .getOrCreateInstance(document.getElementById('errorToggleModal'))
            .show();

        pendingToggle = null;
        return;
    }

    const row = btn.closest('tr');
    const badge = row.querySelector('.badge-custom');
    const acciones = row.querySelector('.btn-action-group');

    if (estado === 0) {
        row.classList.add('table-secondary', 'opacity-75');
        badge.className = 'badge-custom bg-secondary';
        badge.innerHTML = `<i class="bi bi-pause-circle-fill"></i> Inactivo`;
        acciones.innerHTML =
            `<button type="button" class="btn-action btn-action-edit btn-toggle" data-id="${id}" data-estado="1" title="Activar"><i class="bi bi-play-circle-fill"></i></button>`;
    } else {
        row.classList.remove('table-secondary', 'opacity-75');
        badge.className = 'badge-custom badge-ok';
        badge.innerHTML = `<i class="bi bi-check-circle-fill"></i> OK`;
        acciones.innerHTML = `
                <button type="button" class="btn-action btn-action-view btn-mov" data-id="${id}" data-tipo="ENTRADA" title="Entrada"><i class="bi bi-plus-circle-fill"></i></button>
                <button type="button" class="btn-action btn-action-view btn-mov" data-id="${id}" data-tipo="SALIDA" title="Salida"><i class="bi bi-dash-circle-fill"></i></button>
                <a class="btn-action btn-action-edit" href="public/editar.php?id=${id}" title="Editar"><i class="bi bi-pencil-square"></i></a>
                <button type="button" class="btn-action btn-action-view btn-toggle" data-id="${id}" data-estado="0" title="Desactivar"><i class="bi bi-pause-circle-fill"></i></button>
            `;
    }

    pendingToggle = null;
    fetch(window.location.href)
    .then(res => res.text())
    .then(html => {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        
        document.querySelector('main').innerHTML =
            doc.querySelector('main').innerHTML;
    });
});

// Guarda la posición del scroll al salir a editar
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-save-scroll]');
    if (!link) return;
    sessionStorage.setItem('inv_scroll', window.scrollY);
});
</script>

<script>
(function(){
  
  const root = document.getElementById('inv');
  if (!root) return;

  const form = root.querySelector('#inventario-form');
  const q    = form?.querySelector('input[name="q"]');
  const clase = form?.querySelector('select[name="clase_id"]');
  const perPageInput  = form.querySelector('input[name="per_page_inv"]');
  const perPageSelect = root.querySelector('#perPageSelect');

  let t;

  // q.addEventListener('input', function(){
  //   clearTimeout(t);
  //   t = setTimeout(function(){ form.submit(); }, 400);
  // });

  clase.addEventListener('change', function(){ form.submit(); });

  // Cambio de items por página
  if (perPageSelect) {
    perPageSelect.addEventListener('change', function(){
      if (perPageInput) {
        perPageInput.value = this.value;
      }
      form.submit();
    });
  }

  const modalEl   = document.getElementById('imgModal');
  const zoomBox   = document.getElementById('imgZoomContainer');
  const closeBtn  = document.querySelector('#imgModal .img-modal-close');
  const canvas    = document.getElementById('imgCanvas');
  const ctx       = canvas.getContext('2d');
  const loaderImg = document.getElementById('imgPreloader');

  let bsModal = null;
  let modalShown = false;
  let imgReady   = false;

  function ensureModal(){
    if (!bsModal) {
      if (!window.bootstrap || !bootstrap.Modal) return null;
      bsModal = new bootstrap.Modal(modalEl);
    }
    return bsModal;
  }

  let scale = 1, minScale = 0.9, maxScale = 4;
  let tx = 0, ty = 0;
  let imgW = 0, imgH = 0;
  let baseW = 0, baseH = 0;
  let dragging = false, lx = 0, ly = 0;

  function resizeCanvas(){
    const dpr = window.devicePixelRatio || 1;
    const cw = zoomBox.clientWidth;
    const ch = zoomBox.clientHeight;
    canvas.width  = Math.round(cw * dpr);
    canvas.height = Math.round(ch * dpr);
    canvas.style.width  = cw + 'px';
    canvas.style.height = ch + 'px';
    ctx.setTransform(1,0,0,1,0,0);
    ctx.scale(dpr, dpr);
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
  }

  function computeBaseSize(){
    const cw = zoomBox.clientWidth;
    const ch = zoomBox.clientHeight;
    const s = Math.min(cw / imgW, ch / imgH);
    baseW = imgW * s;
    baseH = imgH * s;
  }

  function clamp(){
    const cw = zoomBox.clientWidth;
    const ch = zoomBox.clientHeight;
    const dispW = baseW * scale;
    const dispH = baseH * scale;
    const maxX = Math.max(0, (dispW - cw) / 2);
    const maxY = Math.max(0, (dispH - ch) / 2);
    if (tx >  maxX) tx =  maxX;
    if (tx < -maxX) tx = -maxX;
    if (ty >  maxY) ty =  maxY;
    if (ty < -maxY) ty = -maxY;
  }

  function draw(){
    ctx.fillStyle = '#000';
    ctx.fillRect(0,0,canvas.width,canvas.height);

    const cw = zoomBox.clientWidth;
    const ch = zoomBox.clientHeight;
    const cx = cw/2;
    const cy = ch/2;

    const rtx = Math.round(tx);
    const rty = Math.round(ty);
    const rscale = Math.round(scale * 100) / 100;

    const w = baseW * rscale;
    const h = baseH * rscale;
    const x = Math.round(cx - w/2 + rtx);
    const y = Math.round(cy - h/2 + rty);

    ctx.drawImage(loaderImg, x, y, Math.round(w), Math.round(h));

    if (rscale > 1) zoomBox.classList.add('can-pan'); else zoomBox.classList.remove('can-pan');
  }

  let needsDraw = false;

  function safeDraw() {
    if (needsDraw) return;
    needsDraw = true;

    requestAnimationFrame(() => {
      draw();
      needsDraw = false;
    });
  }



  function resetView(toScale=1){
    scale = toScale; tx = 0; ty = 0;
    resizeCanvas();
    computeBaseSize();
    safeDraw();
  }

  function openImgModal(imgFile){
    if (!imgFile) return;

    imgReady = false;
    loaderImg.onload = function(){
      imgW = loaderImg.naturalWidth;
      imgH = loaderImg.naturalHeight;
      imgReady = true;
      if (modalShown) resetView(1);
    };
    loaderImg.src = 'uploads/' + imgFile;

    const m = ensureModal();
    if (m) m.show();
  }

  modalEl.addEventListener('shown.bs.modal', () => {
    modalShown = true;
    if (imgReady) resetView(1);
  });

  modalEl.addEventListener('hidden.bs.modal', () => {
    modalShown = false;
    scale = 1; tx = 0; ty = 0; imgW = 0; imgH = 0;
    ctx.clearRect(0,0,canvas.width,canvas.height);
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const m = ensureModal(); if (m) m.hide();
    });
  }

  window.addEventListener('resize', () => { 
    if (modalShown && imgReady) resetView(scale);
  });

  zoomBox.addEventListener('wheel', (e) => {
    e.preventDefault();
    if (!imgW || !imgH || !modalShown) return;

    const rect = zoomBox.getBoundingClientRect();
    const pcx = e.clientX - rect.left - rect.width/2 - tx;
    const pcy = e.clientY - rect.top  - rect.height/2 - ty;

    const step = 0.25;
    let newScale = scale + (e.deltaY > 0 ? -step : step);
    newScale = Math.min(maxScale, Math.max(minScale, newScale));
    newScale = Math.round(newScale * 100) / 100;
    if (newScale === scale) return;

    const k = newScale / scale;
    tx = tx - pcx * (k - 1);
    ty = ty - pcy * (k - 1);
    scale = newScale;
    clamp();
    safeDraw();
  }, { passive:false });

  zoomBox.addEventListener('mousedown', (e) => {
    if (!modalShown || !imgReady) return;
    if (e.button !== 0) return;
    if (scale <= 1) return;
    e.preventDefault();
    dragging = true;
    zoomBox.classList.add('grabbing');
    lx = e.clientX; ly = e.clientY;
  });

  zoomBox.addEventListener('mousemove', (e) => {
    if (!dragging) return;
    if ((e.buttons & 1) !== 1) {
      dragging = false; zoomBox.classList.remove('grabbing'); return;
    }
    const dx = e.clientX - lx;
    const dy = e.clientY - ly;
    lx = e.clientX; ly = e.clientY;
    tx += dx; ty += dy;
    clamp();
    safeDraw();
  });

  ['mouseup','mouseleave'].forEach(ev => {
    zoomBox.addEventListener(ev, () => { dragging = false; zoomBox.classList.remove('grabbing'); });
  });

  zoomBox.addEventListener('dblclick', (e) => {
    e.preventDefault();
    if (!imgW || !imgH || !modalShown) return;

    const rect = zoomBox.getBoundingClientRect();
    const pcx = e.clientX - rect.left - rect.width/2 - tx;
    const pcy = e.clientY - rect.top  - rect.height/2 - ty;

    const target = (scale <= 1.1) ? 2 : 1;
    const k = target / scale;
    tx = tx - pcx * (k - 1);
    ty = ty - pcy * (k - 1);
    scale = target;
    clamp();
    safeDraw();
  });

  document.querySelectorAll('.img-thumb[data-img]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      openImgModal(el.getAttribute('data-img'));
    });
  });

  document.getElementById('tabla-inventario')?.addEventListener('click', function (e) {
    const tr = e.target.closest('tr[data-img]');
    if (!tr) return;
    if (e.target.closest('a,button,form,input,select,label')) return;

    openImgModal(tr.getAttribute('data-img'));
  });

})();

</script>

<?php include __DIR__.'/editar.php'; ?>
