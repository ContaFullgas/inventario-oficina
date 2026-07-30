<?php
// ==================================================
// cat_usuarios.php
// ==================================================

ob_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/ajax.php';

// ========== SEGURIDAD (solo admin) ==========
auth_check();
auth_require_admin();
$is_admin = true;

// Detectar AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$currentUserId = auth_user()['id'] ?? 0;

// Respuesta JSON propia y autocontenida — no depende de cómo se comporte
// ajax_response() en casos de error/redirect; esto GARANTIZA que el
// cliente siempre reciba JSON limpio y nunca una redirección de página.
function usr_json($ok, $message, $extra = []) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

// ================= POST =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Validación de CSRF propia (no delegamos en check_csrf() para AJAX,
  // así evitamos cualquier redirect inesperado a otra pestaña)
  if ($is_ajax) {
    $csrfOk = isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
    if (!$csrfOk) {
      usr_json(false, 'Tu sesión expiró, recarga la página e intenta de nuevo.');
    }
  } else {
    check_csrf();
  }

  // ---------- AGREGAR ----------
  if (($_POST['accion_usr'] ?? '') === 'add') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = (string)($_POST['clave'] ?? '');
    $rol     = ($_POST['rol'] ?? 'consulta') === 'admin' ? 'admin' : 'consulta';

    $errors = [];
    if ($usuario === '') $errors[] = 'El usuario es obligatorio';
    if ($clave === '')   $errors[] = 'La contraseña es obligatoria';
    if ($clave !== '' && strlen($clave) < 4) $errors[] = 'La contraseña debe tener al menos 4 caracteres';

    if ($errors) {
      if ($is_ajax) usr_json(false, implode(' ', $errors));
    } else {
      try {
        $pdo->prepare(
          "INSERT INTO usuarios (usuario, clave, rol, activo) VALUES (:u, :c, :r, 1)"
        )->execute([':u' => $usuario, ':c' => $clave, ':r' => $rol]);

        $newId = (int)$pdo->lastInsertId();

        if ($is_ajax) {
          usr_json(true, 'Usuario agregado', [
            'id' => $newId, 'usuario' => $usuario, 'rol' => $rol, 'activo' => 1,
          ]);
        }

        flash_set('ok', 'Usuario agregado');
        header('Location: ../index.php?tab=cusr#cusr', true, 303);
        exit;

      } catch (PDOException $e) {
        if ($is_ajax) usr_json(false, 'No se pudo agregar (¿usuario duplicado?)');
      }
    }
  }

  // ---------- ACTUALIZAR ----------
  if (($_POST['accion_usr'] ?? '') === 'upd') {
    $id      = (int)($_POST['id'] ?? 0);
    $usuario = trim($_POST['usuario'] ?? '');
    $clave   = (string)($_POST['clave'] ?? '');
    $rol     = ($_POST['rol'] ?? 'consulta') === 'admin' ? 'admin' : 'consulta';

    $errors = [];
    if ($id <= 0)        $errors[] = 'ID inválido';
    if ($usuario === '') $errors[] = 'El usuario es obligatorio';
    if ($clave !== '' && strlen($clave) < 4) $errors[] = 'La contraseña debe tener al menos 4 caracteres';

    if ($errors) {
      if ($is_ajax) usr_json(false, implode(' ', $errors));
    } else {
      try {
        if ($clave !== '') {
          $pdo->prepare("UPDATE usuarios SET usuario=:u, clave=:c, rol=:r WHERE id=:id")
              ->execute([':u'=>$usuario, ':c'=>$clave, ':r'=>$rol, ':id'=>$id]);
        } else {
          $pdo->prepare("UPDATE usuarios SET usuario=:u, rol=:r WHERE id=:id")
              ->execute([':u'=>$usuario, ':r'=>$rol, ':id'=>$id]);
        }

        if ($is_ajax) {
          usr_json(true, 'Usuario actualizado', [
            'id' => $id, 'usuario' => $usuario, 'rol' => $rol,
          ]);
        }

        flash_set('ok', 'Usuario actualizado');
        header('Location: ../index.php?tab=cusr#cusr', true, 303);
        exit;

      } catch (PDOException $e) {
        if ($is_ajax) usr_json(false, 'No se pudo actualizar (¿usuario duplicado?)');
      }
    }
  }

  // ---------- ACTIVAR / DESACTIVAR ----------
  if (($_POST['accion_usr'] ?? '') === 'toggle') {
    $id     = (int)($_POST['id'] ?? 0);
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

    if ($id <= 0) {
      if ($is_ajax) usr_json(false, 'ID inválido');
    } elseif ($id === $currentUserId) {
      if ($is_ajax) usr_json(false, 'No puedes desactivar tu propia cuenta.');
    } else {
      $pdo->prepare("UPDATE usuarios SET activo=:a WHERE id=:id")
          ->execute([':a' => $estado ? 1 : 0, ':id' => $id]);

      if ($is_ajax) {
        usr_json(true, $estado ? 'Usuario activado' : 'Usuario desactivado', [
          'id' => $id, 'activo' => $estado,
        ]);
      }

      flash_set('ok', $estado ? 'Usuario activado' : 'Usuario desactivado');
      header('Location: ../index.php?tab=cusr#cusr', true, 303);
      exit;
    }
  }
}

