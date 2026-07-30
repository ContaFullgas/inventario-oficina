<?php
// Archivo: movimientos.php (SOLO VISTA + AJAX)

require_once __DIR__.'/../config/util.php';

auth_check();
?>

<style>
/* ==========================================================================
   1. CONTENEDORES Y BÚSQUEDA
   ========================================================================== */
.movimientos-wrapper {
    padding: 0;
}

#movFilterForm {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 2rem;
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
#movFilterForm .btn {
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

/* ==========================================================================
   3. TABLA E ITEMS
   ========================================================================== */
.table-container-mov {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

#tabla-movimientos {
    margin-bottom: 0;
}

/* Encabezado de tabla verde sólido siempre (Igual a Inventario) */
#tabla-movimientos thead,
#tabla-movimientos thead th {
    background-color: #27ae60 !important;
    color: white !important;
}

#tabla-movimientos thead th {
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    border: none;
    white-space: nowrap;
}

#tabla-movimientos tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f8f9fa;
}

/* Hover de tabla unificado con Inventario */
@media (hover: hover) and (pointer: fine) {
    #tabla-movimientos.table-hover > tbody > tr:hover > * {
        background-color: #d4f4dd !important;
        color: #1e293b !important;
    }
}

#tabla-movimientos tbody td {
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

/* Animación para ícono de carga */
@keyframes spin {
    100% { transform: rotate(360deg); }
}
.spin-icon {
    display: inline-block;
    animation: spin 1s linear infinite;
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
    min-width: 100px;
    justify-content: center;
    white-space: nowrap;
    text-align: center;
}

.badge-reponer {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
    min-width: 100px;
    justify-content: center;
    white-space: nowrap;
}

/* ==========================================================================
   5. PAGINACIÓN
   ========================================================================== */
.pagination-wrapper-mov {
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

.pagination-info-mov {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: #2c3e50;
    font-weight: 500;
}

.results-count-mov {
    background: linear-gradient(135deg, #fef9e7 0%, #fcf3cf 100%);
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    color: #f39c12;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.per-page-selector-mov {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.per-page-selector-mov label {
    margin: 0;
    font-size: 0.9rem;
    color: #7f8c8d;
}

.per-page-selector-mov select {
    border: 2px solid #f8f9fa;
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    font-weight: 600;
    color: #2c3e50;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.per-page-selector-mov select:focus {
    border-color: #f39c12;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(243, 156, 18, 0.15);
}

.pagination-controls-mov {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.pagination-btn-mov {
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

.pagination-btn-mov:hover {
    background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
    color: white;
    border-color: #f39c12;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.pagination-btn-mov.active {
    background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
    color: white;
    border-color: #f39c12;
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

/* ==========================================================================
   6. MODO OSCURO (movimientos)
   ========================================================================== */

body.dark-mode #movFilterForm,
body.dark-mode .table-container-mov,
body.dark-mode .pagination-wrapper-mov {
    background: var(--bg-card);
}

/* Encabezado de tabla en modo oscuro */
body.dark-mode #tabla-movimientos thead,
body.dark-mode #tabla-movimientos thead th {
    background-color: #1e8449 !important; /* Verde oscuro unificado */
    color: white !important;
}

body.dark-mode #tabla-movimientos thead th i {
    color: white !important;
}

body.dark-mode #movFilterForm #btnFiltrarMov i.bi {
    color: white !important;
}

/* El botón "Limpiar" mantiene fondo blanco fijo; necesita texto oscuro */
body.dark-mode #movFilterForm #btnLimpiarMov {
    color: #495057 !important;
}

/* Buscador y filtros (cápsulas bg-light) */
body.dark-mode #movFilterForm .capsule-focus,
body.dark-mode #movFilterForm .bg-light {
    background-color: var(--bg-body) !important;
}

body.dark-mode #movFilterForm input,
body.dark-mode #movFilterForm select,
body.dark-mode #movFilterForm .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #movFilterForm input::placeholder {
    color: var(--text-muted) !important;
    opacity: 1;
}

body.dark-mode #movFilterForm .text-muted,
body.dark-mode #movFilterForm i.bi {
    color: var(--text-muted) !important; /* Ajustado para que los iconos no queden invisibles */
}

body.dark-mode #movFilterForm select option {
    background: var(--bg-card);
    color: var(--text-main);
}

