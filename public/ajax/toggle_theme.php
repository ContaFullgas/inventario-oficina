<?php
session_start();
header('Content-Type: application/json');

$theme = $_POST['theme'] ?? 'light';
if (!in_array($theme, ['light', 'dark'], true)) {
    $theme = 'light';
}

$_SESSION['theme'] = $theme;

echo json_encode(['success' => true, 'theme' => $theme]);