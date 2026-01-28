<?php
// Archivo: movimientos.php (SOLO VISTA + AJAX)

require_once __DIR__.'/../config/util.php';
auth_check();
auth_require_admin();
?>

<!-- ======================= -->
<!--  ENCABEZADO / FILTROS  -->
<!-- ======================= -->

<form class="row g-3 mb-4" id="movFilterForm">

  <div class="col-md-5">
    <div class="input-group">
      <span class="input-group-text">
        <i class="bi bi-search"></i>
      </span>
      <input
        type="text"
        name="q"
        class="form-control"
        placeholder="Producto o motivo">
    </div>
  </div>

  <div class="col-md-2">
    <button type="button" class="btn btn-primary w-100" id="btnFiltrarMov">
      <i class="bi bi-search"></i> Buscar
    </button>
  </div>

  <div class="col-md-3">
    <select name="tipo" class="form-select">
      <option value="">Todos los tipos</option>
      <option value="ENTRADA">Entrada</option>
      <option value="SALIDA">Salida</option>
    </select>
  </div>

  <div class="col-md-2">
    <button type="button" class="btn btn-success w-100" id="btnLimpiarMov">
      <i class="bi bi-arrow-clockwise"></i> Limpiar
    </button>
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
          <!-- <th><i class="bi bi-person"></i> Usuario</th> -->
        </tr>
      </thead>

      <tbody id="movBody">
        <tr>
          <td colspan="5" class="text-center py-5 text-muted">
            <div class="spinner-border text-warning"></div>
            <div>Cargando movimientos…</div>
          </td>
        </tr>
      </tbody>

    </table>
  </div>
</div>

<!-- ======================= -->
<!--      PAGINACIÓN        -->
<!-- ======================= -->

<div class="pagination-wrapper mt-4">
  <div class="pagination-info">
    <div class="results-count" id="movInfo"></div>

    <div class="per-page-selector">
      <label>Por página:</label>
      <select id="perPageMov">
        <option value="10">10</option>
        <option value="25" selected>25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
  </div>

  <div class="pagination-controls" id="movPagination"></div>
</div>

<!-- ======================= -->
<!--        AJAX            -->
<!-- ======================= -->

<script>
const movState = {
  q: '',
  tipo: '',
  page: 1,
  limit: 25
};

window.loadMovimientos = async function() {
  const params = new URLSearchParams(movState);

  const res = await fetch('public/ajax/movimientos_list.php?' + params);
  const json = await res.json();

  const body = document.getElementById('movBody');
  const pag  = document.getElementById('movPagination');
  const info = document.getElementById('movInfo');

  body.innerHTML = '';
  pag.innerHTML  = '';
  info.innerHTML = '';

  if (!json.ok || json.data.length === 0) {
    body.innerHTML = `
      <tr>
        <td colspan="5" class="text-center py-5 text-muted">
          <i class="bi bi-inbox fs-1 d-block mb-2"></i>
          Sin movimientos registrados
        </td>
      </tr>`;
    return;
  }

  json.data.forEach(m => {
    body.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${new Date(m.created_at).toLocaleString()}</td>
        <td>${m.item}</td>
        <td>
          <span class="badge-custom ${m.tipo==='ENTRADA'?'badge-ok':'badge-reponer'}">
            ${m.tipo}
          </span>
        </td>
        <td class="fw-bold">${m.cantidad}</td>
        <td>${m.motivo || '—'}</td>
        
      </tr>
    `);
  });

  const pages = Math.ceil(json.total / movState.limit);
  info.textContent = `Página ${movState.page} de ${pages} — ${json.total} registros`;

  for (let i = 1; i <= pages; i++) {
    pag.insertAdjacentHTML('beforeend', `
      <button class="pagination-btn ${i===movState.page?'active':''}">
        ${i}
      </button>
    `);
  }

  pag.querySelectorAll('button').forEach((btn, idx) => {
    btn.onclick = () => {
      movState.page = idx + 1;
      loadMovimientos();
    };
  });
}

loadMovimientos();
</script>

<script>
/* =======================
   EVENTOS DE FILTROS
======================= */

// Buscar por texto (en tiempo real)
// document.querySelector('#movFilterForm input[name="q"]')
//   .addEventListener('input', e => {
//     movState.q = e.target.value;
//     movState.page = 1;
//     loadMovimientos();
//   });

// Filtrar por tipo
document.querySelector('#movFilterForm select[name="tipo"]')
  .addEventListener('change', e => {
    movState.tipo = e.target.value;
    movState.page = 1;
    loadMovimientos();
  });

// Botón Filtrar (por si quieres usarlo manual)
document.getElementById('btnFiltrarMov')
  .addEventListener('click', () => {
    movState.page = 1;
    loadMovimientos();
  });

// Botón Limpiar
document.getElementById('btnLimpiarMov')
  .addEventListener('click', () => {
    movState.q = '';
    movState.tipo = '';
    movState.page = 1;
    document.getElementById('movFilterForm').reset();
    loadMovimientos();
  });

// Cambio de registros por página
document.getElementById('perPageMov')
  .addEventListener('change', e => {
    movState.limit = parseInt(e.target.value, 10);
    movState.page = 1;
    loadMovimientos();
  });
</script>

<script>
/* =======================
   FILTRAR SOLO AL CONFIRMAR
======================= */

// Presionar ENTER en el input de búsqueda
document.querySelector('#movFilterForm input[name="q"]')
  .addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault(); // evita submit del form
      movState.q = e.target.value.trim();
      movState.page = 1;
      loadMovimientos();
    }
  });

// Cambio de tipo (puede ser inmediato o esperar botón, tú decides)
document.querySelector('#movFilterForm select[name="tipo"]')
  .addEventListener('change', e => {
    movState.tipo = e.target.value;
  });

// Botón Filtrar
document.getElementById('btnFiltrarMov')
  .addEventListener('click', () => {
    const form = document.getElementById('movFilterForm');
    movState.q    = form.querySelector('input[name="q"]').value.trim();
    movState.tipo = form.querySelector('select[name="tipo"]').value;
    movState.page = 1;
    loadMovimientos();
  });

// Botón Limpiar
document.getElementById('btnLimpiarMov')
  .addEventListener('click', () => {
    movState.q = '';
    movState.tipo = '';
    movState.page = 1;
    document.getElementById('movFilterForm').reset();
    loadMovimientos();
  });

// Cambio de registros por página
document.getElementById('perPageMov')
  .addEventListener('change', e => {
    movState.limit = parseInt(e.target.value, 10);
    movState.page = 1;
    loadMovimientos();
  });
</script>
