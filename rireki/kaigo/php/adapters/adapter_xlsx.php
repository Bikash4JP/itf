<?php
require_once __DIR__ . '/../bootstrap.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Build and save only the XLS (BIFF8). Keeps template fidelity.
 * Returns: ['ok'=>bool, 'xls'=>?string, 'err'=>?string]
 */
function rireki_render_xls_only(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'xls'=>null, 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // Resolve template
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'xls'=>null, 'err'=>"Template not readable: $tplRel"];
    }

    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // Date split to AC2/AF2/AH2 (from mapping)
    if (!empty($map['date_now_split'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $sheet->setCellValue($map['date_now_split']['year_cell'],  (int)$dt->format('Y'));
      $sheet->setCellValue($map['date_now_split']['month_cell'], (int)$dt->format('n'));
      $sheet->setCellValue($map['date_now_split']['day_cell'],   (int)$dt->format('j'));
    }

    // Singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        if (!array_key_exists($key,$data)) continue;
        $sheet->setCellValue($cell, (string)$data[$key]);
      }
    }

    // Repeaters with {row}
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
          $row = $rows[$i];
          $r = $startRow + ($i * $rowStep);
          foreach ($cols as $field => $tpl) {
            $coord = str_replace(['{row}','{row_plus_1}','{row_plus_2}'], [$r, $r+1, $r+2], $tpl);
            $sheet->setCellValue($coord, (string)($row[$field] ?? ''));
          }
        }
      }
    }

    // Composed rules (e.g., age_from_dob)
    if (!empty($map['composed_rules'])) {
      foreach ($map['composed_rules'] as $rule) {
        if (($rule['calc'] ?? '') === 'age_from_date') {
          $yAddr = $rule['source']['year']  ?? null;
          $mAddr = $rule['source']['month'] ?? null;
          $dAddr = $rule['source']['day']   ?? null;
          $target= $rule['target']          ?? null;
          if ($yAddr && $mAddr && $dAddr && $target) {
            $sy = (string)$sheet->getCell($yAddr)->getValue();
            $sm = (string)$sheet->getCell($mAddr)->getValue();
            $sd = (string)$sheet->getCell($dAddr)->getValue();
            if ($sy!=='' && $sm!=='' && $sd!=='') {
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

    // Photo: anchor at AD3 (e.g., merged AD3:AI8) and fit inside with 3px padding to show border
    if (!empty($map['photo']) && !empty($data['photo_path']) && is_readable($data['photo_path'])) {
      $anchor = $map['photo']['anchor_cell'] ?? 'AD3';
      [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
      [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
      [$imgW,$imgH]     = @getimagesize($data['photo_path']) ?: [600,800];

      $pad  = 3;
      $maxW = isset($map['photo']['max_width_px'])  ? min((int)$map['photo']['max_width_px'],  max(1,$boxW-2*$pad)) : max(1,$boxW-2*$pad);
      $maxH = isset($map['photo']['max_height_px']) ? min((int)$map['photo']['max_height_px'], max(1,$boxH-2*$pad)) : max(1,$boxH-2*$pad);

      $scale = min($maxW / max($imgW,1), $maxH / max($imgH,1));
      $newW = (int)floor($imgW * $scale);
      $newH = (int)floor($imgH * $scale);
      $offX = max(0, (int)floor(($maxW - $newW)/2)) + $pad;
      $offY = max(0, (int)floor(($maxH - $newH)/2)) + $pad;

      $img = new Drawing();
      $img->setName('Photo'); $img->setDescription('Applicant Photo'); $img->setPath($data['photo_path']);
      $img->setResizeProportional(true);
      $img->setWidthAndHeight($newW, $newH);
      $img->setCoordinates(Coordinate::stringFromColumnIndex($c1) . $r1);
      $img->setOffsetX($offX); $img->setOffsetY($offY);
      $img->setWorksheet($sheet);
    }

    // Page setup for XLS (just to keep a sane default)
    $ps = $sheet->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_A4)
       ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
       ->setFitToWidth(1)->setFitToHeight(0)
       ->setHorizontalCentered(true);
    $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);

    // Save .xls
    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    IOFactory::createWriter($spreadsheet, 'Xls')->save($xlsPath);

    return ['ok'=>true, 'xls'=>$xlsPath, 'err'=>null];

  } catch (Throwable $e) {
    return ['ok'=>false, 'xls'=>null, 'err'=>$e->getMessage()];
  }
}

/**
 * Make a PDF via HTML streaming (single A4 page) with proportional width scaling.
 * Returns: ['ok'=>bool, 'pdf'=>?string, 'err'=>?string]
 */
function rireki_make_pdf_via_html(array $data, string $mappingFile, string $outDir, string $token, string $tmpDir): array {
  try {
    if (!class_exists('\\Mpdf\\Mpdf')) throw new RuntimeException('mPDF not available');

    @ini_set('pcre.backtrack_limit', '5000000');
    @ini_set('pcre.recursion_limit', '5000000');

    if (!is_readable($mappingFile)) throw new RuntimeException("Mapping not readable");
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) throw new RuntimeException("Template not readable");

    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // date split
    if (!empty($map['date_now_split'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $sheet->setCellValue($map['date_now_split']['year_cell'],  (int)$dt->format('Y'));
      $sheet->setCellValue($map['date_now_split']['month_cell'], (int)$dt->format('n'));
      $sheet->setCellValue($map['date_now_split']['day_cell'],   (int)$dt->format('j'));
    }
    // singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        if (array_key_exists($key,$data)) $sheet->setCellValue($cell, (string)$data[$key]);
      }
    }
    // repeaters
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
    // composed (age)
    if (!empty($map['composed_rules'])) {
      foreach ($map['composed_rules'] as $rule) {
        if (($rule['calc'] ?? '') === 'age_from_date') {
          $yAddr = $rule['source']['year']  ?? null;
          $mAddr = $rule['source']['month'] ?? null;
          $dAddr = $rule['source']['day']   ?? null;
          $target= $rule['target']          ?? null;
          if ($yAddr && $mAddr && $dAddr && $target) {
            $sy = (string)$sheet->getCell($yAddr)->getValue();
            $sm = (string)$sheet->getCell($mAddr)->getValue();
            $sd = (string)$sheet->getCell($dAddr)->getValue();
            if ($sy!=='' && $sm!=='' && $sd!=='') {
              $dob = DateTime::createFromFormat('Y-n-j', "$sy-$sm-$sd");
              if ($dob) $sheet->setCellValue($target, $dob->diff(new DateTime('now', new DateTimeZone('Asia/Tokyo')))->y);
            }
          }
        }
      }
    }

    // photo overlay (AD3 merged region) with 3px padding so border is visible
    $overlay = null;
    if (!empty($map['photo']) && !empty($data['photo_path']) && is_readable($data['photo_path'])) {
      $anchor = $map['photo']['anchor_cell'] ?? 'AD3';
      [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
      [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
      [$imgW,$imgH]     = @getimagesize($data['photo_path']) ?: [600,800];

      $pad  = 3;
      $boxW = max(1, $boxW - 2*$pad);
      $boxH = max(1, $boxH - 2*$pad);

      $scale = min($boxW / max($imgW,1), $boxH / max($imgH,1));
      $newW  = (int)floor($imgW * $scale);
      $newH  = (int)floor($imgH * $scale);
      $offX  = max(0, (int)floor(($boxW - $newW)/2)) + $pad + 2; // +2px for TD padding
      $offY  = max(0, (int)floor(($boxH - $newH)/2)) + $pad;

      $bin = @file_get_contents($data['photo_path']);
      $mime = 'image/jpeg'; $gi = @getimagesize($data['photo_path']);
      if (is_array($gi) && !empty($gi['mime'])) $mime = $gi['mime'];
      $dataUri = $bin!==false ? ('data:'.$mime.';base64,'.base64_encode($bin)) : null;

      if ($dataUri) {
        $overlay = [
          'addr'   => Coordinate::stringFromColumnIndex($c1) . $r1,
          'src'    => $dataUri,
          'width'  => $newW,
          'height' => $newH,
          'offX'   => $offX,
          'offY'   => $offY,
        ];
      }
    }

    // ===== mPDF config + font (ＭＳ 明朝) =====
    $fontDir  = rireki_path('fonts');
    $msMincho = $fontDir . '/msmincho001.ttf';

    $defaultConfig       = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $defaultFontConfig   = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontDirsDefault     = $defaultConfig['fontDir'];
    $fontDataDefault     = $defaultFontConfig['fontdata'];

    $extraFontData = [];
    if (is_readable($msMincho)) {
      $extraFontData = [
        'msmincho001' => [
          'R'         => 'msmincho001.ttf',
          'useOTL'    => 0xFF,
          'useKashida'=> 0,
        ],
      ];
    }

    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);
    if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

    // Margins in mm (tight)
    $marginL = 5; $marginR = 5; $marginT = 5; $marginB = 5;

    $mpdf = new \Mpdf\Mpdf([
      'tempDir'          => $tmpDir,
      'format'           => 'A4',
      'orientation'      => 'P',
      'margin_top'       => $marginT,
      'margin_bottom'    => $marginB,
      'margin_left'      => $marginL,
      'margin_right'     => $marginR,
      'autoScriptToLang' => true,
      'autoLangToFont'   => true,
      'fontDir'          => array_merge($fontDirsDefault, [$fontDir]),
      'fontdata'         => $extraFontData + $fontDataDefault,
      'default_font'     => is_readable($msMincho) ? 'msmincho001' : 'sans',
    ]);

    // Do NOT let mPDF auto-shrink our table; we’ll fit widths ourselves.
    $mpdf->shrink_tables_to_fit = 0;

    // Compute available content width in *CSS px* (mPDF uses 96dpi for px)
    $mm_to_px = 96 / 25.4;
    $contentWidthPx = (210 - ($marginL + $marginR)) * $mm_to_px; // A4 width minus margins
    if ($contentWidthPx < 720) $contentWidthPx = 720; // guardrail

    // Render the whole sheet and scale to fit content width
    $lastCol = $sheet->getHighestColumn();
    $lastRow = max(86, (int)$sheet->getHighestRow());

    [$headHtml, $rowHtmlList, $tailHtml] = renderSheetRegionHtmlStreamScaled(
      $sheet, 'A', $lastCol, 1, $lastRow, $overlay, (int)$contentWidthPx
    );

    // Basic CSS; keep dotted borders by mapping from template
    $css = 'table{border-collapse:collapse;table-layout:fixed;margin:0;padding:0}'
         . 'td{vertical-align:top;white-space:pre-wrap;line-height:1.15}'
         . 'tr{page-break-inside:avoid}';
    $mpdf->WriteHTML('<style>'.$css.'</style>', \Mpdf\HTMLParserMode::HEADER_CSS);

    // Stream in chunks (avoid PCRE limits)
    $mpdf->WriteHTML($headHtml, \Mpdf\HTMLParserMode::HTML_BODY);
    foreach ($rowHtmlList as $rowHtml) {
      $mpdf->WriteHTML($rowHtml, \Mpdf\HTMLParserMode::HTML_BODY);
    }
    $mpdf->WriteHTML($tailHtml, \Mpdf\HTMLParserMode::HTML_BODY);

    $pdfPath = rtrim($outDir,'/') . '/' . $token . '.pdf';
    $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);
    if (!is_readable($pdfPath) || filesize($pdfPath) === 0) throw new RuntimeException('Failed to produce PDF file.');
    return ['ok'=>true, 'pdf'=>$pdfPath, 'err'=>null];

  } catch (Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'err'=>$e->getMessage()];
  }
}

/* ===================== HTML render helpers (scaled) ===================== */
function renderSheetRegionHtmlStreamScaled(
  Worksheet $sheet, string $colStartLetter, string $colEndLetter,
  int $rowStart, int $rowEnd, ?array $overlay, int $targetTotalPx
): array {
  $cStart = Coordinate::columnIndexFromString(strtoupper($colStartLetter));
  $cEnd   = Coordinate::columnIndexFromString(strtoupper($colEndLetter));

  // Column widths (px) from Excel
  $colPx = [];
  $defaultColW = $sheet->getDefaultColumnDimension()->getWidth();
  if ($defaultColW === null || $defaultColW <= 0) $defaultColW = 8.43;
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $w = $dim && $dim->getWidth() !== null ? (float)$dim->getWidth() : (float)$defaultColW;
    $colPx[$c] = excelColWidthToPx($w);
  }

  // Scale all columns to fit exactly into targetTotalPx
  $sum = 0; foreach ($colPx as $v) $sum += $v;
  $scale = $sum > 0 ? ($targetTotalPx / $sum) : 1.0;
  if ($scale <= 0) $scale = 1.0;
  foreach ($colPx as $k => $v) $colPx[$k] = max(1, (int)round($v * $scale));

  // Row heights (px)
  $rowPx = [];
  $defaultRowPt = $sheet->getDefaultRowDimension()->getRowHeight();
  if ($defaultRowPt === -1 || $defaultRowPt === null) $defaultRowPt = 15;
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $dim = $sheet->getRowDimension($r);
    $pt = $dim && $dim->getRowHeight() > 0 ? (float)$dim->getRowHeight() : (float)$defaultRowPt;
    $rowPx[$r] = pointsToPx($pt); // keep template heights; no scale (height in px is stable)
  }

  // Merges
  [$mergeTop, $mergeCovered] = buildMergeMaps($sheet, $cStart, $cEnd, $rowStart, $rowEnd);

  // HEAD with fixed table pixel width
  $tableWidth = array_sum($colPx);
  $head = [];
  $head[] = '<div style="display:block;background:#fff;position:relative;">';
  $head[] = '<table style="border-collapse:collapse;table-layout:fixed;width:'.$tableWidth.'px;margin:0;padding:0">';
  $head[] = '<colgroup>';
  for ($c = $cStart; $c <= $cEnd; $c++) $head[] = '<col style="width:' . (int)$colPx[$c] . 'px;">';
  $head[] = '</colgroup>';
  $headHtml = implode('', $head);

  // ROWS
  $rows = [];
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $rowParts = [];
    $rowParts[] = '<tr style="height:' . (int)$rowPx[$r] . 'px;">';
    for ($c = $cStart; $c <= $cEnd; $c++) {
      if (isset($mergeCovered[$r][$c])) continue;
      $rs = 1; $cs = 1;
      if (isset($mergeTop[$r][$c])) { [$rs, $cs] = $mergeTop[$r][$c]; }

      // Borders from template (dotted/dashed preserved)
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

      // photo overlay if this is anchor
      if ($overlay && $overlay['addr'] === $addr) {
        $img = '<div style="position:relative;width:100%;height:100%;">'
             . '<img alt="photo" src="'.htmlspecialchars($overlay['src'],ENT_QUOTES,'UTF-8').'" '
             . 'style="position:absolute;left:'.$overlay['offX'].'px;top:'.$overlay['offY'].'px;'
             . 'width:'.$overlay['width'].'px;height:'.$overlay['height'].'px;object-fit:cover;border:0;"/>'
             . '</div>';
        $val = $img . $val;
      }

      $rowParts[] = '<td'.$extra.$attrs.'>'.$val.'</td>';
    }
    $rowParts[] = '</tr>';
    $rows[] = implode('', $rowParts);
  }

  // TAIL
  $tail = '</table></div>';

  return [$headHtml, $rows, $tail];
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

/* ===================== Photo/size helpers ===================== */
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

function excelColWidthToPx(float $widthChars): int {
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int { return (int)round($pt * 96 / 72); }
