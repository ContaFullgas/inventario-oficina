<?php

//Archivo editar.php

ob_start();
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/util.php';
require_once __DIR__.'/../config/auth.php';
auth_check();
auth_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM items WHERE id=:id");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); echo "No encontrado"; exit; }

$clases = $pdo->query("SELECT id, nombre FROM cat_clases ORDER BY nombre")->fetchAll();
$conds  = $pdo->query("SELECT id, nombre FROM cat_condiciones ORDER BY nombre")->fetchAll();
$ubis   = $pdo->query("SELECT id, nombre FROM cat_ubicaciones ORDER BY nombre")->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $nombre    = trim($_POST['nombre'] ?? '');
  $cantidad  = (int)($_POST['cantidad'] ?? 0);
  $notas     = trim($_POST['notas'] ?? '');
  $min_stock = (int)($_POST['min_stock'] ?? 0);
  $max_stock = (int)($_POST['max_stock'] ?? 0);

  $clase_id     = ($_POST['clase_id'] !== '')     ? (int)$_POST['clase_id']     : null;
  $condicion_id = ($_POST['condicion_id'] !== '') ? (int)$_POST['condicion_id'] : null;
  $ubicacion_id = ($_POST['ubicacion_id'] !== '') ? (int)$_POST['ubicacion_id'] : null;

  if ($nombre === '') $errors[] = 'El nombre es obligatorio';
  if ($min_stock < 0 || $max_stock < 0 || $cantidad < 0) $errors[] = 'Cantidades/stock no pueden ser negativos';
  if ($max_stock < $min_stock) $errors[] = 'El máximo no puede ser menor que el mínimo';

  $imgName = $item['imagen'];
  if (!empty($_FILES['imagen']['name'])) {
    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
      $errors[] = 'Formato de imagen no permitido (usa JPG/PNG/WEBP)';
    } elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
      $errors[] = 'Error al subir imagen';
    } else {
      if (!empty($imgName)) {
        $old = __DIR__.'/../uploads/'.$imgName;
        if (is_file($old)) @unlink($old);
      }
      $imgName = uniqid('img_', true).'.'.$ext;
      $dest = __DIR__.'/../uploads/'.$imgName;
      if (!is_dir(__DIR__.'/../uploads')) @mkdir(__DIR__.'/../uploads', 0775, true);
      move_uploaded_file($_FILES['imagen']['tmp_name'], $dest);
    }
  }

  if (!$errors) {
    $sql = "UPDATE items SET
              nombre=:nombre,
              clase_id=:clase_id,
              cantidad=:cantidad,
              condicion_id=:condicion_id,
              notas=:notas,
              ubicacion_id=:ubicacion_id,
              min_stock=:min_stock,
              max_stock=:max_stock,
              imagen=:imagen
            WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':nombre'       => $nombre,
      ':clase_id'     => $clase_id,
      ':cantidad'     => $cantidad,
      ':condicion_id' => $condicion_id,
      ':notas'        => $notas ?: null,
      ':ubicacion_id' => $ubicacion_id,
      ':min_stock'    => $min_stock,
      ':max_stock'    => $max_stock,
      ':imagen'       => $imgName,
      ':id'           => $id
    ]);

    flash_set('ok','Cambios guardados correctamente');
    $dest = "../index.php?tab=inv#inv";
    if (!headers_sent()) { header("Location: $dest", true, 303); exit; }
    echo '<!doctype html><html><head><meta http-equiv="refresh" content="0;url='.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'"></head><body><script>location.replace("'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'");</script><a href="'.htmlspecialchars($dest,ENT_QUOTES,'UTF-8').'">Continuar</a></body></html>';
    exit;
  } else {
    // reinyecta al form
    $item['nombre']=$nombre; $item['cantidad']=$cantidad; $item['notas']=$notas;
    $item['min_stock']=$min_stock; $item['max_stock']=$max_stock; $item['imagen']=$imgName;
    $item['clase_id']=$clase_id; $item['condicion_id']=$condicion_id; $item['ubicacion_id']=$ubicacion_id;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Inventario - InvenPro</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    :root {
      --primary: #0f766e;
      --primary-dark: #115e59;
      --primary-light: #ccfbf1;
      --secondary: #0284c7;
      --dark: #1e293b;
      --darker: #0f172a;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --success: #059669;
      --warning: #f59e0b;
      --danger: #dc2626;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #0f766e 0%, #0284c7 100%);
      min-height: 100vh;
    }

    /* ===== NAVBAR ===== */
    .modern-navbar {
      /*background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);*/
      background-color: #000;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 3px solid var(--primary);
    }

    .navbar-brand-custom {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
      text-decoration: none;
    }

    .navbar-brand-custom:hover {
      color: var(--primary-light);
    }

    .brand-icon {
      width: 45px;
      height: 45px;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: white;
      box-shadow: 0 4px 12px rgba(15, 118, 110, 0.4);
    }

    .user-badge {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 8px 20px;
      border-radius: 50px;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 500;
    }

    .user-badge i {
      font-size: 1.1rem;
    }

    .role-badge {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-logout {
      background: rgba(220, 38, 38, 0.1);
      border: 2px solid rgba(220, 38, 38, 0.3);
      color: #fca5a5;
      padding: 8px 24px;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-logout:hover {
      background: var(--danger);
      border-color: var(--danger);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
    }

    /* ===== CONTAINER ===== */
    .main-container {
      max-width: 1400px;
      margin: 2rem auto;
      padding: 0 20px;
    }

    /* ===== FLASH MESSAGES ===== */
    .flash-message {
      background: white;
      border-radius: 12px;
      padding: 16px 20px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 4px solid var(--success);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .flash-message i {
      font-size: 1.5rem;
      color: var(--success);
    }

    .flash-message .flash-text {
      flex: 1;
      color: var(--gray-800);
      font-weight: 500;
    }

    .btn-close-custom {
      background: transparent;
      border: none;
      color: var(--gray-700);
      font-size: 1.2rem;
      cursor: pointer;
      padding: 0;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.2s ease;
    }

    .btn-close-custom:hover {
      background: var(--gray-200);
    }

    /* ===== CARD CONTAINER ===== */
    .inventory-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
      overflow: hidden;
    }

    /* ===== MODERN TABS ===== */
    .modern-tabs {
      background: linear-gradient(to right, var(--gray-100), var(--gray-50));
      padding: 12px 20px;
      display: flex;
      gap: 8px;
      border-bottom: 2px solid var(--gray-200);
      overflow-x: auto;
    }

    .modern-tabs::-webkit-scrollbar {
      height: 6px;
    }

    .modern-tabs::-webkit-scrollbar-thumb {
      background: var(--gray-300);
      border-radius: 10px;
    }

    .tab-link {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      background: transparent;
      border: none;
      border-radius: 10px;
      color: var(--gray-700);
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      transition: all 0.3s ease;
      white-space: nowrap;
      cursor: pointer;
    }

    .tab-link:hover {
      background: rgba(15, 118, 110, 0.1);
      color: var(--primary);
      transform: translateY(-2px);
    }

    .tab-link.active {
      background: white;
      color: var(--primary);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    .tab-link i {
      font-size: 1.1rem;
    }

    /* ===== TAB CONTENT ===== */
    .tab-content-area {
      padding: 2rem;
      min-height: 400px;
      background: var(--gray-50);
    }

    .tab-pane {
      display: none;
      animation: fadeIn 0.3s ease;
    }

    .tab-pane.show.active {
      display: block;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* ===== STATS SECTION ===== */
    .stats-header {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      color: white;
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 8px 20px rgba(15, 118, 110, 0.3);
    }

    .stats-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .stats-header p {
      margin: 8px 0 0 0;
      opacity: 0.9;
    }

    /* Formulario de búsqueda mejorado */
    #inventario-form .input-group-text {
      background: linear-gradient(135deg, #f4d03f 0%, #f39c12 100%);
      border: none;
      color: white;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(243,156,18,0.3);
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

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .main-container {
        padding: 0 10px;
        margin: 1rem auto;
      }

      .tab-content-area {
        padding: 1rem;
      }

      .user-badge {
        padding: 6px 12px;
        font-size: 0.85rem;
      }

      .btn-logout {
        padding: 6px 16px;
        font-size: 0.85rem;
      }

      .modern-tabs {
        padding: 8px 10px;
      }

      .tab-link {
        padding: 10px 16px;
        font-size: 0.875rem;
      }

      .navbar-brand-custom {
        font-size: 1.2rem;
      }

      .brand-icon {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
      }
    }

    /* ===== UTILITIES ===== */
    .icon-gradient {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Normalizar altura de inputs en formularios */
    #inventario-form .input-group > .form-control,
    #inventario-form .input-group > .form-select,
    #inventario-form .input-group > .input-group-text {
      height: 44px;
    }

    #inventario-form .input-group {
      align-items: center;
    }

  </style>
</head>
<body>

  <!--Aquí pondre el head -->
<nav class="modern-navbar">
  <div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center">
      <a href="../index.php?tab=inv#inv" class="navbar-brand-custom">
        <div class="brand-icon">
          <i class="bi bi-backspace-fill"></i>
        </div>
        <span>Editando: <?=h($item['nombre'])?></span>
      </a>
    </div>
  </div>
</nav>

<div class="main-container">
  <div class="inventory-card">
    <div class="tab-content-area">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?=h($e)?></li><?php endforeach; ?></ul></div>
      <?php endif; ?>
        <!--Aquí va el Form -->
      <form enctype="multipart/form-data" class="row g-3" id="inventario-form">
      <input type="hidden" name="id" value="<?= intval($id) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="return_url"
        value="<?= h($_SERVER['HTTP_REFERER'] ?? '../index.php?tab=inv#inv') ?>">

        <!--Grupo 1 dos inputs -->
        <div class="col-md-6">
          <label class="form-label fw-semibold">
            Nombre del producto
          </label>
          <div class="input-group">
            <label class="input-group-text text-white"><i class="bi bi-clipboard2-fill"></i></label>
            <input type="text" name="nombre" class="form-control" placeholder="Nombre del articulo/Producto *" required value="<?=h($item['nombre'])?>">
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">
            Clase
          </label>
          <div class="input-group">
            <label class="input-group-text text-white"><i class="bi bi-tag-fill"></i></label>
            <select name="clase_id" class="form-select">
              <option value="">Selecciona una Clase</option>
              <?php foreach($clases as $c): ?>
                <option value="<?=$c['id']?>" <?=$c['id']==($item['clase_id']??null)?'selected':''?>><?=h($c['nombre'])?></option>
              <?php endforeach; ?>
            </select>
            <span class="input-group-text btn btn-success"><a href="../index.php?tab=cclase#cclase" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
          </div>
        </div>

        <!--Grupo 2 3 inputs de stock -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Stock
          </label>
          <div class="input-group">
            <label  class="input-group-text text-white"><i class="bi bi-layers-fill"></i></label>
            <input type="number" name="cantidad" class="form-control" min="0" value="<?=intval($item['cantidad'])?>">
          </div>
          
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Min
          </label>
          <div class="input-group">
            <label  class="input-group-text text-white"><i class="bi bi-arrow-down-circle-fill"></i></label>
            <input type="number" name="min_stock" class="form-control" min="0" value="<?=intval($item['min_stock'])?>">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Max
          </label>
          <div class="input-group">
            <label  class="input-group-text text-white"><i class="bi bi-arrow-up-circle-fill"></i></label>
            <input type="number" name="max_stock" class="form-control" min="0" value="<?=intval($item['max_stock'])?>">
          </div>
        </div>

        <!--Grupo 3 3 inputs -->
        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Condicion
          </label>
          <div class="input-group">
            <label class="input-group-text text-white"><i class="bi bi-check-circle-fill"></i></label>
            <select name="condicion_id" class="form-select">
              <option value="">Selecciona una Condición/Estado</option>
              <?php foreach($conds as $c): ?>
                <option value="<?=$c['id']?>" <?=$c['id']==($item['condicion_id']??null)?'selected':''?>><?=h($c['nombre'])?></option>
              <?php endforeach; ?>
            </select>
            <span class="input-group-text btn btn-success"><a href="../index.php?tab=ccond#ccond" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Ubicación
          </label>
          <div class="input-group">
            <label class="input-group-text text-white"><i class="bi bi-geo-alt-fill"></i></label>
            <select name="ubicacion_id" class="form-select">
              <option value="">Selecciona una Ubicación</option>
              <?php foreach($ubis as $u): ?>
                <option value="<?=$u['id']?>" <?=$u['id']==($item['ubicacion_id']??null)?'selected':''?>><?=h($u['nombre'])?></option>
              <?php endforeach; ?>
            </select>
            <span class="input-group-text btn btn-success"><a href="../index.php?tab=cubi#cubi" class="link-light"><i class="bi bi-plus-lg"></i></a></span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label fw-semibold">
            Imagen
          </label>
          <div class="input-group">
            <label class="input-group-text text-white"><i class="bi bi-image-fill"></i></label>
            <input type="file" name="imagen" class="form-control" accept="image/*">
          </div>
        </div>
              <!--Imagen previa -->
        <div class="col-12">
          <div class="list-group">
            <label class="list-group-item input-group-text"><i class="bi bi-file-image-fill"></i> Imagen actual</label>
             <?php if (!empty($item['imagen'])): ?>
              <div class="mt-2 list-group-item text-center">
                <img src="../uploads/<?=h($item['imagen'])?>" style="height:100px;object-fit:cover;" class="rounded border" alt="">
              </div>
            <?php endif; ?>
          </div>

        </div>
        
        <!--Notas -->
        <div class="col-12 list-group">
          <label class="list-group-item input-group-text"><i class="bi bi-journal-bookmark-fill"></i> Notas</label>
          <textarea name="notas" class="form-control mt-2" rows="3"><?=h($item['notas'])?></textarea>
        </div>

        <div class="col-12 text-center">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-floppy2-fill"></i> Guardar cambios
          </button>

          <button type="button" class="btn btn-secondary" id="btn-cancelar">
            <i class="bi bi-x-lg"></i> Cancelar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('inventario-form').addEventListener('submit', function(e) {
  e.preventDefault();

  const data = new FormData(this);

  fetch('ajax/editar_item.php', {
    method: 'POST',
    body: data
  })
  .then(r => r.json())
  .then(res => {
    if (res.ok) {
      // alert(res.message);

      const returnUrl = document.querySelector('[name="return_url"]').value;
      window.location.href = returnUrl;
    } else {
      alert(res.errors.join('\n'));
    }
  });


});

document.getElementById('btn-cancelar').addEventListener('click', function () {
  const returnUrl = document.querySelector('[name="return_url"]').value;
  window.location.href = returnUrl;
});


</script>


</body>
</html>

