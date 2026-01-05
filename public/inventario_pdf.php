<?php
//Archivo inventario_pdf.php
// Versión final lista para pegar:
// - paginado ?page=1&per=100
// - thumbnails al vuelo (prioriza GD), temporales limpiados
// - título exacto y fecha/hora debajo del título
// - total calculado automáticamente y mostrado en la última página
// - evita timeouts (útil para XAMPP): set_time_limit(0) + refresco por iteración

// Permitir ejecución más larga (útil en XAMPP / desarrollo)
@ini_set('memory_limit', '256M');    // opcional, ajustar si el host lo permite
@set_time_limit(0);
@ini_set('max_execution_time', '0');

// registro inicial
error_log(date('c') . " - inventario_pdf_split_thumbs_final.php iniciado");

// Cargar DB: ajusta si tu proyecto usa otra forma de conexión
require_once __DIR__ . '/../config/db.php'; // debe definir $pdo (PDO)

// Cargar FPDF (varias rutas de fallback)
$fpdf_loaded = false;
$try_paths = [
    __DIR__ . '/../fpdf/fpdf.php',
    (realpath(__DIR__ . '/../fpdf') ? realpath(__DIR__ . '/../fpdf') . '/fpdf.php' : false),
    __DIR__ . '/fpdf/fpdf.php',
    __DIR__ . '/../vendor/autoload.php'
];
foreach ($try_paths as $p) {
    if (!$p) continue;
    if (is_file($p) && is_readable($p)) {
        if (basename($p) === 'autoload.php') {
            require_once $p;
            if (class_exists('FPDF', false)) { $fpdf_loaded = true; break; }
            continue;
        }
        require_once $p;
        if (class_exists('FPDF', false)) { $fpdf_loaded = true; break; }
    }
}
if (!$fpdf_loaded) {
    error_log("[inventario_pdf_split_thumbs_final] FPDF no disponible");
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "FPDF no disponible. Revisa logs.";
    exit;
}



// Filtros opcionales
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$clase_id = (isset($_GET['clase_id']) && $_GET['clase_id'] !== '') ? (int)$_GET['clase_id'] : null;

// -----------------------------
// 1) Calcular total de existencia (SUM(cantidad)) con filtros
// -----------------------------
try {
    $sumSql = "SELECT COALESCE(SUM(i.cantidad),0) AS total_existencia
               FROM items i
               LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
               LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
               LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
               WHERE i.cantidad >= 1";
    $sumParams = [];
    if ($q !== '') {
        $sumSql .= " AND (i.nombre LIKE :q OR c1.nombre LIKE :q OR c2.nombre LIKE :q OR c3.nombre LIKE :q OR IFNULL(i.notas,'') LIKE :q)";
        $sumParams[':q'] = "%$q%";
    }
    if (!is_null($clase_id)) {
        $sumSql .= " AND i.clase_id = :cid";
        $sumParams[':cid'] = $clase_id;
    }
    $stmt = $pdo->prepare($sumSql);
    foreach ($sumParams as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalExistencia = (int)($row['total_existencia'] ?? 0);
} catch (Exception $e) {
    error_log("[inventario_pdf_split_thumbs_final] Error calculando total existencia: " . $e->getMessage());
    $totalExistencia = 0;
}

// -----------------------------
// 2) Contar total items (para paginación/info)
// -----------------------------
try {
    $countSql = "SELECT COUNT(*) FROM items i
        LEFT JOIN cat_clases c1 ON c1.id = i.clase_id
        LEFT JOIN cat_condiciones c2 ON c2.id = i.condicion_id
        LEFT JOIN cat_ubicaciones c3 ON c3.id = i.ubicacion_id
        WHERE i.cantidad >= 1";
    $countParams = [];
    if ($q !== '') {
        $countSql .= " AND (i.nombre LIKE :q OR c1.nombre LIKE :q OR c2.nombre LIKE :q OR c3.nombre LIKE :q OR IFNULL(i.notas,'') LIKE :q)";
        $countParams[':q'] = "%$q%";
    }
    if (!is_null($clase_id)) {
        $countSql .= " AND i.clase_id = :cid";
        $countParams[':cid'] = $clase_id;
    }
    $stmt = $pdo->prepare($countSql);
    foreach ($countParams as $k => $v) $stmt->bindValue($k, $v);
    $stmt->execute();
    $totalItems = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    error_log("[inventario_pdf_split_thumbs_final] Error contando items: " . $e->getMessage());
    $totalItems = 0;
}


// ---- FORZAR PDF GIGANTE ----
$page = 1;
$per = $totalItems;   // ahora sí existe
$totalPages = 1;
$offset = 0;

// -----------------------------
// 3) Query paginada para los items de esta página
// -----------------------------
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
    $sql .= " AND (i.nombre LIKE :q OR c1.nombre LIKE :q OR c2.nombre LIKE :q OR c3.nombre LIKE :q OR IFNULL(i.notas,'') LIKE :q)";
    $params[':q'] = "%$q%";
}
if (!is_null($clase_id)) {
    $sql .= " AND i.clase_id = :cid";
    $params[':cid'] = $clase_id;
}
$sql .= " ORDER BY i.nombre";

