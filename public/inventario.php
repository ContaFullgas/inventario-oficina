<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

// Permisos (index ya cargó auth.php; usamos helper)
$is_admin = function_exists('auth_is_admin') ? auth_is_admin() : false;

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase_id = (isset($_GET['clase_id']) && $_GET['clase_id'] !== '') ? (int)$_GET['clase_id'] : null;

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
$sql .= " ORDER BY i.nombre";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
?>
<form id="inventario-form" class="row gy-2 gx-2 align-items-end mb-3" method="get" action="index.php#inv">
  <input type="hidden" name="tab" value="inv">
  <div class="col-md-5">
    <label class="form-label">Buscar</label>
    <input type="text" name="q" class="form-control" placeholder="Nombre, clase, condición, ubicación o notas" value="<?=h($q)?>">
  </div>
  <div class="col-md-5">
    <label class="form-label">Clase</label>
    <select name="clase_id" class="form-select">
      <option value="">(Todas)</option>
      <?php foreach ($clases as $c): ?>
        <option value="<?=$c['id']?>" <?=$clase_id===$c['id']?'selected':''?>><?=h($c['nombre'])?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-flex gap-2">
    <!-- <button class="btn btn-primary w-100" type="submit">Filtrar</button> -->
    <a class="btn btn-outline-secondary w-100" href="index.php?tab=inv#inv">Limpiar</a>
  </div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle" id="tabla-inventario">
  <thead class="table-light">
    <tr>
      <th>Imagen</th>
      <th>Nombre</th>
      <th>Clase</th>
      <th>Ubicación</th>
      <th>Condición</th>
      <th>Stock</th>
      <th>Mín</th>
      <th>Máx</th>
      <th>Estado</th>
      <?php if ($is_admin): ?>
        <th>Acciones</th>
      <?php endif; ?>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it):
      $estado = 'OK'; $badge = 'bg-success';
      if ((int)$it['cantidad'] <= (int)$it['min_stock']) { $estado='Reponer'; $badge='bg-danger'; }
      elseif ((int)$it['cantidad'] < (int)$it['max_stock']) { $estado='Bajo'; $badge='bg-warning text-dark'; }

      $rowDataAttr = (!empty($it['imagen'])) ? ' data-img="'.h($it['imagen']).'"' : '';
      $rowStyle = (!empty($it['imagen'])) ? ' style="cursor:pointer;"' : '';
    ?>
    <tr<?=$rowDataAttr?><?=$rowStyle?>>
      <td style="width:72px;">
        <?php if (!empty($it['imagen'])): ?>
          <a href="#" class="img-thumb d-inline-block" data-img="<?=h($it['imagen'])?>" title="Ver imagen">
            <img src="../uploads/<?=h($it['imagen'])?>" class="img-thumbnail" style="width:64px;height:64px;object-fit:cover;" alt="">
          </a>
        <?php else: ?>
          <div class="bg-secondary" style="width:64px;height:64px;border-radius:.5rem;"></div>
        <?php endif; ?>
      </td>
      <td>
        <div class="nombre d-inline-block<?=!empty($it['imagen'])?' link-primary':''?>" <?=!empty($it['imagen'])?'style="cursor:pointer;"':''?>>
          <strong><?=h($it['nombre'])?></strong>
        </div>
        <br><small class="text-muted"><?=h($it['notas'] ?? '')?></small>
      </td>
      <td><?=h($it['clase_nombre'] ?? '')?></td>
      <td><?=h($it['ubicacion_nombre'] ?? '')?></td>
      <td><?=h($it['condicion_nombre'] ?? '')?></td>
      <td><?=intval($it['cantidad'])?></td>
      <td><?=intval($it['min_stock'])?></td>
      <td><?=intval($it['max_stock'])?></td>
      <td><span class="badge <?=$badge?>"><?=$estado?></span></td>
      <?php if ($is_admin): ?>
      <td class="no-modal">
        <a class="btn btn-sm btn-outline-primary" href="editar.php?id=<?=intval($it['id'])?>">Editar</a>
        <form action="eliminar.php" method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este registro?');">
          <?=csrf_field()?>
          <input type="hidden" name="id" value="<?=intval($it['id'])?>">
          <button class="btn btn-sm btn-outline-danger">Eliminar</button>
        </form>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Modal de imagen con CANVAS -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content position-relative">
      <!-- Botón rojo (X) con SVG -->
      <button
        type="button"
        class="btn btn-danger btn-sm rounded-circle position-absolute end-0 m-3 img-modal-close d-flex align-items-center justify-content-center"
        data-bs-dismiss="modal"
        aria-label="Close">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" aria-hidden="true">
          <path fill="currentColor" d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z"/>
        </svg>
      </button>
      <div class="modal-body p-0 zoom-container" id="imgZoomContainer">
        <canvas id="imgCanvas"></canvas>
        <!-- Preloader oculto para obtener dimensiones naturales -->
        <img id="imgPreloader" alt="" style="display:none;">
      </div>
    </div>
  </div>
