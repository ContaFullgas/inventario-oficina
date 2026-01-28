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

<style>
/* ======================= */
/*   FILTROS Y BÚSQUEDA   */
/* ======================= */

#movFilterForm {
  background: white;
  padding: 1.5rem;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  margin-bottom: 2rem;
}

#movFilterForm .input-group-text {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  border: none;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(243,156,18,0.3);
}

#movFilterForm .form-control,
#movFilterForm .form-select {
  border: 2px solid #f8f9fa;
  border-radius: 10px;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

#movFilterForm .form-control:focus,
#movFilterForm .form-select:focus {
  border-color: #f39c12;
  box-shadow: 0 0 0 0.2rem rgba(243,156,18,0.15);
}

#movFilterForm .btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
}

/* ======================= */
/*         TABLA          */
/* ======================= */

.table-container {
  background: white;
  border-radius: 15px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  overflow: hidden;
}

.table {
  margin-bottom: 0;
}

.table thead {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.table thead th {
  border: none;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  padding: 1rem;
}

.table thead th i {
  margin-right: 0.5rem;
  opacity: 0.9;
}

.table tbody tr {
  transition: all 0.2s ease;
  border-bottom: 1px solid #f0f0f0;
}

.table tbody tr:hover {
  background-color: #f8f9ff;
  transform: scale(1.01);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.table tbody td {
  padding: 1rem;
  vertical-align: middle;
}

/* ======================= */
/*        BADGES          */
/* ======================= */

.badge-custom {
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  min-width: 130px;  /* Añade esto */
  justify-content: center;  /* Añade esto */
}

.badge-custom i {
  font-size: 1rem;
}

.badge-ok {
  background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
  color: white;
}

.badge-reponer {
  background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
  color: white;
}

/* ======================= */
/*      PAGINACIÓN        */
/* ======================= */

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding: 1.5rem;
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  margin-top: 2rem;
}

.pagination-info {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex-wrap: wrap;
}

.results-count {
  font-weight: 600;
  color: #495057;
  font-size: 0.95rem;
  background: linear-gradient(135deg, #f0f0ff 0%, #e8e8ff 100%);
  padding: 0.5rem 1rem;
  border-radius: 10px;
  color: #667eea;
}

.per-page-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.per-page-selector label {
  margin: 0;
  font-weight: 500;
  color: #6c757d;
  font-size: 0.9rem;
}

.per-page-selector select {
  padding: 0.4rem 0.8rem;
  border: 2px solid #f8f9fa;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  background: white;
  color: #2c3e50;
}

.per-page-selector select:hover {
  border-color: #667eea;
}

.per-page-selector select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

.pagination-controls {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #f8f9fa;
  background: white;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #495057;
  min-width: 45px;
}

.pagination-btn:hover {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.pagination-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* ======================= */
/*    ESTADOS VACÍOS      */
/* ======================= */

.table tbody td.text-center.py-5 {
  padding: 3rem 1rem !important;
}

.table tbody td .spinner-border {
  width: 3rem;
  height: 3rem;
  margin-bottom: 1rem;
}

.table tbody td i.bi-inbox {
  font-size: 3rem;
  color: #667eea;
  margin-bottom: 1rem;
}

/* ======================= */
/*      RESPONSIVE        */
/* ======================= */

@media (max-width: 768px) {
  #movFilterForm {
    padding: 1rem;
  }

  .pagination-wrapper {
    flex-direction: column;
    align-items: stretch;
  }
  
  .pagination-info {
    flex-direction: column;
    gap: 1rem;
  }
  
  .pagination-controls {
    justify-content: center;
  }
  
  .table-container {
    overflow-x: auto;
  }

  .table tbody td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }
}
</style>

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
  // Determinar icono según el tipo
  const tipoIcon = m.tipo === 'ENTRADA' 
    ? '<i class="bi bi-arrow-down-circle-fill"></i>' 
    : '<i class="bi bi-arrow-up-circle-fill"></i>';
    
  body.insertAdjacentHTML('beforeend', `
    <tr>
      <td>${new Date(m.created_at).toLocaleString()}</td>
      <td>${m.item}</td>
      <td>
        <span class="badge-custom ${m.tipo==='ENTRADA'?'badge-ok':'badge-reponer'}">
          ${tipoIcon}
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
