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
        header('Location: index.php?tab=cclase#cclase', true, 303); exit;
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
        header('Location: index.php?tab=cclase#cclase', true, 303); exit;
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
    header('Location: index.php?tab=cclase#cclase', true, 303); exit;
  }
}

$rows = $pdo->query("SELECT * FROM cat_clases ORDER BY nombre")->fetchAll();
?>
<form class="row g-2 mb-3" method="post" action="cat_clases.php">
  <?=csrf_field()?>
  <input type="hidden" name="accion_clase" value="add">
  <div class="col-md-8">
    <label class="form-label">Nueva clase</label>
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
  <thead><tr><th>Clase</th><th style="width:220px;">Acciones</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <?php if ($edit_id === (int)$r['id']): ?>
        <tr>
          <td>
            <form method="post" class="row g-2" action="cat_clases.php">
              <?=csrf_field()?>
              <input type="hidden" name="accion_clase" value="upd">
              <input type="hidden" name="id" value="<?=$r['id']?>">
              <div class="col-12">
                <input name="nombre" class="form-control" required value="<?=h($r['nombre'])?>">
              </div>
          </td>
          <td class="d-flex gap-2">
              <button class="btn btn-sm btn-primary">Guardar</button>
              <a class="btn btn-sm btn-secondary" href="index.php?tab=cclase#cclase">Cancelar</a>
            </form>
          </td>
        </tr>
      <?php else: ?>
        <tr>
          <td><?=h($r['nombre'])?></td>
          <td>
            <a class="btn btn-sm btn-outline-primary" href="index.php?tab=cclase&edit=<?=$r['id']?>#cclase">Editar</a>
            <form method="post" class="d-inline" action="cat_clases.php" onsubmit="return confirm('¿Eliminar?');">
              <?=csrf_field()?>
              <input type="hidden" name="accion_clase" value="del">
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