try {
    $stmt = $pdo->prepare($sql);
    if ($q !== '') $stmt->bindValue(':q', "%$q%");
    if (!is_null($clase_id)) $stmt->bindValue(':cid', $clase_id, PDO::PARAM_INT);
    // $stmt->bindValue(':lim', $per, PDO::PARAM_INT);
    // $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("[inventario_pdf_split_thumbs_final] Error consulta paginada: " . $e->getMessage());
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    echo "Error en consulta. Revisa logs.";
    exit;
}

// -----------------------------
// Rutas y configuración
// -----------------------------
$uploads = realpath(__DIR__ . '/../uploads') ?: (__DIR__ . '/../uploads');
$thumbs_dir = rtrim($uploads, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'thumbs';
$temporaryFiles = []; // temporales creados en esta ejecución
$maxSide = 80; // px, ajusta a 60/50 si necesitas aún menor uso

// -----------------------------
// Función: PRIMERO GD, luego Imagick si GD no disponible
// -----------------------------
function create_thumb_temp($srcPath, $maxSide) {
    $tmpPng = false;

    // Intentar GD primero
    if (extension_loaded('gd')) {
        try {
            $info = @getimagesize($srcPath);
            if ($info === false) throw new Exception("getimagesize falló");
            $origW = $info[0]; $origH = $info[1];
            $mime = $info['mime'] ?? '';
            switch ($mime) {
                case 'image/jpeg': $srcImg = @imagecreatefromjpeg($srcPath); break;
                case 'image/png':  $srcImg = @imagecreatefrompng($srcPath); break;
                case 'image/gif':  $srcImg = @imagecreatefromgif($srcPath); break;
                default: $srcImg = false; break;
            }
            if ($srcImg === false) throw new Exception("GD no soporta el formato: $mime");
            if ($origW > $origH) { $newW = $maxSide; $newH = intval($origH * ($maxSide / $origW)); }
            else { $newH = $maxSide; $newW = intval($origW * ($maxSide / $origH)); }
            if ($newW < 1) $newW = 1; if ($newH < 1) $newH = 1;
            $thumb = imagecreatetruecolor($newW, $newH);
            $white = imagecolorallocate($thumb, 255,255,255);
            imagefill($thumb, 0,0, $white);
            imagecopyresampled($thumb, $srcImg, 0,0,0,0, $newW, $newH, $origW, $origH);
            $tmp = tempnam(sys_get_temp_dir(), 'inv_tmb_');
            if ($tmp === false) { imagedestroy($srcImg); imagedestroy($thumb); throw new Exception("tempnam falló"); }
            $tmpPng = $tmp . '.png';
            imagepng($thumb, $tmpPng, 6);
            imagedestroy($srcImg);
            imagedestroy($thumb);
            if (file_exists($tmp)) @unlink($tmp);
            gc_collect_cycles();
            return $tmpPng;
        } catch (Exception $e) {
            error_log("[create_thumb_temp] GD falló para $srcPath: " . $e->getMessage());
            if (isset($srcImg) && is_resource($srcImg)) @imagedestroy($srcImg);
            if (isset($thumb) && is_resource($thumb)) @imagedestroy($thumb);
            if ($tmpPng && is_file($tmpPng)) @unlink($tmpPng);
            gc_collect_cycles();
        }
    }

    // Fallback Imagick
    if (extension_loaded('imagick')) {
        $im = null;
        try {
            $im = new Imagick();
            if (defined('Imagick::RESOURCETYPE_MEMORY')) {
                $im->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, 48 * 1024 * 1024);
                $im->setResourceLimit(Imagick::RESOURCETYPE_MAP,    96 * 1024 * 1024);
                $im->setResourceLimit(Imagick::RESOURCETYPE_DISK,   128 * 1024 * 1024);
                $im->setResourceLimit(Imagick::RESOURCETYPE_THREADS,1);
            }
            $im->readImage($srcPath);
            if ($im->getImageAlphaChannel()) {
                $bg = new Imagick();
                $bg->newImage($im->getImageWidth(), $im->getImageHeight(), 'white');
                $bg->compositeImage($im, Imagick::COMPOSITE_DEFAULT, 0,0);
                $im->clear(); $im->destroy();
                $im = $bg;
            }
            $im->thumbnailImage($maxSide, $maxSide, true, true);
            $im->setImageFormat('png');
            $tmp = tempnam(sys_get_temp_dir(), 'inv_tmb_');
            if ($tmp === false) throw new Exception("tempnam falló");
            $tmpPng = $tmp . '.png';
            if (!$im->writeImage($tmpPng)) {
                if (file_exists($tmpPng)) @unlink($tmpPng);
                if (file_exists($tmp)) @unlink($tmp);
                throw new Exception("no se pudo escribir tmp png");
            }
            if (file_exists($tmp)) @unlink($tmp);
            $im->clear(); $im->destroy();
            unset($im);
            gc_collect_cycles();
            return $tmpPng;
        } catch (Exception $e) {
            error_log("[create_thumb_temp] Imagick falló para $srcPath: " . $e->getMessage());
            if (is_object($im)) { try { $im->clear(); $im->destroy(); } catch (Throwable $_) {} }
            if ($tmpPng && is_file($tmpPng)) @unlink($tmpPng);
            unset($im);
            gc_collect_cycles();
            return false;
        }
    }

    error_log("[create_thumb_temp] Ni GD ni Imagick disponibles/útiles para $srcPath");
    return false;
}

