<?php
require_once __DIR__ . '/../bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Build and return a Spreadsheet object filled from $data and $mappingFile.
 * DOES NOT write any files. Consumers can save as XLS/PDF/etc.
 */
function rireki_build_spreadsheet(array $data, string $mappingFile): Spreadsheet {
  if (!is_readable($mappingFile)) {
    throw new RuntimeException("Mapping not readable: $mappingFile");
  }
  $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

  // Resolve template path (relative to mappings/)
  $tplRel  = $map['template_file'] ?? '';
  $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
  if (!$tplPath || !is_readable($tplPath)) {
    throw new RuntimeException("Template not readable: $tplRel");
  }

  // Load template + pick sheet
  $spreadsheet = IOFactory::load($tplPath);
  $sheet = $spreadsheet->getActiveSheet();
  if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
    $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
    $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
  }

  // Split "today" into cells (year, month, day)
  if (!empty($map['date_now_split'])) {
    $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
    $sheet->setCellValue($map['date_now_split']['year_cell']  ?? 'AC2', (int)$dt->format('Y'));
    $sheet->setCellValue($map['date_now_split']['month_cell'] ?? 'AF2', (int)$dt->format('n'));
    $sheet->setCellValue($map['date_now_split']['day_cell']   ?? 'AH2', (int)$dt->format('j'));
  }

  // Singles
  if (!empty($map['singles'])) {
    foreach ($map['singles'] as $key => $cell) {
      if (array_key_exists($key, $data)) {
        $sheet->setCellValue($cell, (string)$data[$key]);
      }
    }
  }

  // Repeaters (with {row}, {row_plus_1}, {row_plus_2})
  if (!empty($map['repeaters'])) {
    foreach ($map['repeaters'] as $repKey => $repConf) {
      $rows = $data[$repKey] ?? [];
      if (!is_array($rows)) continue;
      $startRow = (int)($repConf['start_row'] ?? 0);
      $rowStep  = (int)($repConf['row_step'] ?? 1);
      $maxRows  = (int)($repConf['max_rows'] ?? count($rows));
      $cols     = $repConf['columns'] ?? [];
      $limit    = min(count($rows), $maxRows);

      for ($i=0; $i<$limit; $i++) {
        $row = $rows[$i] ?? [];
        $r = $startRow + ($i * $rowStep);
        foreach ($cols as $field => $tpl) {
          $coord = str_replace(['{row}','{row_plus_1}','{row_plus_2}'], [$r, $r+1, $r+2], $tpl);
          $sheet->setCellValue($coord, (string)($row[$field] ?? ''));
        }
      }
    }
  }

  // Composed rules (e.g., age from DOB)
  if (!empty($map['composed_rules'])) {
    foreach ($map['composed_rules'] as $rule) {
      if (($rule['calc'] ?? '') === 'age_from_date') {
        $yAddr = $rule['source']['year']  ?? null;
        $mAddr = $rule['source']['month'] ?? null;
        $dAddr = $rule['source']['day']   ?? null;
        $target= $rule['target']          ?? null;
        if ($yAddr && $mAddr && $dAddr && $target) {
          $sy = trim((string)$sheet->getCell($yAddr)->getValue());
          $sm = trim((string)$sheet->getCell($mAddr)->getValue());
          $sd = trim((string)$sheet->getCell($dAddr)->getValue());
          if ($sy !== '' && $sm !== '' && $sd !== '') {
            $dob = DateTime::createFromFormat('Y-n-j', "$sy-$sm-$sd");
            if ($dob) {
              $age = $dob->diff(new DateTime('now', new DateTimeZone('Asia/Tokyo')))->y;
              $sheet->setCellValue($target, $age);
            }
          }
        }
      }
    }
  }

  // Photo fit (anchor AD3; merged range auto-detected, e.g., AD3:AI8) with 3px padding
  if (!empty($map['photo']) && !empty($data['photo_path'])) {
    $ph = $data['photo_path'];
    if (is_readable($ph)) {
      $anchor = $map['photo']['anchor_cell'] ?? 'AD3';
      [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
      [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
      [$imgW,$imgH]     = @getimagesize($ph) ?: [600,800];

      $maxW = isset($map['photo']['max_width_px'])  ? (int)$map['photo']['max_width_px']  : $boxW;
      $maxH = isset($map['photo']['max_height_px']) ? (int)$map['photo']['max_height_px'] : $boxH;
      $boxW = min($boxW, $maxW);
      $boxH = min($boxH, $maxH);

      // 3px padding all around to keep cell border visible
      $pad = 3;
      $boxW = max(1, $boxW - 2*$pad);
      $boxH = max(1, $boxH - 2*$pad);

      $scale = min(max($boxW,1)/$imgW, max($boxH,1)/$imgH);
      $newW = (int)floor($imgW * $scale);
      $newH = (int)floor($imgH * $scale);
      $offX = max(0, (int)floor(($boxW - $newW)/2)) + $pad;
      $offY = max(0, (int)floor(($boxH - $newH)/2)) + $pad;

      $img = new Drawing();
      $img->setName('Photo'); $img->setDescription('Applicant Photo'); $img->setPath($ph);
      $img->setResizeProportional(true);
      $img->setWidthAndHeight($newW, $newH);
      $img->setCoordinates(Coordinate::stringFromColumnIndex($c1) . $r1);
      $img->setOffsetX($offX); $img->setOffsetY($offY);
      $img->setWorksheet($sheet);
    }
  }

  // Page setup
  $ps = $sheet->getPageSetup();
  $ps->setPaperSize(PageSetup::PAPERSIZE_B5)
     ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
     ->setFitToWidth(1)->setFitToHeight(1)
     ->setHorizontalCentered(true);
  $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
  $sheet->setShowGridlines(false);
  $sheet->setPrintGridlines(false);

  return $spreadsheet;
}

/**
 * Quick XLS writer (kept for your existing flow).
 * Returns: ['ok'=>bool,'xls'=>?string,'html_left'=>'','html_right'=>'','err'=>?string]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    $spreadsheet = rireki_build_spreadsheet($data, $mappingFile);

    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    IOFactory::createWriter($spreadsheet, 'Xls')->save($xlsPath);

    return ['ok'=>true, 'xls'=>$xlsPath, 'html_left'=>'', 'html_right'=>'', 'err'=>null];
  } catch (\Throwable $e) {
    return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
  }
}

/**
 * Build PDF by rendering the sheet region to HTML and using mPDF (no PdfWriter).
 * Returns ['ok'=>bool,'pdf'=>?string,'err'=>?string]
 */
function rireki_make_pdf_via_html(array $data, string $mappingFile, string $outDir, string $token, string $tmpDir): array {
  try {
    if (!class_exists('\\Mpdf\\Mpdf')) {
      throw new RuntimeException('mPDF library not available (composer require mpdf/mpdf).');
    }

    $spreadsheet = rireki_build_spreadsheet($data, $mappingFile);
    $sheet = $spreadsheet->getActiveSheet();

    // Build photo overlay for HTML preview (data URI)
    $overlay = null;
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);
    if (!empty($map['photo']) && !empty($data['photo_path']) && is_readable($data['photo_path'])) {
      $anchor = $map['photo']['anchor_cell'] ?? 'AD3';
      [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
      [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
      [$imgW,$imgH]     = @getimagesize($data['photo_path']) ?: [600,800];

      $pad = 3;
      $boxW = max(1, $boxW - 2*$pad);
      $boxH = max(1, $boxH - 2*$pad);

      $scale = min(max($boxW,1)/$imgW, max($boxH,1)/$imgH);
      $newW = (int)floor($imgW * $scale);
      $newH = (int)floor($imgH * $scale);
      $offX = max(0, (int)floor(($boxW - $newW)/2)) + $pad + 2; // +2 for TD padding
      $offY = max(0, (int)floor(($boxH - $newH)/2)) + $pad;

      $gi = @getimagesize($data['photo_path']); $mime = ($gi && !empty($gi['mime'])) ? $gi['mime'] : 'image/jpeg';
      $bin = @file_get_contents($data['photo_path']);
      if ($bin !== false) {
        $overlay = [
          'addr'   => Coordinate::stringFromColumnIndex($c1) . $r1,
          'src'    => 'data:' . $mime . ';base64,' . base64_encode($bin),
          'width'  => $newW,
          'height' => $newH,
          'offX'   => $offX,
          'offY'   => $offY,
        ];
      }
    }

    // Render two pages: A1–O86 and P1–X86 (same as your previews)
    $htmlLeft  = renderSheetRegionHtml($sheet, 'A','O', 1,86, $overlay);
    $htmlRight = renderSheetRegionHtml($sheet, 'P','X', 1,86, $overlay);

    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

    $mpdf = new \Mpdf\Mpdf([
      'tempDir' => $tmpDir,
      'format'  => 'B5',
      'orientation' => 'P',
      'autoScriptToLang' => true,
      'autoLangToFont'   => true,
      'margin_top'    => 8,
      'margin_bottom' => 8,
      'margin_left'   => 8,
      'margin_right'  => 8,
    ]);

    $css = <<<CSS
      body { font-family: DejaVu Sans, ipag, "Noto Sans CJK JP", sans-serif; }
      .pagewrap { width: 100%; }
CSS;

    $mpdf->WriteHTML('<style>'.$css.'</style>', \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML('<div class="pagewrap">'.$htmlLeft.'</div>', \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->AddPage();
    $mpdf->WriteHTML('<div class="pagewrap">'.$htmlRight.'</div>', \Mpdf\HTMLParserMode::HTML_BODY);

    $pdfPath = rtrim($outDir,'/') . '/' . $token . '.pdf';
    $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

    if (!is_readable($pdfPath) || filesize($pdfPath) === 0) {
      throw new RuntimeException('Failed to produce PDF file.');
    }
    return ['ok'=>true, 'pdf'=>$pdfPath, 'err'=>null];

  } catch (\Throwable $e) {
    // optional: write a log here if you want
    return ['ok'=>false, 'pdf'=>null, 'err'=>$e->getMessage()];
  }
}

/** ================= HTML render helpers ================= */
function renderSheetRegionHtml(Worksheet $sheet, string $colStartLetter, string $colEndLetter, int $rowStart, int $rowEnd, ?array $overlay = null): string {
  $cStart = Coordinate::columnIndexFromString(strtoupper($colStartLetter));
  $cEnd   = Coordinate::columnIndexFromString(strtoupper($colEndLetter));

  // Column widths (px)
  $colPx = [];
  $defaultColW = $sheet->getDefaultColumnDimension()->getWidth();
  if ($defaultColW === null || $defaultColW <= 0) $defaultColW = 8.43;
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $w = $dim && $dim->getWidth() !== null ? (float)$dim->getWidth() : (float)$defaultColW;
    $colPx[$c] = excelColWidthToPx($w);
  }

  // Row heights (px)
  $rowPx = [];
  $defaultRowPt = $sheet->getDefaultRowDimension()->getRowHeight();
  if ($defaultRowPt === -1 || $defaultRowPt === null) $defaultRowPt = 15;
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $dim = $sheet->getRowDimension($r);
    $pt = $dim && $dim->getRowHeight() > 0 ? (float)$dim->getRowHeight() : (float)$defaultRowPt;
    $rowPx[$r] = pointsToPx($pt);
  }

  // Merges
  [$mergeTop, $mergeCovered] = buildMergeMaps($sheet, $cStart, $cEnd, $rowStart, $rowEnd);

  $html = [];
  $html[] = '<div style="display:inline-block;background:#fff;position:relative;">';
  $html[] = '<table style="border-collapse:collapse;table-layout:fixed;">';
  $html[] = '<colgroup>';
  for ($c = $cStart; $c <= $cEnd; $c++) $html[] = '<col style="width:' . (int)$colPx[$c] . 'px;">';
  $html[] = '</colgroup>';

  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $html[] = '<tr style="height:' . (int)$rowPx[$r] . 'px;">';
    for ($c = $cStart; $c <= $cEnd; $c++) {
      if (isset($mergeCovered[$r][$c])) continue;
      $rs = 1; $cs = 1;
      if (isset($mergeTop[$r][$c])) { [$rs, $cs] = $mergeTop[$r][$c]; }

      // Borders: only if template has borders
      $stylePieces = [];
      $bTop    = getCellBorderSideCss($sheet, $r,           $c,           'top');
      $bLeft   = getCellBorderSideCss($sheet, $r,           $c,           'left');
      $bRight  = getCellBorderSideCss($sheet, $r,           $c+$cs-1,     'right');
      $bBottom = getCellBorderSideCss($sheet, $r+$rs-1,     $c,           'bottom');
      if ($bTop)    $stylePieces[] = "border-top:{$bTop}";
      if ($bRight)  $stylePieces[] = "border-right:{$bRight}";
      if ($bBottom) $stylePieces[] = "border-bottom:{$bBottom}";
      if ($bLeft)   $stylePieces[] = "border-left:{$bLeft}";

      $attrs = ' style="padding:0 2px;vertical-align:top;overflow:hidden;white-space:pre-wrap;line-height:1.15;'
             . implode(';', $stylePieces) . (empty($stylePieces)?'':';') . '"';

      $extra = '';
      if ($rs > 1) $extra .= ' rowspan="'.$rs.'"';
      if ($cs > 1) $extra .= ' colspan="'.$cs.'"';

      $addr = Coordinate::stringFromColumnIndex($c) . $r;
      $cell = $sheet->getCell($addr);
      $v = $cell ? $cell->getValue() : '';
      if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) $v = $v->getPlainText();
      $val = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

      // Inject photo overlay if this is the anchor top-left cell
      if ($overlay && $overlay['addr'] === $addr) {
        $img = '<div style="position:relative;width:100%;height:100%;">'
             . '<img alt="photo" src="'.htmlspecialchars($overlay['src'],ENT_QUOTES,'UTF-8').'" '
             . 'style="position:absolute;left:'.$overlay['offX'].'px;top:'.$overlay['offY'].'px;'
             . 'width:'.$overlay['width'].'px;height:'.$overlay['height'].'px;object-fit:cover;border:0;"/>'  // 3px padding handled in offsets
             . '</div>';
        $val = $img . $val;
      }

      $html[] = '<td'.$extra.$attrs.'>'.$val.'</td>';
    }
    $html[] = '</tr>';
  }
  $html[] = '</table></div>';
  return implode('', $html);
}

