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

// Helper JSON
// if (!function_exists('ajax_response')) {
//   function ajax_response(bool $ok, string $message = ''): void {
//     echo json_encode(['ok'=>$ok,'message'=>$message]);
//     exit;
//   }
// }


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
  /*border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);*/
  margin-bottom: 2rem;
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

/* items tables */
.items-table-wrapper {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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
</style>

<!-- Formulario de agregar -->
<form id="inventario-form" class="row g-3 mb-4" method="post" action="public/cat_condiciones.php">
  <?=csrf_field()?>
  <input type="hidden" name="accion_cond" value="add">
  
  <div class="col-md-9">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;">
        <i class="bi bi-check-circle-fill"></i>
      </label>
      <input name="nombre" class="form-control" placeholder="Condición/Estado" required>
    </div>
  </div>
  
  <div class="col-md-3">
    <button class="btn btn-success w-100">
      <i class="bi bi-plus-lg"></i> Agregar
    </button>
  </div>
</form>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>
<div class="table-container">
  <div class="items-table-wrapper table-responsive">
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
                <form method="post" class="row g-2" action="public/cat_condiciones.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_cond" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="col-12">
                    <div class="input-group">
                      <label class="input-group-text text-white" style="background-color: #F59E0B;">
                        <i class="bi bi-check-circle-fill"></i>
                      </label>
                      <input name="nombre" class="form-control" required value="<?=h($r['nombre'])?>">
                    </div>
                  </div>
              </td>
              <td class="text-center">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-primary">
                      <i class="bi bi-check-lg"></i>
                    </button>
                    <a class="btn btn-sm btn-secondary" href="index.php?tab=ccond#ccond">
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

<!-- Modal de Confirmación para Eliminar -->
<!-- <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title d-flex align-items-center gap-2" id="deleteModalLabel">
          <i class="bi bi-exclamation-triangle-fill text-danger"></i>
          Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <div class="delete-icon-wrapper">
            <i class="bi bi-trash-fill"></i>
          </div>
        </div>
        <p class="text-center mb-2 fw-bold" id="deleteItemName"></p>
        <p class="text-center text-muted">Esta acción no se puede deshacer. ¿Estás seguro que deseas eliminar este registro?</p>
      </div>
      <div class="modal-footer border-0 justify-content-center gap-2">
        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancelar
        </button>
        <button type="button" class="btn btn-delete" id="confirmDeleteBtn">
          <i class="bi bi-trash-fill"></i> Eliminar
        </button>
      </div>
    </div>
  </div>
</div> -->

<script>
// Script para usar el modal de eliminación
(function() {
  const deleteModal = document.getElementById('deleteModal');
  const deleteItemName = document.getElementById('deleteItemName');
  const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
  let bsDeleteModal = null;
  let currentForm = null;

  function ensureDeleteModal() {
    if (!bsDeleteModal && window.bootstrap && bootstrap.Modal) {
      bsDeleteModal = new bootstrap.Modal(deleteModal);
    }
    return bsDeleteModal;
  }

  // Interceptar todos los formularios de eliminación
  document.querySelectorAll('form[action*="eliminar"], form[action*="cat_clases"], form[action*="cat_condiciones"], form[action*="cat_ubicaciones"]').forEach(form => {
    // Solo interceptar formularios con acción de eliminar
    const deleteInput = form.querySelector('input[name="accion_clase"][value="del"], input[name="accion_condicion"][value="del"], input[name="accion_ubicacion"][value="del"], input[name="accion_item"][value="del"]');
    
    if (!deleteInput) return; // Si no es un formulario de eliminar, ignorar
    
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Obtener el nombre del item de la fila más cercana
      const row = form.closest('tr');
      let itemName = 'este registro';
      
      // Buscar en diferentes posibles estructuras
      const itemNameEl = row ? (
        row.querySelector('.item-nombre') ||  // Para inventario
        row.querySelector('td:first-child')   // Para catálogos (primera celda)
      ) : null;
      
      if (itemNameEl) {
        itemName = itemNameEl.textContent.trim();
      }
      
      // Actualizar el contenido del modal
      deleteItemName.textContent = itemName;
      
      // Guardar referencia al formulario
      currentForm = form;
      
      // Mostrar el modal
      const modal = ensureDeleteModal();
      if (modal) modal.show();
    });
  });

  // Confirmar eliminación
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', function() {
      if (currentForm) {
        // Cerrar el modal
        const modal = ensureDeleteModal();
        if (modal) modal.hide();
        
        // Enviar el formulario después de cerrar el modal
        setTimeout(() => {
          currentForm.submit();
        }, 300);
      }
    });
  }

  // Limpiar la referencia al cerrar el modal
  deleteModal.addEventListener('hidden.bs.modal', function() {
    currentForm = null;
  });
})();
</script>