// ================= GET =================
try {
  $rows = $pdo->query(
    "SELECT * FROM usuarios ORDER BY activo DESC, usuario"
  )->fetchAll();
} catch (PDOException $e) {
  $rows = [];
  echo '<div class="alert alert-danger">Error al cargar usuarios: '.h($e->getMessage()).'</div>';
}

$csrfToken = $_SESSION['csrf'] ?? '';
?>

<style>
#inventario-form {
  background: white;
  padding: 1.5rem;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.items-table {
  margin: 0;
}

#tablaUsuarios thead,
#tablaUsuarios thead th {
  background: #2abb4f !important;
  color: white !important;
}

.items-table thead th {
  padding: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
  letter-spacing: 0.5px;
  border: none;
  white-space: nowrap;
}

.items-table tbody td {
  padding: 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f0f0f0;
}

.items-table tbody tr:last-child td {
  border-bottom: none;
}

.table-container {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.role-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.4rem 0.9rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.8rem;
}

.role-pill.admin {
  background: linear-gradient(135deg, #0d9488 0%, #0284c7 100%);
  color: white;
}

.role-pill.consulta {
  background: #f8f9fa;
  color: #495057;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 0.4rem 0.9rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.8rem;
  color: white;
}

.status-pill.activo {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
}

.status-pill.inactivo {
  background: #94a3b8;
}

/* ==========================================================================
   MODO OSCURO (usuarios)
   ========================================================================== */

body.dark-mode .table-container {
    background: var(--bg-card);
}

body.dark-mode #tablaUsuarios thead,
body.dark-mode #tablaUsuarios thead th {
    background: #2abb4f !important;
    color: white !important;
}

body.dark-mode #tablaUsuarios thead th i {
    color: white !important;
    border: none !important;
}

body.dark-mode .items-table {
    --bs-table-bg: transparent;
    --bs-table-hover-bg: rgba(243, 156, 18, 0.08);
    --bs-table-hover-color: var(--text-main);
    --bs-table-color: var(--text-main);
    --bs-table-border-color: var(--border-color);
    background-color: transparent;
    color: var(--text-main);
}

body.dark-mode .items-table tbody td {
    border-bottom-color: var(--border-color);
    color: var(--text-main);
}

body.dark-mode .role-pill.consulta {
    background: var(--bg-body);
    color: var(--text-main);
}

body.dark-mode #modalAgregarUsuario .text-dark,
body.dark-mode #modalEditarUsuario .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalAgregarUsuario .btn-light,
body.dark-mode #modalEditarUsuario .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}

body.dark-mode #modalAgregarUsuario select option,
body.dark-mode #modalEditarUsuario select option {
    background: var(--bg-card);
    color: var(--text-main);
}

body.dark-mode #modalConfirmToggle .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalConfirmToggle .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}
</style>

<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn rounded-pill px-4 shadow-sm d-flex align-items-center gap-2 text-white"
    style="background-color: #2563eb; border: none;"
    data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario">
    <i class="bi bi-person-plus-fill"></i> Agregar
  </button>
</div>

