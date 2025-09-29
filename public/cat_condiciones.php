<?php
ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  // SOLO atiende si viene del formulario de condiciones:
  if (isset($_POST['accion_cond']) && $_POST['accion_cond'] === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    if ($nombre === '') $errors[] = 'El nombre es obligatorio';
    if (!$errors) {
      $stmt = $pdo->prepare("INSERT INTO cat_condiciones (nombre) VALUES (:n)");
      try {
        $stmt->execute([':n'=>$nombre]);
        flash_set('ok','Condición/Estado agregada');
      } catch(PDOException $e) {
        $errors[] = 'No se pudo agregar (¿duplicado?)';
      }
      if (!$errors) { header('Location: index.php?tab=ccond#ccond', true, 303); exit; }
    }
  }

    if (isset($_POST['accion_cond']) && $_POST['accion_cond'] === 'del') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // 1) Obtener el nombre de la condición/estado
            $row = $pdo->prepare("SELECT nombre FROM cat_condiciones WHERE id=:id");
            $row->execute([':id'=>$id]);
            $nombre = $row->fetchColumn();

            if ($nombre !== false) {
            // 2) Contar usos en items
            $c = $pdo->prepare("SELECT COUNT(*) FROM items WHERE condicion = :v");
            $c->execute([':v'=>$nombre]);
            $usos = (int)$c->fetchColumn();

            if ($usos > 0) {
                flash_set('ok', "No se puede eliminar: está en uso por $usos producto(s).");
            } else {
                $pdo->prepare("DELETE FROM cat_condiciones WHERE id=:id")->execute([':id'=>$id]);
                flash_set('ok', "Condición/Estado eliminado.");
            }
            }
        }
        header('Location: index.php?tab=ccond#ccond', true, 303);
        exit;
    }
}

$rows = $pdo->query("SELECT * FROM cat_condiciones ORDER BY nombre")->fetchAll();
?>
<form class="row g-2 mb-3" method="post" action="cat_condiciones.php">
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
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-sm table-hover align-middle">
  <thead><tr><th>Condición/Estado</th><th style="width:120px;">Acciones</th></tr></thead>
  <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?=h($r['nombre'])?></td>
        <td>
          <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar?');" action="cat_condiciones.php">
            <?=csrf_field()?>
            <input type="hidden" name="accion_cond" value="del">
            <input type="hidden" name="id" value="<?=$r['id']?>">
            <button class="btn btn-sm btn-outline-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