</div>

<style>
  /* Botón de cierre rojo */
  .img-modal-close {
    z-index: 2;
    width: 36px; height: 36px;
    line-height: 1;
    text-align: center;
    font-weight: bold;
    font-size: 20px;
    padding: 0;
  }

  /* Área de zoom desplazable */
  .zoom-container{
    position: relative;
    overflow: hidden;
    background: #000;
    cursor: default;
    user-select: none;
    -webkit-user-select: none;
    height: 80vh;               /* alto fijo para el canvas */
    display: block;
  }
  .zoom-container.can-pan { cursor: grab; }
  .zoom-container.can-pan.grabbing { cursor: grabbing; }

  /* Canvas ocupa todo el contenedor */
  #imgCanvas{
    display: block;
    width: 100%;
    height: 100%;
    image-rendering: auto;       /* suavizado correcto */
  }

  /* Esquinas redondeadas del modal y recorte del contenido */
  #imgModal .modal-content{
    border-radius: 16px;   /* ajusta a tu gusto: 8px, 12px, 16px... */
    overflow: hidden;      /* importante para que el canvas también se redondee */
  }

  /* Opcional: sombra suave para que “flote” un poco */
  #imgModal .modal-content{
    box-shadow: 0 0.75rem 2rem rgba(0,0,0,.35);
  }

  /* Ajuste del botón rojo para que no se “pegue” a la esquina redondeada */
  #imgModal .img-modal-close{
    margin: .75rem;        /* ya tienes m-3, puedes dejarlo o usar este ajuste fino */
  }

</style>