<?php if ($flash = flash_get('ok')): ?>
  <div class="alert alert-info"><?=h($flash)?></div>
<?php endif; ?>

<div class="table-container">
  <div class="table-responsive">
    <table class="items-table table table-hover" id="tablaUsuarios">
      <thead style="background:#2abb4f !important;">
        <tr>
          <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-at"></i> USUARIO</th>
          <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-shield-fill"></i> ROL</th>
          <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-toggle-on"></i> ESTADO</th>
          <th style="background:#2abb4f !important; color:#fff !important;"><i class="bi bi-gear"></i> ACCIONES</th>
        </tr>
      </thead>
      <tbody id="tbodyUsuarios">
        <?php foreach($rows as $r): ?>
        <tr data-user-row="<?=$r['id']?>">
          <td class="cell-usuario"><?=h($r['usuario'])?></td>
          <td class="cell-rol">
            <span class="role-pill <?=$r['rol']==='admin'?'admin':'consulta'?>">
              <i class="bi bi-<?=$r['rol']==='admin'?'shield-fill-check':'eye-fill'?>"></i>
              <?=$r['rol']==='admin'?'Admin':'Consulta'?>
            </span>
          </td>
          <td class="cell-estado">
            <span class="status-pill <?=$r['activo']?'activo':'inactivo'?>">
              <i class="bi bi-<?=$r['activo']?'check-circle-fill':'pause-circle-fill'?>"></i>
              <?=$r['activo']?'Activo':'Inactivo'?>
            </span>
          </td>
          <td class="cell-acciones">
            <div class="btn-action-group">
              <button type="button" class="btn-action btn-action-edit btn-edit-usuario" title="Editar"
                data-id="<?=$r['id']?>" data-usuario="<?=h($r['usuario'])?>" data-rol="<?=h($r['rol'])?>">
                <i class="bi bi-pencil-square"></i>
              </button>
              <?php if ((int)$r['id'] !== $currentUserId): ?>
                <?php if ($r['activo']): ?>
                  <button type="button" class="btn-action btn-action-view btn-toggle-usuario" title="Desactivar"
                    data-id="<?=$r['id']?>" data-usuario="<?=h($r['usuario'])?>" data-accion="desactivar">
                    <i class="bi bi-pause-circle-fill"></i>
                  </button>
                <?php else: ?>
                  <button type="button" class="btn-action btn-action-edit btn-toggle-usuario" title="Activar"
                    data-id="<?=$r['id']?>" data-usuario="<?=h($r['usuario'])?>" data-accion="activar">
                    <i class="bi bi-play-circle-fill"></i>
                  </button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="usuariosEmptyState" class="text-center py-5" style="display: <?=empty($rows)?'block':'none'?>;">
      <div style="font-size: 3rem; color: #f39c12; margin-bottom: 1rem;"><i class="bi bi-people"></i></div>
      <p class="text-muted mb-0">No hay usuarios registrados</p>
    </div>
  </div>
</div>

<!-- Token CSRF disponible para el JS (reutilizado en filas creadas dinámicamente) -->
<input type="hidden" id="globalCsrf" value="<?=h($csrfToken)?>">