// -----------------------------
// Preparar PDF con FPDF
// -----------------------------
$pdf = new FPDF('P','mm','Letter');
$pdf->SetTitle('Inventario contabsistemas - Artículos con existencia');
$pdf->SetAutoPageBreak(true,10);
$pdf->AddPage();

// Título pedido
$pdf->SetFont('Arial','B',14);
$pdf->Cell(0,8, utf8_decode('Inventario contabsistemas - Artículos con existencia'), 0, 1, 'C');

// Fecha/hora generada debajo del título (formato dd/mm/YYYY HH:ii)
$generated = date('d/m/Y H:i');
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,5, utf8_decode('Generado el '.$generated), 0, 1, 'R');
$pdf->Ln(2);

// Encabezado tabla
$w_num=10; $w_img=18; $w_prod=80; $w_ubic=36; $w_cond=36; $w_stock=16; $rowH=14;
function imprimir_encabezado(&$pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock) {
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(220,53,69);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetDrawColor(0,0,0);
    $pdf->Cell($w_num,7,'#',1,0,'C',true);
    $pdf->Cell($w_img,7,utf8_decode('Imagen'),1,0,'C',true);
    $pdf->Cell($w_prod,7,utf8_decode('Producto'),1,0,'L',true);
    $pdf->Cell($w_ubic,7,utf8_decode('Ubicación'),1,0,'L',true);
    $pdf->Cell($w_cond,7,utf8_decode('Condición'),1,0,'L',true);
    $pdf->Cell($w_stock,7,'Stock',1,1,'C',true);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);
}
imprimir_encabezado($pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock);

