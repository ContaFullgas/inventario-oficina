<?php
// config/auth.php
// Helpers de autenticación y roles

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__.'/db.php';
require_once __DIR__.'/util.php';

function auth_login(string $usuario, string $clave): bool {
  // SIN SHA-2: comparación directa de texto plano
  global $pdo;
  $stmt = $pdo->prepare("SELECT id, usuario, clave, rol, activo FROM usuarios WHERE usuario = :u LIMIT 1");
  $stmt->execute([':u' => $usuario]);
  $row = $stmt->fetch();
  if (!$row) return false;
  if ((int)$row['activo'] !== 1) return false;

  if ($clave !== $row['clave']) return false;

  $_SESSION['auth'] = [
    'id'      => (int)$row['id'],
    'usuario' => $row['usuario'],
    'rol'     => $row['rol'],
    'ts'      => time(),
  ];
  return true;
}

function auth_logout(): void {
  $_SESSION['auth'] = null;
  unset($_SESSION['auth']);
  session_regenerate_id(true);
}

function auth_user(): ?array {
  return $_SESSION['auth'] ?? null;
}

function auth_check(): void {
  if (!auth_user()) {
    // flash_set('ok', 'Por favor inicia sesión.');
    // RUTA RELATIVA (no uses /public/login.php)
    header('Location: login.php', true, 302);
    exit;
  }
}

function auth_is_admin(): bool {
  $u = auth_user();
  return $u && ($u['rol'] === 'admin');
}

function auth_require_admin(): void {
  if (!auth_is_admin()) {
    flash_set('ok', 'No tienes permisos para realizar esta acción.');
    // RUTA RELATIVA
    header('Location: index.php?tab=inv#inv', true, 303);
    exit;
  }
}
