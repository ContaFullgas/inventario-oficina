<?php
// ==================================================
// cat_condiciones.php
// ==================================================

ob_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/ajax.php';

// ========== SEGURIDAD ==========
auth_check();
$is_admin = auth_is_admin();

// Detectar AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Bloquear POST si no es admin
if (!$is_admin && $_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($is_ajax) ajax_response(false,'Sin permisos');
  http_response_code(403);
  exit('Sin permisos');
}

// ===============================================

$errors  = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// ================= POST =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  check_csrf();

  // ---------- AGREGAR ----------
  if (($_POST['accion_cond'] ?? '') === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if ($errors && $is_ajax) ajax_response(false, implode(' ', $errors));

    if (!$errors) {
      try {
        $pdo->prepare(
          "INSERT INTO cat_condiciones (nombre) VALUES (:n)"
        )->execute([':n'=>$nombre]);

        if ($is_ajax) ajax_response(true,'Condición/Estado agregado');

        flash_set('ok','Condición/Estado agregado');
        header('Location: ../index.php?tab=ccond#ccond', true, 303);
        exit;

      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  // ---------- ACTUALIZAR ----------
  if (($_POST['accion_cond'] ?? '') === 'upd') {
    $id     = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    if ($id <= 0)       $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $pdo->prepare(
          "UPDATE cat_condiciones SET nombre=:n WHERE id=:id"
        )->execute([
          ':n'=>$nombre,
          ':id'=>$id
        ]);

        if ($is_ajax) ajax_response(true,'Condición/Estado actualizado');

        flash_set('ok','Condición/Estado actualizado');
        header('Location: ../index.php?tab=ccond#ccond', true, 303);
        exit;

      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }

    $edit_id = $id;
  }

  // ---------- ELIMINAR ----------
  if (($_POST['accion_cond'] ?? '') === 'del') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
      if ($is_ajax) ajax_response(false,'ID inválido');
      flash_set('ok','ID inválido');
    }

    try {
      $pdo->prepare(
        "DELETE FROM cat_condiciones WHERE id=:id"
      )->execute([':id'=>$id]);

      if ($is_ajax) ajax_response(true,'Condición/Estado eliminado');

      flash_set('ok','Condición/Estado eliminado');

    } catch (PDOException $e) {

      if ($is_ajax) {
        ajax_response(false,'No se puede eliminar: está en uso por productos.');
      }

      flash_set('ok','No se puede eliminar: está en uso por productos.');
    }

    header('Location: ../index.php?tab=ccond#ccond', true, 303);
    exit;
  }
}

// ================= GET =================
$rows = $pdo->query(
  "SELECT * FROM cat_condiciones ORDER BY nombre"
)->fetchAll();
?>


<style>
/* Formulario de búsqueda mejorado */
#inventario-form {
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

#inventario-form .input-group-text {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  border: none;
  color: white;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(243,156,18,0.3);
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
  box-shadow: 0 0 0 0.2rem rgba(243,156,18,0.15);
}

#inventario-form .btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
}

#inventario-form .btn-success {
  background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
  box-shadow: 0 4px 12px rgba(39,174,96,0.3);
}

#inventario-form .btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(39,174,96,0.4);
}

/* Modal de eliminación con el mismo diseño */
#deleteModal .modal-content {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 1rem 3rem rgba(0,0,0,0.3);
  border: none;
}

#deleteModal .modal-header {
  background: linear-gradient(135deg, #fef9e7 0%, #fcf3cf 100%);
  padding: 1.5rem;
}

#deleteModal .modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
}

#deleteModal .modal-body {
  padding: 2rem 1.5rem;
  background: white;
}

#deleteModal .modal-footer {
  background: #f8f9fa;
  padding: 1.5rem;
}

/* Ícono de advertencia animado */
.delete-icon-wrapper {
  width: 80px;
  height: 80px;
  margin: 0 auto 1rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
  animation: pulseDelete 2s ease-in-out infinite;
}

.delete-icon-wrapper i {
  font-size: 2.5rem;
  color: white;
}

@keyframes pulseDelete {
  0%, 100% {
    transform: scale(1);
    box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 12px 28px rgba(231, 76, 60, 0.4);
  }
}

/* Botones del modal */
#deleteModal .btn {
  border-radius: 10px;
  padding: 0.75rem 2rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

#deleteModal .btn-cancel {
  background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
}

#deleteModal .btn-cancel:hover {
  background: linear-gradient(135deg, #7f8c8d 0%, #6c7a7b 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(149, 165, 166, 0.4);
}

#deleteModal .btn-delete {
  background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

#deleteModal .btn-delete:hover {
  background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(231, 76, 60, 0.4);
}

/* Texto del item a eliminar */
#deleteItemName {
  font-size: 1.15rem;
  color: #2c3e50;
}

/* Animación de entrada del modal */
#deleteModal.show .modal-dialog {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    transform: translateY(-50px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.items-table {
  margin: 0;
}