<!-- MODAL AGREGAR USUARIO -->
<div class="modal fade" id="modalAgregarUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center"
            style="width: 45px; height: 45px; background-color: #dbeafe; color:#2563eb;">
            <i class="bi bi-person-plus-fill fs-5"></i>
          </div>
          Nuevo Usuario
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4 pt-3">
        <form id="formAgregarUsuario" method="post" action="public/cat_usuarios.php" class="row g-3">
          <?=csrf_field()?>
          <input type="hidden" name="accion_usr" value="add">

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-at text-primary"></i> Usuario
              <span class="text-danger">*</span></label>
            <input type="text" name="usuario" class="form-control custom-input" placeholder="Ej. jperez" required autocomplete="username">
          </div>

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-key-fill text-primary"></i> Contraseña
              <span class="text-danger">*</span></label>
            <input type="password" name="clave" class="form-control custom-input" placeholder="Mínimo 4 caracteres" required minlength="4" autocomplete="new-password">
          </div>

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-shield-fill text-primary"></i> Rol</label>
            <select name="rol" class="form-select custom-input">
              <option value="consulta" selected>Consulta</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div class="col-12">
            <div class="alert alert-danger d-none mb-0" id="usrAddError" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span id="usrAddErrorText"></span>
            </div>
          </div>

          <div class="col-12 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
              style="background-color: #2563eb; border: none;">
              <i class="bi bi-plus-lg"></i> Agregar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL EDITAR USUARIO -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center"
            style="width: 45px; height: 45px; background-color: #dbeafe; color:#2563eb;">
            <i class="bi bi-pencil-square fs-5"></i>
          </div>
          Editar Usuario
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4 pt-3">
        <form id="formEditarUsuario" method="post" action="public/cat_usuarios.php" class="row g-3">
          <?=csrf_field()?>
          <input type="hidden" name="accion_usr" value="upd">
          <input type="hidden" name="id" id="editUsrId" value="">

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-at text-primary"></i> Usuario
              <span class="text-danger">*</span></label>
            <input type="text" name="usuario" id="editUsrUsuario" class="form-control custom-input" required autocomplete="username">
          </div>

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-key-fill text-primary"></i> Nueva contraseña</label>
            <input type="password" name="clave" id="editUsrClave" class="form-control custom-input"
              placeholder="Dejar en blanco para no cambiarla" minlength="4" autocomplete="new-password">
          </div>

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-shield-fill text-primary"></i> Rol</label>
            <select name="rol" id="editUsrRol" class="form-select custom-input">
              <option value="consulta">Consulta</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div class="col-12">
            <div class="alert alert-danger d-none mb-0" id="usrEditError" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span id="usrEditErrorText"></span>
            </div>
          </div>

          <div class="col-12 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
              style="background-color: #2563eb; border: none;">
              <i class="bi bi-check-lg"></i> Guardar cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CONFIRMAR ACTIVAR/DESACTIVAR -->