<script>
(function(){
  const form   = document.getElementById('inventario-form');
  const q      = form.querySelector('input[name="q"]');
  const clase  = form.querySelector('select[name="clase_id"]');
  let t;

  // Auto-submit con debounce al escribir
  q.addEventListener('input', function(){
    clearTimeout(t);
    t = setTimeout(function(){ form.submit(); }, 400);
  });

  // Auto-submit al cambiar la clase
  clase.addEventListener('change', function(){ form.submit(); });

  // ===== Modal de imagen con CANVAS (sin artefactos) =====
  const modalEl   = document.getElementById('imgModal');
  const zoomBox   = document.getElementById('imgZoomContainer');
  const closeBtn  = document.querySelector('#imgModal .img-modal-close');
  const canvas    = document.getElementById('imgCanvas');
  const ctx       = canvas.getContext('2d');
  const loaderImg = document.getElementById('imgPreloader');

  let bsModal = null;
  let modalShown = false;   // <- IMPORTANTE: saber cuándo ya se mostró
  let imgReady   = false;   // imagen cargada (dimensiones listas)

  function ensureModal(){
    if (!bsModal) {
      if (!window.bootstrap || !bootstrap.Modal) return null;
      bsModal = new bootstrap.Modal(modalEl);
    }
    return bsModal;
  }

  // Estado de zoom/pan
  let scale = 1, minScale = 0.9, maxScale = 4;
  let tx = 0, ty = 0;            // traslación adicional por pan
  let imgW = 0, imgH = 0;        // tamaño natural
  let baseW = 0, baseH = 0;      // tamaño ajustado a contenedor
  let dragging = false, lx = 0, ly = 0;

  // Ajustar tamaño del canvas con DPR para máxima nitidez
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
    // Fondo sólido: elimina cualquier efecto de “cuadrícula” por transparencia
    ctx.fillStyle = '#000';
    ctx.fillRect(0,0,canvas.width,canvas.height);

    // Medidas del contenedor (CSS px)
    const cw = zoomBox.clientWidth;
    const ch = zoomBox.clientHeight;

    // Centro del lienzo
    const cx = cw/2;
    const cy = ch/2;

    // Redondeo para evitar subpíxeles
    const rtx = Math.round(tx);
    const rty = Math.round(ty);
    const rscale = Math.round(scale * 100) / 100;

    // Tamaño dibujado y esquina superior izquierda
    const w = baseW * rscale;
    const h = baseH * rscale;
    const x = Math.round(cx - w/2 + rtx);
    const y = Math.round(cy - h/2 + rty);

    ctx.drawImage(loaderImg, x, y, Math.round(w), Math.round(h));

    // Cursor
    if (rscale > 1) zoomBox.classList.add('can-pan'); else zoomBox.classList.remove('can-pan');
  }

  function resetView(toScale=1){
    scale = toScale; tx = 0; ty = 0;
    resizeCanvas();
    computeBaseSize();
    draw();
  }

  function openImgModal(imgFile){
    if (!imgFile) return;

    imgReady = false;
    loaderImg.onload = function(){
      imgW = loaderImg.naturalWidth;
      imgH = loaderImg.naturalHeight;
      imgReady = true;
      // Si el modal YA está visible, ahora sí podemos calcular tamaños y dibujar
      if (modalShown) resetView(1);
    };
    loaderImg.src = '../uploads/' + imgFile;

    const m = ensureModal();
    if (m) m.show();
  }

  // Eventos del modal para asegurar que el contenedor ya tenga tamaño > 0
  modalEl.addEventListener('shown.bs.modal', () => {
    modalShown = true;
    // Ya visible: si la imagen está lista, dibujamos; si no, se dibujará al onload
    if (imgReady) resetView(1);
  });

  modalEl.addEventListener('hidden.bs.modal', () => {
    modalShown = false;
    scale = 1; tx = 0; ty = 0; imgW = 0; imgH = 0;
    ctx.clearRect(0,0,canvas.width,canvas.height);
  });

  // Cerrar (fallback, además de data-bs-dismiss)
  if (closeBtn) {
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const m = ensureModal(); if (m) m.hide();
    });
  }

  // Redibujar si cambia tamaño ventana (solo si el modal está visible)
  window.addEventListener('resize', () => { 
    if (modalShown && imgReady) resetView(scale);
  });

  // Zoom con rueda (paso 0.25 y redondeo a 2 decimales)
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
    draw();
  }, { passive:false });

  // Pan SOLO con clic izquierdo y zoom > 1
  zoomBox.addEventListener('mousedown', (e) => {
    if (!modalShown || !imgReady) return;
    if (e.button !== 0) return;    // solo botón izquierdo
    if (scale <= 1) return;        // sin zoom, no pan
    e.preventDefault();
    dragging = true;
    zoomBox.classList.add('grabbing');
    lx = e.clientX; ly = e.clientY;
  });
  zoomBox.addEventListener('mousemove', (e) => {
    if (!dragging) return;
    if ((e.buttons & 1) !== 1) {   // si suelta el botón, termina
      dragging = false; zoomBox.classList.remove('grabbing'); return;
    }
    const dx = e.clientX - lx;
    const dy = e.clientY - ly;
    lx = e.clientX; ly = e.clientY;
    tx += dx; ty += dy;
    clamp();
    draw();
  });
  ['mouseup','mouseleave'].forEach(ev => {
    zoomBox.addEventListener(ev, () => { dragging = false; zoomBox.classList.remove('grabbing'); });
  });

  // Doble clic: alterna entre 1x y 2x centrado
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
    draw();
  });

  // Abrir modal desde miniatura / fila / nombre
  document.querySelectorAll('.img-thumb[data-img]').forEach(el => {
    el.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      openImgModal(el.getAttribute('data-img'));
    });
  });
  document.querySelectorAll('#tabla-inventario tbody tr').forEach(tr => {
    const imgFile = tr.getAttribute('data-img');
    if (!imgFile) return;
    tr.addEventListener('click', (e) => {
      if (e.target.closest('.no-modal')) return;
      if (e.target.closest('a,button,form,input,select,label')) return;
      openImgModal(imgFile);
    });
    const nombreEl = tr.querySelector('.nombre');
    if (nombreEl) {
      nombreEl.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation();
        openImgModal(imgFile);
      });
    }
  });
})();
</script>
