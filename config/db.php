<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Ajusta tus credenciales
$DB_HOST = '127.0.0.1'; // cambia si usas otro host
$DB_PORT = '3307';      // cambia si usas otro puerto (WAMP 3308, MAMP 8889, etc.)
$DB_NAME = 'oficina_inv';
$DB_USER = 'root';
$DB_PASS = '';

$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo "Error de conexión: ".$e->getMessage();
  exit;
}