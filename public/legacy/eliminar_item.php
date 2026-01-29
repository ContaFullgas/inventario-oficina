<?php

/*
  ARCHIVO LEGACY
  NO USAR EN PRODUCCIÓN

  Reemplazado por:
  - toggle_item.php (activo / inactivo)
*/

// header('Content-Type: application/json');

// require_once dirname(__DIR__, 2).'/config/db.php';
// require_once dirname(__DIR__, 2).'/config/util.php';
// require_once dirname(__DIR__, 2).'/config/auth.php';

// /* =========================
//    SEGURIDAD AJAX (SIN REDIRECT)
// ========================= */

// if (!auth_is_logged()) {
//   http_response_code(401);
//   echo json_encode([
//     'ok' => false,
//     'message' => 'Sesión expirada'
//   ]);
//   exit;
// }

// if (!auth_is_admin()) {
//   http_response_code(403);
//   echo json_encode([
//     'ok' => false,
//     'message' => 'No tienes permisos'
//   ]);
//   exit;
// }

// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//   http_response_code(405);
//   echo json_encode([
//     'ok' => false,
//     'message' => 'Método no permitido'
//   ]);
//   exit;
// }

// check_csrf();

// $id = (int)($_POST['id'] ?? 0);
// if ($id <= 0) {
//   echo json_encode([
//     'ok' => false,
//     'message' => 'ID inválido'
//   ]);
//   exit;
// }

// /* =========================
//    Obtener artículo
// ========================= */

// $stmt = $pdo->prepare("
//   SELECT cantidad, imagen
//   FROM items
//   WHERE id = :id
// ");
// $stmt->execute([':id' => $id]);
// $item = $stmt->fetch(PDO::FETCH_ASSOC);

// if (!$item) {
//   echo json_encode([
//     'ok' => false,
//     'message' => 'El artículo no existe'
//   ]);
//   exit;
// }

// /* =========================
//    🔒 BLOQUEO POR STOCK
// ========================= */

// if ((int)$item['cantidad'] > 0) {
//   echo json_encode([
//     'ok' => false,
//     'message' => 'No se puede eliminar un artículo con stock mayor a 0'
//   ]);
//   exit;
// }

// /* =========================
//    Intentar eliminar
// ========================= */

// try {

//   // Eliminar imagen
//   if (!empty($item['imagen'])) {
//     $path = dirname(__DIR__) . '/uploads/' . $item['imagen'];
//     if (is_file($path)) {
//       @unlink($path);
//     }
//   }

//   // Eliminar registro
//   $del = $pdo->prepare("DELETE FROM items WHERE id = :id");
//   $del->execute([':id' => $id]);

//   echo json_encode([
//     'ok' => true,
//     'message' => 'Artículo eliminado correctamente'
//   ]);
//   exit;

// } catch (PDOException $e) {

//   // Error por FK (tiene movimientos)
//   if ((int)$e->getCode() === 23000) {
//     echo json_encode([
//       'ok' => false,
//       'message' => 'No se puede eliminar el artículo porque tiene movimientos registrados'
//     ]);
//     exit;
//   }

//   // Otro error
//   echo json_encode([
//     'ok' => false,
//     'message' => 'Error al eliminar el artículo'
//   ]);
//   exit;
// }
