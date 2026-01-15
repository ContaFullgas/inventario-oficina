<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';

auth_check();

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Datos incompletos'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

// Validación mínima
if (empty($_POST['item_id']) || empty($_POST['tipo'])) {
    echo json_encode($response);
    exit;
}

$item_id = (int) $_POST['item_id'];
$tipo = $_POST['tipo'];
$observaciones = $_POST['observaciones'] ?? null;

// Validar tipo
if (!in_array($tipo, ['uso', 'devolucion'], true)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tipo inválido'
    ]);
    exit;
}

// 🔐 Usuario desde la sesión REAL
$auth = auth_user();

if (!$auth || empty($auth['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Usuario no identificado en sesión'
    ]);
    exit;
}

$usuario_id = (int) $auth['id'];

// 🧠 Modelo híbrido: admin puede elegir usuario
if (
    auth_is_admin() &&
    isset($_POST['usuario_id']) &&
    is_numeric($_POST['usuario_id'])
) {
    $usuario_id = (int) $_POST['usuario_id'];
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO historial_items
        (item_id, usuario_id, tipo_movimiento, observaciones)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $item_id,
        $usuario_id,
        $tipo,
        $observaciones
    ]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Movimiento registrado'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al guardar historial'
    ]);
}