.items-table thead {
  background: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

.items-table thead th {
  padding: 1rem;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  color: #6c757d;
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

/* Tabla mejorada */
.table-container {
  background: white;
  border-radius: 15px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

#tabla-inventario {
  margin-bottom: 0;
}

#tabla-inventario thead {
  background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
  color: white;
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

#tabla-inventario tbody tr:hover {
  background: linear-gradient(90deg, #fef9e7 0%, #fcf3cf 100%);
  transform: scale(1.01);
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

#tabla-inventario tbody td {
  padding: 1rem;
  vertical-align: middle;
  border: none;
}

/* ==========================================================================
   MODO OSCURO (condiciones)
   ========================================================================== */

/* Este archivo redefine el título del modal de eliminar con un color propio;
   forzamos legibilidad sin importar qué <style> gane la cascada */
body.dark-mode #deleteModal .modal-title {
    color: var(--text-dark) !important;
}

/* Modal "Nueva Condición/Estado" */
body.dark-mode #modalAgregarCondicion .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalAgregarCondicion .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}

</style>

<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
    style="background-color: var(--primary); border: none;"
    data-bs-toggle="modal" data-bs-target="#modalAgregarCondicion">
    <i class="bi bi-plus-lg"></i> Agregar Condición
  </button>
</div>

<?php if ($errors && $edit_id): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<div class="table-container">
  <div class="table-responsive">
    <table class="items-table table table-hover" id="tabla-inventario">
      <thead>
        <tr>
          <th><i class="bi bi-shield-check"></i> CONDICIÓN/ESTADO</th>
          <?php if ($is_admin): ?>
            <th><i class="bi bi-gear"></i> ACCIONES</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <?php if ($edit_id === (int)$r['id']): ?>
            <tr>
              <td>
                <form method="post" class="d-flex align-items-center gap-2 flex-wrap" action="public/cat_condiciones.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_cond" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus flex-grow-1"
                    style="border-color: var(--border-color) !important; transition: all 0.2s; min-width: 200px;">
                    <i class="bi bi-check-circle-fill text-muted"></i>
                    <input name="nombre" class="form-control border-0 bg-transparent shadow-none ms-2 text-dark"
                      required value="<?=h($r['nombre'])?>">
                  </div>
              </td>
              <td class="text-center">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-primary rounded-pill btn-sm px-3" style="background-color: var(--primary); border:none;">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <a class="btn btn-light border rounded-pill btn-sm px-3 text-muted" href="index.php?tab=ccond#ccond">
                      <i class="bi bi-x-lg"></i>
                    </a>
                  </div>
                </form>
              </td>
            </tr>
          <?php else: ?>
            <tr>
              <td><?=h($r['nombre'])?></td>
              <td class="text-center">
                <div class="btn-action-group">
                  <a class="btn-action btn-action-edit" href="index.php?tab=ccond&edit=<?=$r['id']?>#ccond" title="Editar">
                    <i class="bi bi-pencil-square"></i>
                  </a>

                  <form method="post" class="d-inline" action="public/cat_condiciones.php">
                    <?=csrf_field()?>
                    <input type="hidden" name="accion_cond" value="del">
                    <input type="hidden" name="id" value="<?=$r['id']?>">
                    <button type="button" class="btn-action btn-action-delete" title="Eliminar">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </form>

                </div>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
      </table>
  </div>
</div>

<!-- MODAL AGREGAR CONDICIÓN/ESTADO -->
<div class="modal fade" id="modalAgregarCondicion" tabindex="-1" aria-labelledby="modalAgregarCondicionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

      <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3" id="modalAgregarCondicionLabel">
          <div class="text-primary rounded-circle d-flex align-items-center justify-content-center"
            style="width: 45px; height: 45px; background-color: var(--primary-light);">
            <i class="bi bi-check-circle-fill fs-5"></i>
          </div>
          Nueva Condición/Estado
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4 pt-3">
        <form id="formAgregarCondicion" method="post" action="public/cat_condiciones.php" class="row g-3">
          <?=csrf_field()?>
          <input type="hidden" name="accion_cond" value="add">

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-check-circle-fill text-primary"></i> Nombre de la condición/estado
              <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control custom-input"
              placeholder="Ej. Nuevo, Usado, Dañado" required>
          </div>

          <div class="col-12">
            <div class="alert alert-danger d-none mb-0" id="condAddError" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span id="condAddErrorText"></span>
            </div>
          </div>

          <div class="col-12 pt-3 border-top mt-3 d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light border rounded-pill px-4 fw-medium text-muted"
              data-bs-dismiss="modal">
              Cancelar
            </button>
            <button type="submit"
              class="btn rounded-pill px-4 fw-semibold shadow-sm d-flex align-items-center gap-2 text-white"
              style="background-color: var(--primary); border: none; transition: transform 0.2s;"
              onmouseover="this.style.transform='translateY(-2px)'"
              onmouseout="this.style.transform='translateY(0)'">
              <i class="bi bi-plus-lg"></i> Agregar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const modalEl = document.getElementById('modalAgregarCondicion');
  const form = document.getElementById('formAgregarCondicion');
  const errBox = document.getElementById('condAddError');
  const errText = document.getElementById('condAddErrorText');

  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    errBox.classList.add('d-none');

    const data = new FormData(form);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: data,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      if (!json.ok) {
        errText.innerText = json.message || 'No se pudo agregar';
        errBox.classList.remove('d-none');
        return;
      }

      window.location.reload();

    } catch (err) {
      errText.innerText = 'Error de conexión al guardar';
      errBox.classList.remove('d-none');
    }
  });

  modalEl.addEventListener('hidden.bs.modal', function() {
    form.reset();
    errBox.classList.add('d-none');
  });
})();
</script>