<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$errors = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  // Agregar
  if (isset($_POST['accion_clase']) && $_POST['accion_clase'] === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (!$errors) {
      try {
        $pdo->prepare("INSERT INTO cat_clases (nombre) VALUES (:n)")->execute([':n'=>$nombre]);
        flash_set('ok','Clase agregada');
        header('Location: ../index.php?tab=cclase#cclase', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  // Actualizar (editar)
  if (isset($_POST['accion_clase']) && $_POST['accion_clase'] === 'upd') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id <= 0)     $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $stmt = $pdo->prepare("UPDATE cat_clases SET nombre=:n WHERE id=:id");
        $stmt->execute([':n'=>$nombre, ':id'=>$id]);
        flash_set('ok','Clase actualizada');
        header('Location: ../index.php?tab=cclase#cclase', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }
    // Si hubo errores, mantener edit_id para re-render
    $edit_id = $id;
  }

  // Eliminar (con FK RESTRICT: mensaje si está en uso)
  if (isset($_POST['accion_clase']) && $_POST['accion_clase'] === 'del') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        $pdo->prepare("DELETE FROM cat_clases WHERE id=:id")->execute([':id'=>$id]);
        flash_set('ok','Clase eliminada');
      } catch (PDOException $e) {
        flash_set('ok','No se puede eliminar: está en uso por productos.');
      }
    }
    header('Location: ../index.php?tab=cclase#cclase', true, 303); exit;
  }
}

$rows = $pdo->query("SELECT * FROM cat_clases ORDER BY nombre")->fetchAll();
?>

<style>
/* Formulario de búsqueda mejorado */
#inventario-form {
  background: white;
  padding: 1.5rem;
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
/* Prevenir conflictos entre modales */
.modal {
  z-index: 1055 !important;
}

.modal-backdrop {
  z-index: 1050 !important;
}

#deleteModal {
  z-index: 1060 !important;
}

#deleteModal + .modal-backdrop {
  z-index: 1059 !important;
}

#imgModal {
  z-index: 1055 !important;
}

#imgModal + .modal-backdrop {
  z-index: 1054 !important;
}

/* Estilos del Modal */
#deleteModal {
  z-index: 9999 !important;
}

#deleteModal .modal-backdrop {
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
</style>

<!-- Formulario de agregar -->
<form id="inventario-form" class="row g-3 mb-4" method="post" action="public/cat_clases.php">
  <?=csrf_field()?>
  <input type="hidden" name="accion_clase" value="add">
  
  <div class="col-md-9">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;">
        <i class="bi bi-tag-fill"></i>
      </label>
      <input name="nombre" class="form-control" placeholder="Clase" required>
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
                <form method="post" class="row g-2" action="public/cat_clases.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_clase" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="col-12">
                    <div class="input-group">
                      <label class="input-group-text text-white" style="background-color: #F59E0B;">
                        <i class="bi bi-tag-fill"></i>
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
                  <a class="btn btn-sm btn-secondary" href="index.php?tab=cclase#cclase">
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

<!-- Modal de Confirmación para Eliminar
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">
          <i class="bi bi-exclamation-triangle-fill"></i>
          Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <div class="delete-icon-wrapper">
          <i class="bi bi-trash-fill"></i>
        </div>
        <div id="deleteItemName">Nombre del registro</div>
        <p class="delete-warning-text">
          <span class="delete-warning-highlight">⚠️ Esta acción no se puede deshacer.</span><br>
          ¿Estás seguro que deseas eliminar este registro permanentemente?
        </p>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          <span>Cancelar</span>
        </button>
        <button type="button" class="btn btn-delete" id="confirmDeleteBtn">
          <i class="bi bi-trash-fill"></i>
          <span>Eliminar</span>
        </button>
      </div>
    </div>
  </div>
</div> -->

<!--Modal eliminación -->
<!-- <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-exclamation-triangle-fill text-warning"></i>
          Confirmar Eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body text-center">

        <div class="delete-icon-wrapper mb-3">
          <i class="bi bi-trash-fill"></i>
        </div>

        <div id="deleteItemName" class="fw-bold fs-5 mb-2">Nombre del registro</div>

        <p class="delete-warning-text">
          <span class="delete-warning-highlight text-danger fw-semibold">⚠️ Esta acción no se puede deshacer.</span><br>
          ¿Estás seguro que deseas eliminar este registro permanentemente?
        </p>

      </div>

      <div class="modal-footer justify-content-between">

        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i>
          <span>Cancelar</span>
        </button>

        <button type="button" class="btn btn-delete" id="confirmDeleteBtn">
          <i class="bi bi-trash-fill"></i>
          <span>Eliminar</span>
        </button>

      </div>

    </div>
  </div>
</div> -->

<script>
// ========================================
// SCRIPT UNIVERSAL PARA MODAL DE ELIMINACIÓN
// Sin conflictos con otros modales
// ========================================

// (function() {
//   'use strict';
  
