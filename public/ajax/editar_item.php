<?php
header('Content-Type: application/json');

require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/util.php';
require_once __DIR__.'/../../config/auth.php';

auth_check();
auth_require_admin();
check_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'errors'=>['Método no permitido']]);
  exit;
}

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM items WHERE id=:id");
$stmt->execute([':id'=>$id]);
$item = $stmt->fetch();

if (!$item) {
  echo json_encode(['ok'=>false,'errors'=>['Artículo no encontrado']]);
  exit;
}

$nombre    = trim($_POST['nombre'] ?? '');
$cantidad  = (int)($_POST['cantidad'] ?? 0);
$notas     = trim($_POST['notas'] ?? '');
$min_stock = (int)($_POST['min_stock'] ?? 0);
$max_stock = (int)($_POST['max_stock'] ?? 0);

$clase_id     = $_POST['clase_id'] !== '' ? (int)$_POST['clase_id'] : null;
$condicion_id = $_POST['condicion_id'] !== '' ? (int)$_POST['condicion_id'] : null;
$ubicacion_id = $_POST['ubicacion_id'] !== '' ? (int)$_POST['ubicacion_id'] : null;

$errors = [];

if ($nombre === '') $errors[] = 'El nombre es obligatorio';
if ($cantidad < 0 || $min_stock < 0 || $max_stock < 0)
  $errors[] = 'Las cantidades no pueden ser negativas';
if ($max_stock < $min_stock)
  $errors[] = 'El máximo no puede ser menor que el mínimo';

/* Imagen */
$imgName = $item['imagen'];

if (!empty($_FILES['imagen']['name'])) {

    $ext = strtolower(
        pathinfo(
            $_FILES['imagen']['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {

        $errors[] = 'Formato de imagen no permitido';

    } elseif ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {

        $errors[] = 'Error al subir la imagen';

    } else {

        if ($imgName) {

            $oldImg =
                __DIR__.'/../../uploads/'.
                $imgName;

            if (is_file($oldImg)) {
                @unlink($oldImg);
            }

            $oldThumb =
                __DIR__.'/../../uploads/thumbs/'.
                pathinfo($imgName, PATHINFO_FILENAME).
                '.png';

            if (is_file($oldThumb)) {
                @unlink($oldThumb);
            }
        }

        $imgName =
            uniqid('img_', true).
            '.'.
            $ext;

        $dest =
            __DIR__.'/../../uploads/'.
            $imgName;

        if (move_uploaded_file(
            $_FILES['imagen']['tmp_name'],
            $dest
        )) {

            try {

                $thumbDir =
                    __DIR__.'/../../uploads/thumbs';

                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0775, true);
                }

                switch ($ext) {

                    case 'jpg':
                    case 'jpeg':
                        $src = imagecreatefromjpeg($dest);
                        break;

                    case 'png':
                        $src = imagecreatefrompng($dest);
                        break;

                    case 'webp':
                        $src = imagecreatefromwebp($dest);
                        break;

                    default:
                        $src = false;
                }

                if ($src !== false) {

                    $origW = imagesx($src);
                    $origH = imagesy($src);

                    $thumbSize = 150;

                    $ratio = min(
                        $thumbSize / $origW,
                        $thumbSize / $origH
                    );

                    $newW = max(
                        1,
                        (int)($origW * $ratio)
                    );

                    $newH = max(
                        1,
                        (int)($origH * $ratio)
                    );

                    $thumb =
                        imagecreatetruecolor(
                            $newW,
                            $newH
                        );

                    imagecopyresampled(
                        $thumb,
                        $src,
                        0,
                        0,
                        0,
                        0,
                        $newW,
                        $newH,
                        $origW,
                        $origH
                    );

                    imagepng(
                        $thumb,
                        $thumbDir.'/'.
                        pathinfo(
                            $imgName,
                            PATHINFO_FILENAME
                        ).
                        '.png',
                        8
                    );

                    imagedestroy($src);
                    imagedestroy($thumb);
                }

            } catch (Throwable $e) {

                error_log(
                    'Error creando thumbnail: '.
                    $e->getMessage()
                );
            }
        }
    }
}

if ($errors) {
  echo json_encode(['ok'=>false,'errors'=>$errors]);
  exit;
}

/* Update */
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
  ':nombre'=>$nombre,
  ':clase_id'=>$clase_id,
  ':cantidad'=>$cantidad,
  ':condicion_id'=>$condicion_id,
  ':notas'=>$notas ?: null,
  ':ubicacion_id'=>$ubicacion_id,
  ':min_stock'=>$min_stock,
  ':max_stock'=>$max_stock,
  ':imagen'=>$imgName,
  ':id'=>$id
]);

echo json_encode([
  'ok'=>true,
  'message'=>'Cambios guardados correctamente'
]);
