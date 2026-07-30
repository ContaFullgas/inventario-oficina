<?php
// Archivo: config/rotacion.php
// Lógica compartida de la rotación de turnos de inventario.
// La usan public/rotacion.php (vista) y public/ajax/rotacion_accion.php (acciones).
//
// Importante: la pareja "pendiente" (la que se está proponiendo) NO se guarda
// en la base de datos. Vive únicamente en $_SESSION hasta que el responsable
// marca el inventario como completado; solo ahí se inserta una fila permanente
// en rotacion_turnos.

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Usuarios elegibles para la rotación: activos y que NO tengan rol admin
function rot_usuarios_activos(PDO $pdo): array {
  return $pdo->query(
    "SELECT id, usuario FROM usuarios WHERE activo=1 AND rol <> 'admin' ORDER BY id ASC"
  )->fetchAll();
}

// Suma días hábiles (lunes a viernes) a una fecha
function rot_sumar_dias_habiles(string $fechaInicio, int $dias): string {
  $fecha = new DateTime($fechaInicio);
  $agregados = 0;
  while ($agregados < $dias) {
    $fecha->modify('+1 day');
    if ((int)$fecha->format('N') <= 5) { // 1..5 = lunes..viernes
      $agregados++;
    }
  }
  return $fecha->format('Y-m-d');
}

// Calcula la siguiente pareja (responsable, ayudante) a partir del último responsable conocido.
// Avanza 2 posiciones (pareja completamente nueva) sobre la lista de usuarios elegibles.
function rot_siguiente_par(PDO $pdo, ?int $ultimoResponsableId): array {
  $usuarios = rot_usuarios_activos($pdo);
  $total = count($usuarios);

  if ($total < 2) {
    return [null, null];
  }

  $idx = 0;
  if ($ultimoResponsableId !== null) {
    foreach ($usuarios as $i => $u) {
      if ((int)$u['id'] === $ultimoResponsableId) {
        $idx = ($i + 2) % $total;
        break;
      }
    }
  }

  $responsable = $usuarios[$idx];
  $ayudante    = $usuarios[($idx + 1) % $total];

  return [(int)$responsable['id'], (int)$ayudante['id']];
}

function rot_nombre_usuario(PDO $pdo, ?int $id): ?string {
  if (!$id) return null;
  $stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE id=:id");
  $stmt->execute([':id' => $id]);
  $r = $stmt->fetch();
  return $r ? $r['usuario'] : null;
}

// Devuelve la pareja PENDIENTE actual (aún no guardada en la tabla).
// Si no existe en sesión, la recalcula a partir del último turno guardado (o desde cero).
function rot_get_pendiente(PDO $pdo): ?array {
  if (isset($_SESSION['rot_pendiente'])) {
    return $_SESSION['rot_pendiente'];
  }

  $ultimo = $pdo->query("SELECT * FROM rotacion_turnos ORDER BY id DESC LIMIT 1")->fetch();
  $ultimoResponsableId = $ultimo ? (int)$ultimo['responsable_id'] : null;

  [$rid, $aid] = rot_siguiente_par($pdo, $ultimoResponsableId);
  if (!$rid) {
    return null; // no hay suficientes usuarios elegibles
  }

  $_SESSION['rot_pendiente'] = [
    'responsable_id' => $rid,
    'ayudante_id'    => $aid,
    'fecha_inicio'   => date('Y-m-d'),
  ];

  return $_SESSION['rot_pendiente'];
}

// Guarda (o limpia, si $responsableId es null) la pareja pendiente en sesión
function rot_set_pendiente(?int $responsableId, ?int $ayudanteId, ?string $fechaInicio = null): void {
  if ($responsableId === null) {
    unset($_SESSION['rot_pendiente']);
    return;
  }
  $_SESSION['rot_pendiente'] = [
    'responsable_id' => $responsableId,
    'ayudante_id'    => $ayudanteId,
    'fecha_inicio'   => $fechaInicio ?? date('Y-m-d'),
  ];
}