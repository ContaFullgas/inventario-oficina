<?php
// Archivo: public/ajax/rotacion_accion.php
// Acciones del turno de inventario: regenerar, completar, deshacer.

require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/util.php';
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/ajax.php';
require_once __DIR__.'/../../config/rotacion.php';

auth_check();
auth_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  ajax_response(false, 'Método no permitido');
}

check_csrf();

$accion = $_POST['accion'] ?? '';

switch ($accion) {

  // Descarta la pareja pendiente (sin guardar nada) y propone otra. No requiere confirmación.
  case 'regenerar': {
    $pendiente = rot_get_pendiente($pdo);
    if (!$pendiente) {
      ajax_response(false, 'Se necesitan al menos 2 usuarios (no administradores) activos');
    }

    [$rid, $aid] = rot_siguiente_par($pdo, (int)$pendiente['responsable_id']);
    if (!$rid) {
      ajax_response(false, 'No hay suficientes usuarios para regenerar');
    }

    // Misma fecha de inicio del periodo: solo cambia la pareja propuesta
    rot_set_pendiente($rid, $aid, $pendiente['fecha_inicio']);

    ajax_response(true, 'Se propuso una nueva pareja');
    break;
  }

  // El responsable marca que terminó el inventario -> AHORA SÍ se guarda en la tabla
  case 'completar': {
    $pendiente = rot_get_pendiente($pdo);
    if (!$pendiente) {
      ajax_response(false, 'No hay una pareja pendiente para completar');
    }

    $limite = rot_sumar_dias_habiles($pendiente['fecha_inicio'], 15);

    try {
      $stmt = $pdo->prepare(
        "INSERT INTO rotacion_turnos (responsable_id, ayudante_id, fecha_inicio, fecha_limite, estado, fecha_completado)
         VALUES (:r, :a, :fi, :fl, 'completado', NOW())"
      );
      $stmt->execute([
        ':r'  => $pendiente['responsable_id'],
        ':a'  => $pendiente['ayudante_id'],
        ':fi' => $pendiente['fecha_inicio'],
        ':fl' => $limite,
      ]);
    } catch (Throwable $e) {
      ajax_response(false, 'No se pudo guardar el turno completado');
    }

    // Calcula y deja en sesión la SIGUIENTE pareja (todavía sin guardar)
    [$rid, $aid] = rot_siguiente_par($pdo, (int)$pendiente['responsable_id']);
    rot_set_pendiente($rid, $aid, date('Y-m-d'));

    ajax_response(true, 'Turno marcado como completado');
    break;
  }

  // Revierte el último turno GUARDADO (completado), devolviéndolo a "pendiente"
  case 'deshacer': {
    $ultimo = $pdo->query("SELECT * FROM rotacion_turnos ORDER BY id DESC LIMIT 1")->fetch();
    if (!$ultimo) {
      ajax_response(false, 'No hay nada que deshacer');
    }

    try {
      $pdo->prepare("DELETE FROM rotacion_turnos WHERE id=:id")->execute([':id' => $ultimo['id']]);
    } catch (Throwable $e) {
      ajax_response(false, 'No se pudo deshacer el cambio');
    }

    rot_set_pendiente(
      (int)$ultimo['responsable_id'],
      $ultimo['ayudante_id'] !== null ? (int)$ultimo['ayudante_id'] : null,
      $ultimo['fecha_inicio']
    );

    ajax_response(true, 'Se deshizo el último turno completado');
    break;
  }

  default:
    ajax_response(false, 'Acción no reconocida');
}