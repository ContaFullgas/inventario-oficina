<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../fpdf/fpdf.php';


// Filtros (mismos que inventario.php, pero sin paginación)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase_id = (isset($_GET['clase_id']) && $_GET['clase_id'] !== '') ? (int)$_GET['clase_id'] : null;

// Consulta: SOLO artículos con existencia (cantidad >= 1)
$sql = "SELECT i.*,
               c1.nombre AS clase_nombre,
               c2.nombre AS condicion_nombre,
               c3.nombre AS ubicacion_nombre
        FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE i.cantidad >= 1";
$params = [];

if ($q !== '') {
  $sql .= " AND (i.nombre LIKE :q
              OR c1.nombre LIKE :q
              OR c2.nombre LIKE :q
              OR c3.nombre LIKE :q
              OR IFNULL(i.notas,'') LIKE :q)";
  $params[':q'] = "%$q%";
}
if (!is_null($clase_id)) {
  $sql .= " AND i.clase_id = :cid";
  $params[':cid'] = $clase_id;
}

$sql .= " ORDER BY i.nombre";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
  $stmt->bindValue($k, $v);
}
$stmt->execute();
$items = $stmt->fetchAll();

// ===================
// Generar PDF con FPDF
// ===================

$pdf = new FPDF('P', 'mm', 'Letter');
$pdf->SetTitle('Inventario disponible');
$pdf->AddPage();

// Título
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('Inventario contabsistemas - Artículos con existencia'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode('Generado el '.date('d/m/Y H:i')), 0, 1, 'R');
$pdf->Ln(3);

// ===== ENCABEZADO CON COLOR =====
$pdf->SetFont('Arial', 'B', 9);

// Color del fondo (rojo)
$pdf->SetFillColor(220, 53, 69);  // Rojo tipo Bootstrap "danger"

// Color del texto (blanco)
$pdf->SetTextColor(255, 255, 255);

// Color del borde (negro)
$pdf->SetDrawColor(0, 0, 0);

// Encabezado con relleno
$pdf->Cell(10, 7, '#', 1, 0, 'C', true);
$pdf->Cell(95, 7, utf8_decode('Producto'), 1, 0, 'L', true);
$pdf->Cell(40, 7, utf8_decode('Ubicación'), 1, 0, 'L', true);
$pdf->Cell(35, 7, utf8_decode('Condición'), 1, 0, 'L', true);
$pdf->Cell(16, 7, 'Stock', 1, 1, 'C', true);

// Restaurar colores normales para filas
$pdf->SetTextColor(0, 0, 0);


$pdf->SetFont('Arial', '', 8);

if (empty($items)) {
    $pdf->Cell(0, 7, utf8_decode('No hay artículos con stock disponible.'), 1, 1, 'C');
} else {
    $i = 1;
    foreach ($items as $it) {
        $pdf->Cell(10, 6, $i++, 1, 0, 'C');
        $pdf->Cell(95, 6, utf8_decode(substr($it['nombre'], 0, 60)), 1, 0, 'L');
        $pdf->Cell(40, 6, utf8_decode(substr($it['ubicacion_nombre'] ?? '', 0, 28)), 1, 0, 'L');
        $pdf->Cell(35, 6, utf8_decode(substr($it['condicion_nombre'] ?? '', 0, 25)), 1, 0, 'L');
        $pdf->Cell(16, 6, (string)(int)$it['cantidad'], 1, 1, 'C');
    }
}


// Esta línea envía el PDF forzando descarga:
$pdf->Output('D', 'inventario_disponible.pdf');
exit;
