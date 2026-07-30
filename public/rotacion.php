<?php
// Archivo: public/rotacion.php

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/rotacion.php';

auth_check();
$is_admin = auth_is_admin();

function rot_historial(PDO $pdo, int $limit = 15): array {
  $stmt = $pdo->prepare(
    "SELECT t.*, ur.usuario AS responsable_nombre, ua.usuario AS ayudante_nombre
     FROM rotacion_turnos t
     LEFT JOIN usuarios ur ON ur.id = t.responsable_id
     LEFT JOIN usuarios ua ON ua.id = t.ayudante_id
     ORDER BY t.id DESC LIMIT :lim"
  );
  $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

$pendiente     = rot_get_pendiente($pdo);
$historial     = rot_historial($pdo);
$hayHistorial  = count($historial) > 0;
$hoy           = new DateTime('today');

$responsableNombre = $pendiente ? rot_nombre_usuario($pdo, $pendiente['responsable_id']) : null;
$ayudanteNombre    = $pendiente ? rot_nombre_usuario($pdo, $pendiente['ayudante_id']) : null;
?>

<style>
.rotacion-wrapper {
  padding: 0;
}

.turno-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  margin-bottom: 2rem;
}

.turno-card-header {
  background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
  color: white;
  padding: 1.25rem 1.75rem;
  font-weight: 700;
  font-size: 1.05rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.turno-pill-estado {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 4px 14px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.turno-card-body {
  padding: 2rem 1.75rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 1.75rem;
}

.turno-info-label {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-muted);
  font-weight: 700;
  margin-bottom: 0.6rem;
}

.turno-persona {
  display: flex;
  align-items: center;
  gap: 12px;
}

.turno-avatar {
  width: 46px;
  height: 46px;
  min-width: 46px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
}

.turno-avatar.responsable {
  background: linear-gradient(135deg, #0d9488, #0284c7);
}

.turno-avatar.ayudante {
  background: linear-gradient(135deg, #f4d03f, #f39c12);
}

.turno-nombre {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-dark);
  line-height: 1.2;
}

.turno-fecha-valor {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-dark);
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 0.5rem;
}

.turno-fecha-valor i {
  color: var(--primary);
  font-size: 0.95rem;
}

.badge-estado-rotacion {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.85rem;
  white-space: nowrap;
  min-width: 130px;
  justify-content: center;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  color: white;
}

.badge-estado-rotacion.completado {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
}

.badge-estado-rotacion.regenerado {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
}

.turno-info-sublabel {
  font-size: 0.68rem;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: none;
  letter-spacing: 0;
  display: block;
  margin-top: 2px;
}

.badge-vencimiento {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.45rem 1.1rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.85rem;
  white-space: nowrap;
  color: white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
}

.badge-vencimiento.al-dia {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
}

.badge-vencimiento.vencido {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

.turno-card-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.turno-empty {
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  padding: 3rem 1.5rem;
  text-align: center;
  margin-bottom: 2rem;
}

/* Encabezado de tabla verde sólido siempre (mismo patrón que Inventario/Min-Max: ID + shorthand + !important) */
#tabla-rotacion thead,
#tabla-rotacion thead th {
  background: #2abb4f !important;
  color: white !important;
}

/* ==========================================================================
   MODO OSCURO (rotación)
   ========================================================================== */

body.dark-mode .turno-card,
body.dark-mode .turno-empty {
    background: var(--bg-card);
}

/* Encabezado de la tabla de historial en modo oscuro (mismo patrón: ID + shorthand + !important) */
body.dark-mode #tabla-rotacion thead,
body.dark-mode #tabla-rotacion thead th {
    background: #2abb4f !important;
    color: white !important;
}

body.dark-mode #tabla-rotacion thead th i {
    color: white !important;
}

/* Tabla de historial (reutiliza .items-table de Min/Máx, se refuerza aquí
   por si este archivo se carga de forma independiente) */
body.dark-mode .rotacion-wrapper .items-table {
    --bs-table-bg: transparent;
    --bs-table-striped-bg: transparent;
    --bs-table-hover-bg: rgba(243, 156, 18, 0.08);
    --bs-table-hover-color: var(--text-main);
    --bs-table-color: var(--text-main);
    --bs-table-border-color: var(--border-color);
    background-color: transparent;
    color: var(--text-main);
}

body.dark-mode .rotacion-wrapper .items-table tbody td {
    border-bottom-color: var(--border-color);
    color: var(--text-main);
}