<div class="modal fade" id="modalConfirmToggle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
      <div class="modal-body p-4 pt-4 text-center">
        <div id="toggleIconWrap" class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
          style="width: 70px; height: 70px;">
          <i id="toggleIcon" class="fs-3"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2" id="toggleModalTitle">¿Confirmar acción?</h5>
        <p class="text-muted mb-0" id="toggleModalText"></p>
        <div class="alert alert-danger d-none mt-3 mb-0" id="usrToggleError" role="alert"></div>
      </div>
      <div class="modal-footer border-top-0 justify-content-center pb-4 gap-2">
        <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="button" id="btnConfirmToggle" class="btn rounded-pill px-4 fw-semibold text-white" style="border:none;">
          Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const CSRF = document.getElementById('globalCsrf').value;
  const tbody = document.getElementById('tbodyUsuarios');
  const emptyState = document.getElementById('usuariosEmptyState');

  function hideEmptyState() {
    if (emptyState) emptyState.style.display = 'none';
  }

  // Construye el <tr> completo de un usuario (usado al Agregar)
  function buildRowHtml(u) {
    const rolAdmin = u.rol === 'admin';
    const rolPillClass = rolAdmin ? 'admin' : 'consulta';
    const rolIcon = rolAdmin ? 'shield-fill-check' : 'eye-fill';
    const rolLabel = rolAdmin ? 'Admin' : 'Consulta';

    return `
      <tr data-user-row="${u.id}">
        <td class="cell-usuario">${u.usuario}</td>
        <td class="cell-rol">
          <span class="role-pill ${rolPillClass}">
            <i class="bi bi-${rolIcon}"></i> ${rolLabel}
          </span>
        </td>
        <td class="cell-estado">
          <span class="status-pill activo">
            <i class="bi bi-check-circle-fill"></i> Activo
          </span>
        </td>
        <td class="cell-acciones">
          <div class="btn-action-group">
            <button type="button" class="btn-action btn-action-edit btn-edit-usuario" title="Editar"
              data-id="${u.id}" data-usuario="${u.usuario}" data-rol="${u.rol}">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button type="button" class="btn-action btn-action-view btn-toggle-usuario" title="Desactivar"
              data-id="${u.id}" data-usuario="${u.usuario}" data-accion="desactivar">
              <i class="bi bi-pause-circle-fill"></i>
            </button>
          </div>
        </td>
      </tr>`;
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // ---------- Modal AGREGAR ----------
  const addModalEl = document.getElementById('modalAgregarUsuario');
  const addForm = document.getElementById('formAgregarUsuario');
  const addErrBox = document.getElementById('usrAddError');
  const addErrText = document.getElementById('usrAddErrorText');

  addForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    addErrBox.classList.add('d-none');
    const data = new FormData(addForm);

    try {
      const res = await fetch(addForm.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();

      if (!json.ok) {
        addErrText.innerText = json.message || 'No se pudo agregar';
        addErrBox.classList.remove('d-none');
        return;
      }

      hideEmptyState();
      tbody.insertAdjacentHTML('afterbegin', buildRowHtml({
        id: json.id,
        usuario: escapeHtml(json.usuario),
        rol: json.rol,
      }));

      bootstrap.Modal.getInstance(addModalEl)?.hide();
      addForm.reset();

    } catch (err) {
      addErrText.innerText = 'Error de conexión al guardar';
      addErrBox.classList.remove('d-none');
    }
  });

  addModalEl.addEventListener('hidden.bs.modal', function() {
    addForm.reset();
    addErrBox.classList.add('d-none');
  });

  // ---------- Modal EDITAR ----------
  const editModalEl = document.getElementById('modalEditarUsuario');
  const editForm = document.getElementById('formEditarUsuario');
  const editErrBox = document.getElementById('usrEditError');
  const editErrText = document.getElementById('usrEditErrorText');
  let editModal = null;
  function ensureEditModal() {
    if (!editModal && window.bootstrap && bootstrap.Modal) editModal = new bootstrap.Modal(editModalEl);
    return editModal;
  }

  // ---------- Modal CONFIRMAR ACTIVAR/DESACTIVAR ----------
  const toggleModalEl = document.getElementById('modalConfirmToggle');
  const toggleIconWrap = document.getElementById('toggleIconWrap');
  const toggleIcon = document.getElementById('toggleIcon');
  const toggleTitle = document.getElementById('toggleModalTitle');
  const toggleText = document.getElementById('toggleModalText');
  const toggleErrBox = document.getElementById('usrToggleError');
  const btnConfirmToggle = document.getElementById('btnConfirmToggle');
  let toggleModal = null;
  let toggleTargetId = null;
  let toggleTargetAccion = null;
  function ensureToggleModal() {
    if (!toggleModal && window.bootstrap && bootstrap.Modal) toggleModal = new bootstrap.Modal(toggleModalEl);
    return toggleModal;
  }

  // Delegación de eventos: funciona también para filas agregadas dinámicamente
  tbody.addEventListener('click', function(e) {
    const editBtn = e.target.closest('.btn-edit-usuario');
    if (editBtn) {
      document.getElementById('editUsrId').value = editBtn.dataset.id;
      document.getElementById('editUsrUsuario').value = editBtn.dataset.usuario;
      document.getElementById('editUsrRol').value = editBtn.dataset.rol;
      document.getElementById('editUsrClave').value = '';
      editErrBox.classList.add('d-none');
      const m = ensureEditModal();
      if (m) m.show();
      return;
    }

    const toggleBtn = e.target.closest('.btn-toggle-usuario');
    if (toggleBtn) {
      toggleTargetId = toggleBtn.dataset.id;
      toggleTargetAccion = toggleBtn.dataset.accion; // 'activar' | 'desactivar'
      const usuario = toggleBtn.dataset.usuario;
      toggleErrBox.classList.add('d-none');

      if (toggleTargetAccion === 'desactivar') {
        toggleIconWrap.style.backgroundColor = '#fee2e2';
        toggleIcon.style.color = '#dc2626';
        toggleIcon.className = 'fs-3 bi bi-pause-circle-fill';
        toggleTitle.textContent = 'Desactivar usuario';
        toggleText.textContent = `¿Seguro que deseas desactivar a "${usuario}"? No podrá iniciar sesión mientras esté inactivo.`;
        btnConfirmToggle.style.backgroundColor = '#dc2626';
      } else {
        toggleIconWrap.style.backgroundColor = '#d1fae5';
        toggleIcon.style.color = '#059669';
        toggleIcon.className = 'fs-3 bi bi-play-circle-fill';
        toggleTitle.textContent = 'Activar usuario';
        toggleText.textContent = `¿Deseas activar a "${usuario}"? Podrá volver a iniciar sesión.`;
        btnConfirmToggle.style.backgroundColor = '#059669';
      }

      const m = ensureToggleModal();
      if (m) m.show();
    }
  });

  editForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    editErrBox.classList.add('d-none');
    const data = new FormData(editForm);

    try {
      const res = await fetch(editForm.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();

      if (!json.ok) {
        editErrText.innerText = json.message || 'No se pudo actualizar';
        editErrBox.classList.remove('d-none');
        return;
      }

      // Actualiza la fila existente en el DOM, sin recargar ni navegar
      const row = tbody.querySelector(`tr[data-user-row="${json.id}"]`);
      if (row) {
        row.querySelector('.cell-usuario').textContent = json.usuario;

        const rolAdmin = json.rol === 'admin';
        const rolPill = row.querySelector('.cell-rol .role-pill');
        rolPill.className = 'role-pill ' + (rolAdmin ? 'admin' : 'consulta');
        rolPill.innerHTML = `<i class="bi bi-${rolAdmin ? 'shield-fill-check' : 'eye-fill'}"></i> ${rolAdmin ? 'Admin' : 'Consulta'}`;

        const editBtn = row.querySelector('.btn-edit-usuario');
        if (editBtn) {
          editBtn.dataset.usuario = json.usuario;
          editBtn.dataset.rol = json.rol;
        }
        const toggleBtn = row.querySelector('.btn-toggle-usuario');
        if (toggleBtn) toggleBtn.dataset.usuario = json.usuario;
      }

      ensureEditModal()?.hide();

    } catch (err) {
      editErrText.innerText = 'Error de conexión al guardar';
      editErrBox.classList.remove('d-none');
    }
  });

  btnConfirmToggle.addEventListener('click', async function() {
    if (!toggleTargetId) return;
    toggleErrBox.classList.add('d-none');

    const data = new FormData();
    data.append('accion_usr', 'toggle');
    data.append('id', toggleTargetId);
    data.append('estado', toggleTargetAccion === 'activar' ? 1 : 0);
    data.append('csrf', CSRF);

    try {
      const res = await fetch('public/cat_usuarios.php', { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json();

      if (!json.ok) {
        toggleErrBox.textContent = json.message || 'No se pudo completar la acción';
        toggleErrBox.classList.remove('d-none');
        return;
      }

      const row = tbody.querySelector(`tr[data-user-row="${json.id}"]`);
      if (row) {
        const estadoPill = row.querySelector('.cell-estado .status-pill');
        const actionsCell = row.querySelector('.cell-acciones .btn-action-group');
        const usuario = row.querySelector('.btn-toggle-usuario')?.dataset.usuario || '';

        if (json.activo) {
          estadoPill.className = 'status-pill activo';
          estadoPill.innerHTML = '<i class="bi bi-check-circle-fill"></i> Activo';
          const oldBtn = actionsCell.querySelector('.btn-toggle-usuario');
          if (oldBtn) {
            oldBtn.className = 'btn-action btn-action-view btn-toggle-usuario';
            oldBtn.title = 'Desactivar';
            oldBtn.dataset.accion = 'desactivar';
            oldBtn.innerHTML = '<i class="bi bi-pause-circle-fill"></i>';
          }
        } else {
          estadoPill.className = 'status-pill inactivo';
          estadoPill.innerHTML = '<i class="bi bi-pause-circle-fill"></i> Inactivo';
          const oldBtn = actionsCell.querySelector('.btn-toggle-usuario');
          if (oldBtn) {
            oldBtn.className = 'btn-action btn-action-edit btn-toggle-usuario';
            oldBtn.title = 'Activar';
            oldBtn.dataset.accion = 'activar';
            oldBtn.innerHTML = '<i class="bi bi-play-circle-fill"></i>';
          }
        }
      }

      ensureToggleModal()?.hide();

    } catch (err) {
      toggleErrBox.textContent = 'Error de conexión';
      toggleErrBox.classList.remove('d-none');
    }
  });
})();
</script>