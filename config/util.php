<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
}
function check_csrf(): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']);
    if (!$ok) {
      http_response_code(400);
      echo "CSRF inválido";
      exit;
    }
  }
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function flash_set(string $key, string $message): void {
  if (session_status() === PHP_SESSION_NONE) session_start();
  $_SESSION['_flash'][$key] = $message;
}
function flash_get(string $key): ?string {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (!empty($_SESSION['_flash'][$key])) {
    $m = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $m;
  }
  return null;
}