// -----------------------------
// Generar filas (creando thumbs temporales solo para estos items)
// -----------------------------
if (empty($items)) {
    $pdf->Cell(0,7,utf8_decode('No hay artículos en esta página.'),1,1,'C');
} else {
    $i = $offset + 1;
    foreach ($items as $it) {
        // refrescar contador por iteración para prevenir timeouts parciales
        if (function_exists('set_time_limit')) {
            @set_time_limit(30); // da 30s extra para esta iteración
        }

        if ($pdf->GetY() + $rowH + 10 > $pdf->GetPageHeight()) {
            $pdf->AddPage();
            imprimir_encabezado($pdf, $w_num, $w_img, $w_prod, $w_ubic, $w_cond, $w_stock);
        }
        $startX = $pdf->GetX(); $startY = $pdf->GetY();

        $pdf->Cell($w_num,$rowH,$i++,1,0,'C');
        $pdf->Cell($w_img,$rowH,'',1,0,'C'); // dibuja la celda

        // seleccionar ruta original de imagen o thumb permanente si existe
        $thumbFile = '';
        if (!empty($it['imagen'])) {
            $candidate = rtrim($uploads, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($it['imagen']);
            $pref_thumb = $thumbs_dir . DIRECTORY_SEPARATOR . pathinfo($it['imagen'], PATHINFO_FILENAME) . '.png';
            if (is_file($pref_thumb) && is_readable($pref_thumb)) {
                $thumbFile = $pref_thumb;
            } elseif (is_file($candidate) && is_readable($candidate)) {
                $tmp = create_thumb_temp($candidate, $maxSide);
                if ($tmp !== false && is_file($tmp) && is_readable($tmp)) {
                    $thumbFile = $tmp;
                    $temporaryFiles[] = $tmp;
                } else {
                    $thumbFile = '';
                }
            } else {
                $thumbFile = '';
            }
        }

        if ($thumbFile !== '') {
            $size = @getimagesize($thumbFile);
            if ($size && $size[0] > 0 && $size[1] > 0) {
                $maxW = $w_img - 2; $maxH = $rowH - 2;
                $origW = $size[0]; $origH = $size[1];
                $imgRatio = $origW / $origH; $cellRatio = $maxW / $maxH;
                if ($imgRatio >= $cellRatio) { $newW = $maxW; $newH = $maxW / $imgRatio; } else { $newH = $maxH; $newW = $maxH * $imgRatio; }
                $imgX = $startX + $w_num + (($w_img - $newW)/2);
                $imgY = $startY + (($rowH - $newH)/2);
                try {
                    $pdf->Image($thumbFile, $imgX, $imgY, $newW, $newH);
                } catch (Exception $e) {
                    error_log("[inventario_pdf_split_thumbs_final] Image error: ".$e->getMessage());
                    $thumbFile = '';
                }
            } else {
                $thumbFile = '';
            }
        }

        if ($thumbFile === '') {
            $pdf->SetXY($startX + $w_num, $startY);
            $pdf->Cell($w_img, $rowH, '-', 0, 0, 'C');
            $pdf->SetXY($startX + $w_num + $w_img, $startY);
        } else {
            $pdf->SetXY($startX + $w_num + $w_img, $startY);
        }

        // resto columnas
        $pdf->Cell($w_prod,$rowH,utf8_decode(substr($it['nombre'],0,80)),1,0,'L');
        $pdf->Cell($w_ubic,$rowH,utf8_decode(substr($it['ubicacion_nombre'] ?? '',0,40)),1,0,'L');
        $pdf->Cell($w_cond,$rowH,utf8_decode(substr($it['condicion_nombre'] ?? '',0,40)),1,0,'L');
        $pdf->Cell($w_stock,$rowH,(string)(int)$it['cantidad'],1,1,'C');

        if (isset($size)) unset($size);
        gc_collect_cycles();
    }
}

// -----------------------------
// Pie: solo en la última página mostrar totalExistencia
// -----------------------------
if ($page === $totalPages) {
    $pdf->Ln(6);
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(0,6, utf8_decode('Total de artículos con existencia: ' . $totalExistencia), 0, 1, 'R');
}

// limpiar temporales creados en esta ejecución
if (!empty($temporaryFiles)) {
    foreach ($temporaryFiles as $tf) {
        if (is_file($tf)) @unlink($tf);
    }
    error_log("[inventario_pdf_split_thumbs_final] temporales eliminados: " . count($temporaryFiles));
}

$pdf->Ln(4);
$pdf->SetFont('Arial','I',8);
$pdf->Cell(0,5,utf8_decode("Total de artículos: $totalItems"),0,1,'L');

// Forzar descarga
$pdf->Output('D','inventario_disponible_page_'.$page.'.pdf');
exit;
