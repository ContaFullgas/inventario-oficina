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
$pdf->SetAutoPageBreak(true, 10); // dejar 10mm al final de página
$pdf->AddPage();

// Datos de diseño: anchos de columna (ajusta si quieres)
$w_num  = 10;
$w_img  = 18;
$w_prod = 80;
$w_ubic = 36;
$w_cond = 36;
$w_stock= 16;

// Altura de fila para filas normales (en mm)
$rowHeight = 14; // un poco más alto para la miniatura

// ruta base de uploads (desde este script que está en public/)
$uploads_dir = realpath(__DIR__ . '/../uploads/'); // ajusta si tu carpeta uploads está en otro sitio

// Función para imprimir el encabezado de la tabla (se puede llamar en nueva página)
function imprimir_encabezado(&$pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock) {
    // Título pequeño arriba (solo si es nueva página podrías ponerlo también)
    // Encabezado con color
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(220, 53, 69);  // fondo rojo
    $pdf->SetTextColor(255, 255, 255); // texto blanco
    $pdf->SetDrawColor(0, 0, 0);       // borde negro

    $pdf->Cell($w_num, 7, '#', 1, 0, 'C', true);
    $pdf->Cell($w_img, 7, utf8_decode('Imagen'), 1, 0, 'C', true);
    $pdf->Cell($w_prod, 7, utf8_decode('Producto'), 1, 0, 'L', true);
    $pdf->Cell($w_ubic, 7, utf8_decode('Ubicación'), 1, 0, 'L', true);
    $pdf->Cell($w_cond, 7, utf8_decode('Condición'), 1, 0, 'L', true);
    $pdf->Cell($w_stock,7, 'Stock', 1, 1, 'C', true);

    // Restaurar colores para las filas
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 8);
}

// Imprimir cabecera principal y meta
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, utf8_decode('Inventario contabsistemas - Artículos con existencia'), 0, 1, 'C');

$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, utf8_decode('Generado el '.date('d/m/Y H:i')), 0, 1, 'R');
$pdf->Ln(2);

// Imprimir encabezado de columna por primera vez
imprimir_encabezado($pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock);

// Si no hay items
if (empty($items)) {
    $pdf->Cell(0, 7, utf8_decode('No hay artículos con stock disponible.'), 1, 1, 'C');
} else {
    $i = 1;
    foreach ($items as $it) {
        // Antes de dibujar la fila, comprobar si hay espacio suficiente en la página.
        // Si no hay, crear nueva página y volver a imprimir encabezado.
        $currentY = $pdf->GetY();
        $pageHeight = $pdf->GetPageHeight();
        $bottomMargin = 10; // coincide con SetAutoPageBreak
        if ($currentY + $rowHeight + $bottomMargin > $pageHeight) {
            $pdf->AddPage();
            imprimir_encabezado($pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock);
        }

        // Posición actual (usada para alinear la imagen)
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        // 1) Número
        $pdf->Cell($w_num, $rowHeight, $i++, 1, 0, 'C');

        // 2) Celda de imagen (dibujamos la celda en blanco con borde)
        $pdf->Cell($w_img, $rowHeight, '', 1, 0, 'C');

        // Si hay imagen intentamos colocarla escalada y centrada
        $imgFile = '';
        if (!empty($it['imagen'])) {
            $candidate = $uploads_dir . DIRECTORY_SEPARATOR . $it['imagen'];
            if (file_exists($candidate)) {
                $imgFile = $candidate;
            }
        }

        if ($imgFile !== '') {
            // Calcular tamaño máximo dentro de la celda (mm)
            $maxW = $w_img - 2;      // 1mm padding a cada lado
            $maxH = $rowHeight - 2;  // 1mm padding arriba/abajo

            // Obtener tamaño real (pixeles) para mantener proporción
            $size = @getimagesize($imgFile);
            if ($size && isset($size[0]) && isset($size[1]) && $size[0] > 0 && $size[1] > 0) {
                $origW = $size[0];
                $origH = $size[1];
                $imgRatio = $origW / $origH;
                $cellRatio = $maxW / $maxH;

                if ($imgRatio >= $cellRatio) {
                    // imagen más ancha en proporción -> limitar por ancho
                    $newW = $maxW;
                    $newH = $maxW / $imgRatio;
                } else {
                    // imagen más alta en proporción -> limitar por alto
                    $newH = $maxH;
                    $newW = $maxH * $imgRatio;
                }

                // Calcular posición para centrar la imagen dentro de la celda
                // Nota: la celda de imagen fue dibujada y el cursor está al final de la celda (derecha).
                // Calculamos X basándonos en startX + w_num
                $imgX = $startX + $w_num + (($w_img - $newW) / 2);
                $imgY = $startY + (($rowHeight - $newH) / 2);

                // Insertar imagen con tamaño en mm
                // (si la extensión no es soportada por FPDF, no mostrará)
                $pdf->Image($imgFile, $imgX, $imgY, $newW, $newH);
            }
        } else {
            // No hay imagen: escribir guion centrado dentro de la celda
            // volver a la esquina izquierda de la celda de imagen y escribir
            $pdf->SetXY($startX + $w_num, $startY);
            $pdf->Cell($w_img, $rowHeight, '-', 0, 0, 'C');
            // Restaurar cursor al final de la celda (derecha) para seguir escribiendo la fila
            $pdf->SetXY($startX + $w_num + $w_img, $startY);
        }

        // 3) Producto
        // Usamos substr para evitar que texto desborde; si quieres multilínea, usar MultiCell y controlar alturas.
        $pdf->Cell($w_prod, $rowHeight, utf8_decode(substr($it['nombre'], 0, 80)), 1, 0, 'L');

        // 4) Ubicación
        $pdf->Cell($w_ubic, $rowHeight, utf8_decode(substr($it['ubicacion_nombre'] ?? '', 0, 40)), 1, 0, 'L');

        // 5) Condición
        $pdf->Cell($w_cond, $rowHeight, utf8_decode(substr($it['condicion_nombre'] ?? '', 0, 40)), 1, 0, 'L');

        // 6) Stock y salto de línea
        $pdf->Cell($w_stock, $rowHeight, (string)(int)$it['cantidad'], 1, 1, 'C');
    }
}

// Pie de página opcional: total de artículos (lo ponemos antes de Output)
$pdf->Ln(4);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, utf8_decode('Total de artículos con existencia: ' . count($items)), 0, 1, 'L');

// Forzar descarga
$pdf->Output('D', 'inventario_disponible.pdf');
exit;
