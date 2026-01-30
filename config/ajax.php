<?php
// funcion helper global que se utiliza en los archivos cat_ para que no haya conflictos usando uno global
function ajax_response(bool $ok, string $message = ''): void {
  echo json_encode([
    'ok'      => $ok,
    'message' => $message
  ]);
  exit;
}
