<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$errors = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  if (isset($_POST['accion_ubi']) && $_POST['accion_ubi'] === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (!$errors) {
      try {
        $pdo->prepare("INSERT INTO cat_ubicaciones (nombre) VALUES (:n)")->execute([':n'=>$nombre]);
        flash_set('ok','Ubicación agregada');
        header('Location: ../index.php?tab=cubi#cubi', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  if (isset($_POST['accion_ubi']) && $_POST['accion_ubi'] === 'upd') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id <= 0)       $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $pdo->prepare("UPDATE cat_ubicaciones SET nombre=:n WHERE id=:id")
            ->execute([':n'=>$nombre, ':id'=>$id]);
        flash_set('ok','Ubicación actualizada');
        header('Location: ../index.php?tab=cubi#cubi', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }
    $edit_id = $id;
  }

  if (isset($_POST['accion_ubi']) && $_POST['accion_ubi'] === 'del') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        $pdo->prepare("DELETE FROM cat_ubicaciones WHERE id=:id")->execute([':id'=>$id]);
        flash_set('ok','Ubicación eliminada');
      } catch (PDOException $e) {
        flash_set('ok','No se puede eliminar: está en uso por productos.');
      }
    }
    header('Location: ../index.php?tab=cubi#cubi', true, 303); exit;
  }
}

$rows = $pdo->query("SELECT * FROM cat_ubicaciones ORDER BY nombre")->fetchAll();
?>

<!-- Formulario de agregar -->
<form id="inventario-form" class="row g-3 mb-4" method="post" action="public/cat_ubicaciones.php">
  <?=csrf_field()?>
  <input type="hidden" name="accion_ubi" value="add">
  
  <div class="col-md-9">
    <div class="input-group">
      <label class="input-group-text text-white" style="background-color: #F59E0B;">
        <i class="bi bi-geo-alt-fill"></i>
      </label>
      <input name="nombre" class="form-control" placeholder="Ubicación" required>
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
                <form method="post" class="row g-2" action="public/cat_ubicaciones.php">
                  <?=csrf_field()?>
                  <input type="hidden" name="accion_ubi" value="upd">
                  <input type="hidden" name="id" value="<?=$r['id']?>">
                  <div class="col-12">
                    <div class="input-group">
                      <label class="input-group-text text-white" style="background-color: #F59E0B;">
                        <i class="bi bi-geo-alt-fill"></i>
                      </label>
                      <input name="nombre" class="form-control" required value="<?=h($r['nombre'])?>">
                    </div>
                  </div>
              </td>
              <td class="text-center">
                  <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-primary">
                      <i class="bi bi-check-lg"></i> Guardar
                    </button>
                    <a class="btn btn-sm btn-secondary" href="index.php?tab=cubi#cubi">
                      <i class="bi bi-x-lg"></i> Cancelar
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