/* Anular el fondo blanco que Bootstrap pone a la tabla vía variables CSS */
body.dark-mode #tabla-movimientos {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: transparent;
    --bs-table-hover-bg: #1f4d33; /* Unificado con inventario */
    --bs-table-hover-color: #4ade80;
    --bs-table-color: var(--text-main);
    --bs-table-border-color: var(--border-color);
    background-color: transparent;
    color: var(--text-main);
}

body.dark-mode #tabla-movimientos tbody tr {
    border-bottom-color: var(--border-color);
}

@media (hover: hover) and (pointer: fine) {
    body.dark-mode #tabla-movimientos.table-hover > tbody > tr:hover > * {
        background-color: #1f4d33 !important;
        color: #4ade80 !important;
    }
}

body.dark-mode #tabla-movimientos tbody td {
    color: var(--text-main);
}

body.dark-mode #tabla-movimientos .item-nombre {
    color: var(--text-dark) !important;
}

body.dark-mode #tabla-movimientos .item-notas {
    color: var(--text-muted);
}

body.dark-mode #tabla-movimientos .stock-display {
    color: var(--text-dark) !important;
}

/* Paginación */
body.dark-mode .pagination-info-mov,
body.dark-mode .per-page-selector-mov label {
    color: var(--text-main);
}

body.dark-mode .per-page-selector-mov select {
    background: var(--bg-body);
    color: var(--text-main);
    border-color: var(--border-color);
}

body.dark-mode .per-page-selector-mov select option {
    background: var(--bg-card);
    color: var(--text-main);
}

body.dark-mode .pagination-btn-mov {
    background: var(--bg-body);
    color: var(--text-main);
    border-color: var(--border-color);
}

body.dark-mode .pagination-btn-mov:hover {
    background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
    color: white;
}

body.dark-mode .pagination-btn-mov.active {
    color: white;
}

body.dark-mode .results-count-mov {
    background: rgba(243, 156, 18, 0.12);
    color: #f39c12;
}

</style>

<div class="movimientos-wrapper">

    <!-- BÚSQUEDA Y FILTROS -->
    <form id="movFilterForm" class="m-0">
        <div class="row g-3 align-items-center mb-3">

            <!-- Búsqueda Principal (Cápsula) -->
            <div class="col-12 col-md-5 col-lg-6">
                <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus"
                    style="border-color: var(--border-color) !important; transition: all 0.2s;">
                    <i class="bi bi-search text-muted transition-colors"></i>
                    <input type="text" name="q" class="form-control border-0 bg-transparent shadow-none ms-2 text-dark"
                        placeholder="Buscar producto o motivo...">
                </div>
            </div>

            <!-- Filtro de Tipo (Cápsula) -->
            <div class="col-12 col-md-4 col-lg-3">
                <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus"
                    style="border-color: var(--border-color) !important; transition: all 0.2s;">
                    <i class="bi bi-arrow-left-right text-muted transition-colors"></i>
                    <select name="tipo" class="form-select border-0 bg-transparent shadow-none ms-1 text-muted"
                        style="cursor: pointer;">
                        <option value="">Todos los tipos</option>
                        <option value="ENTRADA">Entradas</option>
                        <option value="SALIDA">Salidas</option>
                    </select>
                </div>
            </div>

        </div>

        <!-- Botones Búsqueda -->
        <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center pt-3 border-top">
            <div class="d-flex flex-wrap gap-2">
                <button type="button"
                    class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
                    id="btnFiltrarMov" style="background-color: var(--primary); border: none;">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <button type="button"
                    class="btn btn-light border rounded-pill px-3 shadow-sm d-flex align-items-center gap-2 text-muted"
                    id="btnLimpiarMov">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
            </div>
        </div>
    </form>

    <!-- TABLA DE MOVIMIENTOS -->
    <div class="table-container-mov mt-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="tabla-movimientos">
                <thead>
                    <tr>
                        <th><i class="bi bi-clock-history me-1"></i> Fecha</th>
                        <th><i class="bi bi-box-seam me-1"></i> Producto</th>
                        <th><i class="bi bi-arrow-left-right me-1"></i> Tipo</th>
                        <th><i class="bi bi-hash me-1"></i> Cantidad</th>
                        <th><i class="bi bi-chat-left-text me-1"></i> Motivo</th>
                    </tr>
                </thead>
                <tbody id="movBody">
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;">
                                <i class="bi bi-arrow-repeat spin-icon"></i>
                            </div>
                            <p class="text-muted mb-0">Cargando movimientos...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINACIÓN -->
    <div class="pagination-wrapper-mov" id="movPaginationWrapper" style="display: none;">
        <div class="pagination-info-mov">
            <div class="results-count-mov" id="movInfo">
                <!-- Se llena con JS -->
            </div>

            <div class="per-page-selector-mov">
                <label>Por página:</label>
                <select id="perPageMov">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="pagination-controls-mov" id="movPaginationControls">
            <!-- Botones AJAX -->
        </div>
    </div>

