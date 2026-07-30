<?php
ob_start();

require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/ajax.php';

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

$errors  = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// ===== POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  check_csrf();

  // ADD
  if (($_POST['accion_clase'] ?? '') === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if ($errors && $is_ajax) ajax_response(false, implode(' ', $errors));

    if (!$errors) {
      try {
        $pdo->prepare(
          "INSERT INTO cat_clases (nombre) VALUES (:n)"
        )->execute([':n'=>$nombre]);

        if ($is_ajax) ajax_response(true,'Clase agregada');

        flash_set('ok','Clase agregada');
        header('Location: ../index.php?tab=cclase#cclase', true, 303);
        exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  // UPD
  if (($_POST['accion_clase'] ?? '') === 'upd') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    if ($id <= 0) $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $pdo->prepare(
          "UPDATE cat_clases SET nombre=:n WHERE id=:id"
        )->execute([':n'=>$nombre, ':id'=>$id]);

        if ($is_ajax) ajax_response(true,'Clase actualizada');

        flash_set('ok','Clase actualizada');
        header('Location: ../index.php?tab=cclase#cclase', true, 303);
        exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }

    $edit_id = $id;
  }

  // DEL
  if (($_POST['accion_clase'] ?? '') === 'del') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
      if ($is_ajax) ajax_response(false,'ID inválido');
      flash_set('ok','ID inválido');
    }

    try {
      $pdo->prepare(
        "DELETE FROM cat_clases WHERE id=:id"
      )->execute([':id'=>$id]);

      if ($is_ajax) ajax_response(true,'Clase eliminada');

      flash_set('ok','Clase eliminada');

    } catch (PDOException $e) {
      if ($is_ajax) ajax_response(false,'No se puede eliminar: está en uso por productos.');
      flash_set('ok','No se puede eliminar: está en uso por productos.');
    }

    header('Location: ../index.php?tab=cclase#cclase', true, 303);
    exit;
  }
}

$rows = $pdo->query(
  "SELECT * FROM cat_clases ORDER BY nombre"
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
/* Prevenir conflictos entre modales */
.modal {
  z-index: 1055 !important;
}

.modal-backdrop {
  z-index: 1050 !important;
}

#imgModal {
  z-index: 1055 !important;
}

#imgModal + .modal-backdrop {
  z-index: 1054 !important;
}

/* Estilos del Modal de eliminación */
#deleteModal {
  z-index: 9999 !important;
}

#deleteModal + .modal-backdrop {
  z-index: 9998 !important;
}

#deleteModal .modal-dialog {
  z-index: 10000 !important;
}

#deleteModal .modal-content {
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  border: none;
  background: white;
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

@keyframes pulseDelete {
  0%, 100% {
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

#deleteModal .btn::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}

#deleteModal .btn:hover::before {
  width: 300px;
  height: 300px;
}

#deleteModal .btn i {
  font-size: 1.2rem;
  position: relative;
  z-index: 1;
}

#deleteModal .btn span {
  position: relative;
  z-index: 1;
}

.btn-cancel {
  background: linear-gradient(135deg, #64748b 0%, #475569 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(100, 116, 139, 0.3);
}

.btn-cancel:hover {
  background: linear-gradient(135deg, #475569 0%, #334155 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
}

.btn-delete {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
}

.btn-delete:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
}

#deleteModal.show .modal-dialog {
  animation: modalSlideDown 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
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
   MODO OSCURO (clases)
   ========================================================================== */

/* Input de edición en línea (fila "editar") */
body.dark-mode .table-container .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode .table-container .text-muted {
    color: var(--text-muted) !important;
}

/* Modal "Nueva Clase" */
body.dark-mode #modalAgregarClase .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalAgregarClase .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}

</style>

<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
    style="background-color: var(--primary); border: none;"
    data-bs-toggle="modal" data-bs-target="#modalAgregarClase">
    <i class="bi bi-plus-lg"></i> Agregar Clase
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
          <th><i class="bi bi-tag"></i> CLASE</th>
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
                <form method="post" class="d-flex align-items-center gap-2 flex-wrap" action="public/cat_clases.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_clase" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus flex-grow-1"
                    style="border-color: var(--border-color) !important; transition: all 0.2s; min-width: 200px;">
                    <i class="bi bi-tag-fill text-muted"></i>
                    <input name="nombre" class="form-control border-0 bg-transparent shadow-none ms-2 text-dark"
                      required value="<?=h($r['nombre'])?>">
                  </div>
              </td>
              <td class="text-center">
                <div class="d-flex gap-2 justify-content-center">
                  <button class="btn btn-primary rounded-pill btn-sm px-3" style="background-color: var(--primary); border:none;">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <a class="btn btn-light border rounded-pill btn-sm px-3 text-muted" href="index.php?tab=cclase#cclase">
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
                  <a class="btn-action btn-action-edit" href="index.php?tab=cclase&edit=<?=$r['id']?>#cclase" title="Editar">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                  
                  <form action="public/cat_clases.php" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="accion_clase" value="del">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
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

<!-- MODAL AGREGAR CLASE -->
<div class="modal fade" id="modalAgregarClase" tabindex="-1" aria-labelledby="modalAgregarClaseLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

      <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3" id="modalAgregarClaseLabel">
          <div class="text-primary rounded-circle d-flex align-items-center justify-content-center"
            style="width: 45px; height: 45px; background-color: var(--primary-light);">
            <i class="bi bi-tag-fill fs-5"></i>
          </div>
          Nueva Clase
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4 pt-3">
        <form id="formAgregarClase" method="post" action="public/cat_clases.php" class="row g-3">
          <?=csrf_field()?>
          <input type="hidden" name="accion_clase" value="add">

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-tag-fill text-primary"></i> Nombre de la clase
              <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control custom-input"
              placeholder="Ej. Herramientas eléctricas" required>
          </div>

          <div class="col-12">
            <div class="alert alert-danger d-none mb-0" id="claseAddError" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span id="claseAddErrorText"></span>
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
  const modalEl = document.getElementById('modalAgregarClase');
  const form = document.getElementById('formAgregarClase');
  const errBox = document.getElementById('claseAddError');
  const errText = document.getElementById('claseAddErrorText');

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