body.dark-mode .rotacion-wrapper .table-container {
    background: var(--bg-card);
}

/* Botones "Deshacer" / "Regenerar" (btn-light con fondo blanco fijo de Bootstrap) */
body.dark-mode #btnRotDeshacer,
body.dark-mode #btnRotRegenerar {
    background-color: var(--bg-body) !important;
    color: var(--text-main) !important;
    border-color: var(--border-color) !important;
}

/* ==========================================================================
   MODAL DE CONFIRMACIÓN (reemplaza confirm() nativo)
   ========================================================================== */

#modalConfirmRotacion .modal-content {
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    border: none;
}

#modalConfirmRotacion .modal-body {
    padding: 2.25rem 2rem 1.5rem;
    text-align: center;
    background: white;
}

.confirm-icon-wrapper {
    width: 84px;
    height: 84px;
    margin: 0 auto 1.25rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: white;
}

.confirm-icon-wrapper.tono-completar {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    box-shadow: 0 10px 24px rgba(39, 174, 96, 0.35);
}

.confirm-icon-wrapper.tono-deshacer {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    box-shadow: 0 10px 24px rgba(243, 156, 18, 0.35);
}

.confirm-icon-wrapper.tono-error {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    box-shadow: 0 10px 24px rgba(231, 76, 60, 0.35);
}

#modalConfirmRotacion .confirm-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-dark, #0f172a);
    margin-bottom: 0.5rem;
}

#modalConfirmRotacion .confirm-text {
    color: #64748b;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

#modalConfirmRotacion .modal-footer {
    padding: 1.25rem 2rem;
    border: none;
    background: #f8fafc;
    display: flex;
    justify-content: center;
    gap: 0.75rem;
}

#modalConfirmRotacion .btn {
    border-radius: 12px;
    padding: 0.7rem 1.75rem;
    font-weight: 600;
    border: none;
    transition: all 0.25s ease;
}

#modalConfirmRotacion .btn-cancelar-confirm {
    background: #e2e8f0;
    color: #475569;
}

#modalConfirmRotacion .btn-cancelar-confirm:hover {
    background: #cbd5e1;
    transform: translateY(-2px);
}

#modalConfirmRotacion .btn-aceptar-confirm {
    color: white;
}

#modalConfirmRotacion .btn-aceptar-confirm.tono-completar {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    box-shadow: 0 4px 12px rgba(39, 174, 96, 0.35);
}

#modalConfirmRotacion .btn-aceptar-confirm.tono-deshacer {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    box-shadow: 0 4px 12px rgba(243, 156, 18, 0.35);
}

#modalConfirmRotacion .btn-aceptar-confirm:hover {
    transform: translateY(-2px);
    filter: brightness(1.05);
}

/* Modo oscuro */
body.dark-mode #modalConfirmRotacion .modal-body {
    background: var(--bg-card);
}

body.dark-mode #modalConfirmRotacion .confirm-title {
    color: var(--text-dark);
}

body.dark-mode #modalConfirmRotacion .confirm-text {
    color: var(--text-muted);
}

body.dark-mode #modalConfirmRotacion .modal-footer {
    background: var(--bg-body);
}

body.dark-mode #modalConfirmRotacion .btn-cancelar-confirm {
    background: var(--bg-card);
    color: var(--text-main);
    border: 1px solid var(--border-color);
}

body.dark-mode #modalConfirmRotacion .btn-cancelar-confirm:hover {
    background: var(--border-color);
}

</style>