function buildMergeMaps(Worksheet $sheet, int $cStart, int $cEnd, int $rStart, int $rEnd): array {
  $mergeTop = []; $covered  = [];
  foreach ($sheet->getMergeCells() as $range) {
    [$start, $end] = Coordinate::rangeBoundaries($range);
    [$c1, $r1] = $start; [$c2, $r2] = $end;

    $ic1 = max($c1, $cStart);
    $ir1 = max($r1, $rStart);
    $ic2 = min($c2, $cEnd);
    $ir2 = min($r2, $rEnd);
    if ($ic1 > $ic2 || $ir1 > $ir2) continue;

    $rowspan = $ir2 - $ir1 + 1;
    $colspan = $ic2 - $ic1 + 1;
    $mergeTop[$ir1][$ic1] = [$rowspan, $colspan];
    for ($r = $ir1; $r <= $ir2; $r++) {
      for ($c = $ic1; $c <= $ic2; $c++) {
        if (!($r===$ir1 && $c===$ic1)) $covered[$r][$c] = true;
      }
    }
  }
  return [$mergeTop, $covered];
}

function getCellBorderSideCss(Worksheet $sheet, int $row, int $col, string $side): string {
  $addr = Coordinate::stringFromColumnIndex($col) . $row;
  $style = $sheet->getStyle($addr)->getBorders();
  switch ($side) {
    case 'top':    $b = $style->getTop();    break;
    case 'right':  $b = $style->getRight();  break;
    case 'bottom': $b = $style->getBottom(); break;
    default:       $b = $style->getLeft();   break;
  }
  $type = $b->getBorderStyle();
  if ($type === Border::BORDER_NONE || !$type) return '';
  [$w, $s] = mapBorderType($type);
  $rgb = $b->getColor() ? strtoupper($b->getColor()->getRGB()) : '000000';
  if (!$rgb || strlen($rgb) !== 6) $rgb = '000000';
  return "{$w} {$s} #" . strtolower($rgb);
}
function mapBorderType(string $type): array {
  switch ($type) {
    case Border::BORDER_THICK:   return ['3px', 'solid'];
    case Border::BORDER_MEDIUM:  return ['2px', 'solid'];
    case Border::BORDER_THIN:    return ['1px', 'solid'];
    case Border::BORDER_DOUBLE:  return ['3px', 'double'];
    case Border::BORDER_DASHED:  return ['1px', 'dashed'];
    case Border::BORDER_DOTTED:  return ['1px', 'dotted'];
    case Border::BORDER_HAIR:    return ['1px', 'solid'];
    case Border::BORDER_MEDIUMDASHED:     return ['2px', 'dashed'];
    case Border::BORDER_DASHDOT:          return ['1px', 'dashed'];
    case Border::BORDER_MEDIUMDASHDOT:    return ['2px', 'dashed'];
    case Border::BORDER_DASHDOTDOT:       return ['1px', 'dashed'];
    case Border::BORDER_MEDIUMDASHDOTDOT: return ['2px', 'dashed'];
    case Border::BORDER_SLANTDASHDOT:     return ['1px', 'dashed'];
    default: return ['1px', 'solid'];
  }
}

