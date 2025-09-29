<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  // SOLO atiende si viene del formulario de clases:
  if (isset($_POST['accion_clase']) && $_POST['accion_clase'] === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (!$errors) {
      $stmt = $pdo->prepare("INSERT INTO cat_clases (nombre) VALUES (:n)");
      try {
        $stmt->execute([':n'=>$nombre]);
        flash_set('ok','Clase agregada');
      } catch(PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
      if (!$errors) { header('Location: index.php?tab=cclase#cclase', true, 303); exit; }
    }
  }

  if (isset($_POST['accion_clase']) && $_POST['accion_clase'] === 'del') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      try {
        $pdo->prepare("DELETE FROM cat_clases WHERE id=:id")->execute([':id'=>$id]);
        flash_set('ok','Clase eliminada');
      } catch (PDOException $e) {
        // Si la clase está referenciada por items (FK RESTRICT), caerá aquí
        flash_set('ok','No se puede eliminar: está en uso por productos.');
      }
    }
    header('Location: index.php?tab=cclase#cclase', true, 303);
    exit;
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
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
  <thead><tr><th>Clase</th><th style="width:120px;">Acciones</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?=h($r['nombre'])?></td>
        <td>
          <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar?');" action="cat_clases.php">
            <?=csrf_field()?>
            <input type="hidden" name="accion_clase" value="del">
            <input type="hidden" name="id" value="<?=$r['id']?>">
            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
