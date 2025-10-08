<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$errors = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  if (isset($_POST['accion_cond']) && $_POST['accion_cond'] === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (!$errors) {
      try {
        $pdo->prepare("INSERT INTO cat_condiciones (nombre) VALUES (:n)")->execute([':n'=>$nombre]);
        flash_set('ok','Condición/Estado agregado');
        header('Location: ../index.php?tab=ccond#ccond', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
    }
  }

  if (isset($_POST['accion_cond']) && $_POST['accion_cond'] === 'upd') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    if ($id <= 0)       $errors[] = 'ID inválido';
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';

    if (!$errors) {
      try {
        $pdo->prepare("UPDATE cat_condiciones SET nombre=:n WHERE id=:id")
            ->execute([':n'=>$nombre, ':id'=>$id]);
        flash_set('ok','Condición/Estado actualizado');
        header('Location: ../index.php?tab=ccond#ccond', true, 303); exit;
      } catch (PDOException $e) {
        $errors[] = 'No se pudo actualizar (¿duplicado?)';
      }
    }
    $edit_id = $id;
  }

  if (isset($_POST['accion_cond']) && $_POST['accion_cond'] === 'del') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        $pdo->prepare("DELETE FROM cat_condiciones WHERE id=:id")->execute([':id'=>$id]);
        flash_set('ok','Condición/Estado eliminado');
      } catch (PDOException $e) {
        flash_set('ok','No se puede eliminar: está en uso por productos.');
      }
    }
    header('Location: ../index.php?tab=ccond#ccond', true, 303); exit;
  }
}

$rows = $pdo->query("SELECT * FROM cat_condiciones ORDER BY nombre")->fetchAll();
?>
<form class="row g-2 mb-3" method="post" action="public/cat_condiciones.php">
  <?=csrf_field()?>
  <input type="hidden" name="accion_cond" value="add">
  <div class="col-md-8">
    <label class="form-label">Nueva condición/estado</label>
    <input name="nombre" class="form-control" required>
  </div>
  <div class="col-md-4 d-flex align-items-end">
    <button class="btn btn-success w-100">Agregar</button>
  </div>
</form>

<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
  <thead><tr><th>Condición/Estado</th><th style="width:220px;">Acciones</th></tr></thead>
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
                <input name="nombre" class="form-control" required value="<?=h($r['nombre'])?>">
              </div>
          </td>
          <td class="d-flex gap-2">
              <button class="btn btn-sm btn-primary">Guardar</button>
              <a class="btn btn-sm btn-secondary" href="index.php?tab=ccond#ccond">Cancelar</a>
            </form>
          </td>
        </tr>
      <?php else: ?>
        <tr>
          <td><?=h($r['nombre'])?></td>
          <td>
            <a class="btn btn-sm btn-outline-primary" href="index.php?tab=ccond&edit=<?=$r['id']?>#ccond">Editar</a>
            <form method="post" class="d-inline" action="public/cat_condiciones.php" onsubmit="return confirm('¿Eliminar?');">
              <?=csrf_field()?>
              <input type="hidden" name="accion_cond" value="del">
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <button class="btn btn-sm btn-outline-danger">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endif; ?>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