//   console.log('🗑️ Iniciando sistema de eliminación...');
  
//   // Esperar a que el DOM y Bootstrap estén listos
//   if (document.readyState === 'loading') {
//     document.addEventListener('DOMContentLoaded', init);
//   } else {
//     init();
//   }
  
//   function init() {
//     console.log('✅ DOM listo, configurando modal de eliminación');
    
//     const deleteModal = document.getElementById('deleteModal');
//     const deleteItemName = document.getElementById('deleteItemName');
//     const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
//     const cancelBtn = deleteModal ? deleteModal.querySelector('.btn-cancel') : null;
    
//     if (!deleteModal) {
//       console.error('❌ Modal #deleteModal no encontrado');
//       return;
//     }
    
//     let currentForm = null;
//     let bsDeleteModal = null;
    
//     // Función para obtener instancia del modal
//     function getModalInstance() {
//       if (!bsDeleteModal && window.bootstrap && bootstrap.Modal) {
//         bsDeleteModal = new bootstrap.Modal(deleteModal, {
//           backdrop: true,
//           keyboard: true,
//           focus: true
//         });
//       }
//       return bsDeleteModal;
//     }
    
//     // Limpiar backdrops huérfanos
//     function cleanBackdrops() {
//       const backdrops = document.querySelectorAll('.modal-backdrop');
//       backdrops.forEach(backdrop => backdrop.remove());
//       document.body.classList.remove('modal-open');
//       document.body.style.overflow = '';
//       document.body.style.paddingRight = '';
//     }
    
//     // Interceptar clicks en botones de eliminar
//     document.addEventListener('click', function(e) {
//       const deleteBtn = e.target.closest('.btn-action-delete');
      
//       if (!deleteBtn) return;
      
//       console.log('🔴 Click en botón eliminar');
//       e.preventDefault();
//       e.stopPropagation();
      
//       // Limpiar cualquier backdrop previo
//       cleanBackdrops();
      
//       // Obtener formulario
//       currentForm = deleteBtn.closest('form');
      
//       if (!currentForm) {
//         console.error('❌ No se encontró el formulario');
//         return;
//       }
      
//       console.log('✅ Formulario encontrado');
      
//       // Obtener nombre del item
//       const row = deleteBtn.closest('tr');
//       let itemName = 'este registro';
      
//       if (row) {
//         // Intentar obtener de diferentes formas
//         const nameEl = row.querySelector('.item-nombre') || 
//                       row.querySelector('td:first-child');
        
//         if (nameEl) {
//           itemName = nameEl.textContent.trim();
//           console.log('📝 Item:', itemName);
//         }
//       }
      
//       // Actualizar modal
//       if (deleteItemName) {
//         deleteItemName.textContent = itemName;
//       }
      
//       // Mostrar modal
//       const modal = getModalInstance();
//       if (modal) {
//         try {
//           modal.show();
//           console.log('✅ Modal mostrado correctamente');
//         } catch (error) {
//           console.error('❌ Error al mostrar modal:', error);
//         }
//       }
//     }, true); // useCapture = true para capturar antes
    
//     // Confirmar eliminación
//     if (confirmDeleteBtn) {
//       confirmDeleteBtn.addEventListener('click', function(e) {
//         e.preventDefault();
//         console.log('✅ Eliminación confirmada');
        
//         if (!currentForm) {
//           console.error('❌ No hay formulario para enviar');
//           return;
//         }
        
//         // Cerrar modal
//         const modal = getModalInstance();
//         if (modal) {
//           try {
//             modal.hide();
//           } catch (error) {
//             console.error('Error al cerrar modal:', error);
//           }
//         }
        
//         // Esperar a que se cierre el modal
//         setTimeout(function() {
//           cleanBackdrops();
//           console.log('📤 Enviando formulario...');
//           currentForm.submit();
//         }, 300);
//       });
//     }
    
//     // Cancelar eliminación
//     if (cancelBtn) {
//       cancelBtn.addEventListener('click', function() {
//         console.log('❌ Eliminación cancelada');
//         currentForm = null;
        
//         const modal = getModalInstance();
//         if (modal) {
//           try {
//             modal.hide();
//           } catch (error) {
//             console.error('Error al cerrar modal:', error);
//           }
//         }
        
//         setTimeout(cleanBackdrops, 300);
//       });
//     }
    
//     // Limpiar al cerrar modal
//     if (deleteModal) {
//       deleteModal.addEventListener('hidden.bs.modal', function() {
//         console.log('🔒 Modal cerrado');
//         currentForm = null;
//         cleanBackdrops();
//       });
      
//       // Asegurar que se muestre correctamente
//       deleteModal.addEventListener('show.bs.modal', function() {
//         console.log('👁️ Mostrando modal...');
//       });
      
//       deleteModal.addEventListener('shown.bs.modal', function() {
//         console.log('✅ Modal visible');
//       });
//     }
    
//     console.log('✅ Sistema de eliminación configurado');
//   }
// })();
// </script>