<div class="rotacion-wrapper">

  <?php if (!function_exists('h')): function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); } endif; ?>

  <?php if (!$pendiente): ?>
    <div class="turno-empty">
      <div style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;">
        <i class="bi bi-people-fill"></i>
      </div>
      <p class="text-muted mb-0">
        Se necesitan al menos 2 usuarios activos (que no sean administradores) para generar la rotación.
      </p>
    </div>

  <?php else:
    $fechaInicioObj = new DateTime($pendiente['fecha_inicio']);
    $fechaLimite     = new DateTime(rot_sumar_dias_habiles($pendiente['fecha_inicio'], 15));
    $vencido         = $hoy > $fechaLimite;
    $diasRestantes   = (int)$hoy->diff($fechaLimite)->format('%r%a');
  ?>
    <div class="turno-card">
      <div class="turno-card-header">
        <i class="bi bi-arrow-repeat"></i> Turno actual de inventario
        <span class="turno-pill-estado">Pendiente de completar</span>
      </div>

      <div class="turno-card-body">
        <div>
          <div class="turno-info-label">Responsable</div>
          <div class="turno-persona">
            <div class="turno-avatar responsable"><i class="bi bi-person-fill"></i></div>
            <div class="turno-nombre"><?=h($responsableNombre ?? 'Usuario eliminado')?></div>
          </div>
        </div>

        <div>
          <div class="turno-info-label">Ayudante</div>
          <div class="turno-persona">
            <div class="turno-avatar ayudante"><i class="bi bi-person-plus-fill"></i></div>
            <div class="turno-nombre"><?=h($ayudanteNombre ?? '—')?></div>
          </div>
        </div>

        <div>
          <div class="turno-info-label">Inicio</div>
          <div class="turno-fecha-valor">
            <i class="bi bi-calendar-event"></i> <?=h($fechaInicioObj->format('d/m/Y'))?>
          </div>
        </div>

        <div>
          <div class="turno-info-label">Límite<span class="turno-info-sublabel">15 días hábiles</span></div>
          <div class="turno-fecha-valor">
            <i class="bi bi-calendar-check"></i> <?=h($fechaLimite->format('d/m/Y'))?>
          </div>
          <span class="badge-vencimiento <?=$vencido ? 'vencido' : 'al-dia'?>">
            <?php if ($vencido): ?>
              <i class="bi bi-exclamation-circle-fill"></i> Vencido
            <?php else: ?>
              <i class="bi bi-clock-fill"></i> <?=$diasRestantes?> <?=$diasRestantes === 1 ? 'día rest.' : 'días rest.'?>
            <?php endif; ?>
          </span>
        </div>
      </div>

      <?php if ($is_admin): ?>
      <div class="turno-card-footer">
        <button type="button" class="btn btn-light border rounded-pill px-4 py-2 fw-medium text-muted d-inline-flex align-items-center gap-2"
          id="btnRotDeshacer" <?=$hayHistorial ? '' : 'disabled'?> title="Deshacer el último turno completado">
          <i class="bi bi-arrow-counterclockwise"></i> Deshacer
        </button>
        <button type="button" class="btn btn-light border rounded-pill px-4 py-2 fw-medium text-muted d-inline-flex align-items-center gap-2"
          id="btnRotRegenerar" title="Proponer otra pareja">
          <i class="bi bi-shuffle"></i> Regenerar
        </button>
        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm d-inline-flex align-items-center gap-2"
          id="btnRotCompletar" style="background-color: var(--primary); border: none;">
          <i class="bi bi-check-circle"></i> Marcar Completado
        </button>
      </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Historial -->
  <div class="table-container">
    <div class="table-responsive">
      <table class="items-table table table-hover" id="tabla-rotacion">
        <thead style="background:#2abb4f !important;">
          <tr>
            <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-person-fill"></i> Responsable</th>
            <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-person-plus-fill"></i> Ayudante</th>
            <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-calendar-event"></i> Inicio</th>
            <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-calendar-check"></i> Límite</th>
            <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-flag-fill"></i> Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$historial): ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">Sin turnos completados aún</td>
          </tr>
          <?php else: foreach ($historial as $hh): ?>
          <tr>
            <td><?=h($hh['responsable_nombre'] ?? 'Usuario eliminado')?></td>
            <td><?=h($hh['ayudante_nombre'] ?? '—')?></td>
            <td><?=h((new DateTime($hh['fecha_inicio']))->format('d/m/Y'))?></td>
            <td><?=h((new DateTime($hh['fecha_limite']))->format('d/m/Y'))?></td>
            <td>
              <?php if ($hh['estado'] === 'completado'): ?>
                <span class="badge-estado-rotacion completado"><i class="bi bi-check-circle-fill"></i> Completado</span>
              <?php else: ?>
                <span class="badge-estado-rotacion regenerado"><i class="bi bi-shuffle"></i> <?=h(ucfirst($hh['estado']))?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Token CSRF disponible para el JS de esta pestaña -->
  <?= csrf_field() ?>

  <!-- Modal de Confirmación (reemplaza confirm() nativo) -->
  <div class="modal fade" id="modalConfirmRotacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="confirm-icon-wrapper" id="confirmIconWrapper">
            <i class="bi" id="confirmIcon"></i>
          </div>
          <div class="confirm-title" id="confirmTitle"></div>
          <p class="confirm-text" id="confirmText"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-cancelar-confirm" data-bs-dismiss="modal">
            Cancelar
          </button>
          <button type="button" class="btn btn-aceptar-confirm" id="confirmAceptarBtn">
            Aceptar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Mensaje/Error (reemplaza alert() nativo) -->
  <div class="modal fade" id="modalMensajeRotacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="confirm-icon-wrapper tono-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
          </div>
          <div class="confirm-title" id="mensajeTitle">Ocurrió un problema</div>
          <p class="confirm-text" id="mensajeText"></p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
          <button type="button" class="btn btn-aceptar-confirm tono-error" data-bs-dismiss="modal">
            Entendido
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const wrapper = document.querySelector('.rotacion-wrapper');
  if (!wrapper) return;

  // Red de seguridad: evita que quede un backdrop huérfano si la
  // transición de cierre del modal se interrumpe por algún motivo.
  document.addEventListener('hidden.bs.modal', function () {
      if (!document.querySelector('.modal.show')) {
          document.body.classList.remove('modal-open');
          document.body.style.removeProperty('padding-right');
          document.body.style.removeProperty('overflow');
          document.querySelectorAll('.modal-backdrop').forEach(bd => bd.remove());
      }
  });

  function mostrarMensaje(texto) {
    const msgModalEl = document.getElementById('modalMensajeRotacion');
    document.getElementById('mensajeText').textContent = texto;
    bootstrap.Modal.getOrCreateInstance(msgModalEl).show();
  }

  async function rotAccion(accion, boton) {
    const csrfInput = wrapper.querySelector('input[name="csrf"]') ||
                       document.querySelector('input[name="csrf"]');

    const data = new FormData();
    data.append('accion', accion);
    if (csrfInput) data.append('csrf', csrfInput.value);

    if (boton) boton.disabled = true;

    try {
      const res = await fetch('public/ajax/rotacion_accion.php', {
        method: 'POST',
        body: data
      });
      const json = await res.json();

      if (!json.ok) {
        mostrarMensaje(json.message || 'No se pudo completar la acción');
        if (boton) boton.disabled = false;
        return;
      }

      window.location.reload();

    } catch (err) {
      mostrarMensaje('Error de conexión. Intenta de nuevo.');
      if (boton) boton.disabled = false;
    }
  }

  // ===== Modal de confirmación (reemplaza confirm() nativo) =====
  const confirmModalEl = document.getElementById('modalConfirmRotacion');
  const confirmIconWrap = document.getElementById('confirmIconWrapper');
  const confirmIcon     = document.getElementById('confirmIcon');
  const confirmTitleEl  = document.getElementById('confirmTitle');
  const confirmTextEl   = document.getElementById('confirmText');
  const confirmBtn      = document.getElementById('confirmAceptarBtn');

  let confirmCallback = null;

  function abrirConfirmacion({ tono, icono, titulo, texto, onAceptar }) {
    confirmIconWrap.className = `confirm-icon-wrapper tono-${tono}`;
    confirmIcon.className = `bi ${icono}`;
    confirmBtn.className = `btn btn-aceptar-confirm tono-${tono}`;
    confirmTitleEl.textContent = titulo;
    confirmTextEl.textContent = texto;
    confirmCallback = onAceptar;
    if (confirmModalEl) bootstrap.Modal.getOrCreateInstance(confirmModalEl).show();
  }

  confirmBtn?.addEventListener('click', () => {
    if (confirmModalEl) bootstrap.Modal.getOrCreateInstance(confirmModalEl).hide();
    if (typeof confirmCallback === 'function') confirmCallback();
    confirmCallback = null;
  });

  // Regenerar: acción instantánea, sin confirmación ni guardado
  document.getElementById('btnRotRegenerar')?.addEventListener('click', function() {
    rotAccion('regenerar', this);
  });

  document.getElementById('btnRotCompletar')?.addEventListener('click', function() {
    const boton = this;
    abrirConfirmacion({
      tono: 'completar',
      icono: 'bi-check-circle-fill',
      titulo: '¿Marcar como completado?',
      texto: 'Se guardará el turno actual y se pasará el inventario a la siguiente pareja.',
      onAceptar: () => rotAccion('completar', boton)
    });
  });

  document.getElementById('btnRotDeshacer')?.addEventListener('click', function() {
    const boton = this;
    abrirConfirmacion({
      tono: 'deshacer',
      icono: 'bi-arrow-counterclockwise',
      titulo: '¿Deshacer el último turno?',
      texto: 'Esta acción revertirá el último turno completado y no se puede deshacer.',
      onAceptar: () => rotAccion('deshacer', boton)
    });
  });
})();
</script>