</div>

<script>
const movState = {
    q: '',
    tipo: '',
    page: 1,
    limit: 25
};

window.loadMovimientos = async function() {
    const params = new URLSearchParams(movState);

    try {
        const res = await fetch('public/ajax/movimientos_list.php?' + params);
        const json = await res.json();

        const body = document.getElementById('movBody');
        const pagControls = document.getElementById('movPaginationControls');
        const info = document.getElementById('movInfo');
        const pagWrapper = document.getElementById('movPaginationWrapper');

        if (!body || !pagControls || !info) return;

        body.innerHTML = '';
        pagControls.innerHTML = '';
        info.innerHTML = '';

        if (!json.ok || json.data.length === 0) {
            pagWrapper.style.display = 'none';
            body.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-5">
                    <div style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <p class="text-muted mb-0">Sin movimientos registrados</p>
                </td>
            </tr>`;
            return;
        }

        pagWrapper.style.display = 'flex';

        json.data.forEach(m => {
            const isEntrada = m.tipo === 'ENTRADA';
            const tipoIcon = isEntrada ? '<i class="bi bi-arrow-down-circle-fill"></i>' :
                '<i class="bi bi-arrow-up-circle-fill"></i>';
            const badgeClass = isEntrada ? 'badge-ok' : 'badge-reponer';

            body.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="text-muted small fw-medium">${new Date(m.created_at).toLocaleString()}</td>
                <td><div class="item-nombre">${m.item}</div></td>
                <td>
                    <span class="badge-custom ${badgeClass}">
                        ${tipoIcon} ${m.tipo}
                    </span>
                </td>
                <td><span class="stock-display">${m.cantidad}</span></td>
                <td><div class="item-notas">${m.motivo || '—'}</div></td>
            </tr>
            `);
        });

        const pages = Math.ceil(json.total / movState.limit);

        info.innerHTML = `
            <i class="bi bi-list-ul"></i>
            <span>Página ${movState.page} de ${pages} — ${json.total} registros</span>
        `;

        for (let i = 1; i <= pages; i++) {
            pagControls.insertAdjacentHTML('beforeend', `
            <button class="pagination-btn-mov ${i === movState.page ? 'active' : ''}">
                ${i}
            </button>
            `);
        }

        pagControls.querySelectorAll('button').forEach((btn, idx) => {
            btn.onclick = () => {
                movState.page = idx + 1;
                loadMovimientos();
            };
        });

    } catch (error) {
        console.error("Error cargando movimientos:", error);
    }
}

// Inicializar si ya estamos en la pestaña
if (document.querySelector('.tab-link[href="#mov"]')?.classList.contains('active')) {
    loadMovimientos();
}
</script>

<script>
/* =======================
   EVENTOS DE FILTROS
======================= */

document.querySelector('#movFilterForm input[name="q"]')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
        e.preventDefault();
        movState.q = e.target.value.trim();
        movState.page = 1;
        loadMovimientos();
    }
});

document.querySelector('#movFilterForm select[name="tipo"]')?.addEventListener('change', e => {
    movState.tipo = e.target.value;
    movState.page = 1;
    loadMovimientos();
});

document.getElementById('btnFiltrarMov')?.addEventListener('click', () => {
    const form = document.getElementById('movFilterForm');
    movState.q = form.querySelector('input[name="q"]').value.trim();
    movState.tipo = form.querySelector('select[name="tipo"]').value;
    movState.page = 1;
    loadMovimientos();
});

document.getElementById('btnLimpiarMov')?.addEventListener('click', () => {
    movState.q = '';
    movState.tipo = '';
    movState.page = 1;
    document.getElementById('movFilterForm').reset();
    loadMovimientos();
});

document.getElementById('perPageMov')?.addEventListener('change', e => {
    movState.limit = parseInt(e.target.value, 10);
    movState.page = 1;
    loadMovimientos();
});
</script>