/** ===== Photo region helpers ===== */
function findMergedBox(Worksheet $sheet, string $anchorCell): array {
  $colLetters = preg_replace('/\d+$/','',$anchorCell);
  $rowNumber  = (int)preg_replace('/^\D+/','',$anchorCell);
  $ac = Coordinate::columnIndexFromString($colLetters);
  $ar = $rowNumber;

  foreach ($sheet->getMergeCells() as $range) {
    [$start, $end] = Coordinate::rangeBoundaries($range);
    [$c1, $r1] = $start; [$c2, $r2] = $end;
    if ($ac >= $c1 && $ac <= $c2 && $ar >= $r1 && $ar <= $r2) {
      return [$c1,$r1,$c2,$r2];
    }
  }
  // not merged: use the single cell as box
  return [$ac,$ar,$ac,$ar];
}
function regionPixelSize(Worksheet $sheet, int $c1, int $c2, int $r1, int $r2): array {
  $defaultColW = $sheet->getDefaultColumnDimension()->getWidth();
  if ($defaultColW === null || $defaultColW <= 0) $defaultColW = 8.43;
  $wPx = 0;
  for ($c=$c1; $c<=$c2; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $w = $dim && $dim->getWidth() !== null ? (float)$dim->getWidth() : (float)$defaultColW;
    $wPx += excelColWidthToPx($w);
  }
  $defaultRowPt = $sheet->getDefaultRowDimension()->getRowHeight();
  if ($defaultRowPt === -1 || $defaultRowPt === null) $defaultRowPt = 15;
  $hPx = 0;
  for ($r=$r1; $r<=$r2; $r++) {
    $dim = $sheet->getRowDimension($r);
    $pt = $dim && $dim->getRowHeight() > 0 ? (float)$dim->getRowHeight() : (float)$defaultRowPt;
    $hPx += pointsToPx($pt);
  }
  return [$wPx, $hPx];
}

/** ===== Units ===== */
function excelColWidthToPx(float $widthChars): int {
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int { return (int)round($pt * 96 / 72); }
