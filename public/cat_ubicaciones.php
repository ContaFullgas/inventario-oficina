<?php
// ==================================================
// cat_ubicaciones.php
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
  if (($_POST['accion_ubi'] ?? '') === 'add') {

    $nombre = trim($_POST['nombre'] ?? '');

    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if ($errors && $is_ajax) ajax_response(false, implode(' ', $errors));

    if (!$errors) {
      try {
        $pdo->prepare(
          "INSERT INTO cat_ubicaciones (nombre) VALUES (:n)"
        )->execute([':n'=>$nombre]);

        if ($is_ajax) ajax_response(true,'Ubicación agregada');

        flash_set('ok','Ubicación agregada');
        header('Location: ../index.php?tab=cubi#cubi', true, 303);
        exit;

      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  // ---------- ACTUALIZAR ----------
  if (($_POST['accion_ubi'] ?? '') === 'upd') {

    $id     = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');

    if ($id <= 0)       $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $pdo->prepare(
          "UPDATE cat_ubicaciones SET nombre=:n WHERE id=:id"
        )->execute([
          ':n'=>$nombre,
          ':id'=>$id
        ]);

        if ($is_ajax) ajax_response(true,'Ubicación actualizada');

        flash_set('ok','Ubicación actualizada');
        header('Location: ../index.php?tab=cubi#cubi', true, 303);
        exit;

      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }

    $edit_id = $id;
  }

  // ---------- ELIMINAR ----------
  if (($_POST['accion_ubi'] ?? '') === 'del') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0) {
      if ($is_ajax) ajax_response(false,'ID inválido');
      flash_set('ok','ID inválido');
    }

    try {
      $pdo->prepare(
        "DELETE FROM cat_ubicaciones WHERE id=:id"
      )->execute([':id'=>$id]);

      if ($is_ajax) ajax_response(true,'Ubicación eliminada');

      flash_set('ok','Ubicación eliminada');

    } catch (PDOException $e) {

      if ($is_ajax) {
        ajax_response(false,'No se puede eliminar: está en uso por productos.');
      }

      flash_set('ok','No se puede eliminar: está en uso por productos.');
    }

    header('Location: ../index.php?tab=cubi#cubi', true, 303);
    exit;
  }
}

// ================= GET =================
$rows = $pdo->query(
  "SELECT * FROM cat_ubicaciones ORDER BY nombre"
)->fetchAll();

?>

<style>
body.dark-mode #modalAgregarUbicacion .text-dark {
    color: var(--text-main) !important;
}

body.dark-mode #modalAgregarUbicacion .btn-light {
    background-color: var(--bg-body) !important;
    border-color: var(--border-color) !important;
}
</style>

<div class="d-flex justify-content-end mb-3">
  <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm d-flex align-items-center gap-2"
    style="background-color: var(--primary); border: none;"
    data-bs-toggle="modal" data-bs-target="#modalAgregarUbicacion">
    <i class="bi bi-plus-lg"></i> Agregar Ubicación
  </button>
</div>

<?php if ($errors && $edit_id): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="table-container">
  <div class="table-responsive">
    <table id="tabla-inventario" class="items-table table table-hover">
      <thead>
        <tr>
          <th><i class="bi bi-tag"></i> UBICACIÓN</th>
          <th><i class="bi bi-gear"></i>ACCIONES</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <?php if ($edit_id === (int)$r['id']): ?>
            <tr>
              <td>
                <form method="post" class="d-flex align-items-center gap-2 flex-wrap" action="public/cat_ubicaciones.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_ubi" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="d-flex align-items-center bg-light border rounded-pill px-3 py-1 capsule-focus flex-grow-1"
                    style="border-color: var(--border-color) !important; transition: all 0.2s; min-width: 200px;">
                    <i class="bi bi-geo-alt-fill text-muted"></i>
                    <input name="nombre" class="form-control border-0 bg-transparent shadow-none ms-2 text-dark"
                      required value="<?=h($r['nombre'])?>">
                  </div>
              </td>
              <td class="text-center">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-primary rounded-pill btn-sm px-3" style="background-color: var(--primary); border:none;">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <a class="btn btn-light border rounded-pill btn-sm px-3 text-muted" href="index.php?tab=cubi#cubi">
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
                  <a class="btn-action btn-action-edit" href="index.php?tab=cubi&edit=<?=$r['id']?>#cubi" title="Editar">
                    <i class="bi bi-pencil-square"></i>
                  </a>

                  <form method="post" class="d-inline" action="public/cat_ubicaciones.php">
                    <?=csrf_field()?>
                    <input type="hidden" name="accion_ubi" value="del">
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

<!-- MODAL AGREGAR UBICACIÓN -->
<div class="modal fade" id="modalAgregarUbicacion" tabindex="-1" aria-labelledby="modalAgregarUbicacionLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">

      <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-3" id="modalAgregarUbicacionLabel">
          <div class="text-primary rounded-circle d-flex align-items-center justify-content-center"
            style="width: 45px; height: 45px; background-color: var(--primary-light);">
            <i class="bi bi-geo-alt-fill fs-5"></i>
          </div>
          Nueva Ubicación
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body p-4 pt-3">
        <form id="formAgregarUbicacion" method="post" action="public/cat_ubicaciones.php" class="row g-3">
          <?=csrf_field()?>
          <input type="hidden" name="accion_ubi" value="add">

          <div class="col-12">
            <label class="custom-form-label"><i class="bi bi-geo-alt-fill text-primary"></i> Nombre de la ubicación
              <span class="text-danger">*</span></label>
            <input type="text" name="nombre" class="form-control custom-input"
              placeholder="Ej. Almacén A, Estante 3" required>
          </div>

          <div class="col-12">
            <div class="alert alert-danger d-none mb-0" id="ubiAddError" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <span id="ubiAddErrorText"></span>
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
  const modalEl = document.getElementById('modalAgregarUbicacion');
  const form = document.getElementById('formAgregarUbicacion');
  const errBox = document.getElementById('ubiAddError');
  const errText = document.getElementById('ubiAddErrorText');

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