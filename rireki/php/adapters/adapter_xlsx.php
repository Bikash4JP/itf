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
 * Render: XLS (BIFF8) + precise HTML previews for page 1 and page 2.
 * - Page 1: A1:O86
 * - Page 2: P1:X86
 *
 * Returns:
 *  [
 *    'ok'        => bool,
 *    'xls'       => ?string,
 *    'pdf'       => null,
 *    'html_left' => string,   // preserves widths/heights/merges/borders
 *    'html_right'=> string,
 *    'err'       => ?string
 *  ]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // Resolve template (relative to mappings/)
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Template not readable: $tplRel"];
    }

    // Load workbook + target sheet
    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // ===== Fill cells =====
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValue($cell, (string)$val);
      }
    }
    if (!empty($map['blocks'])) {
      foreach ($map['blocks'] as $key => $conf) {
        $cell = $conf['cell'] ?? null;
        if (!$cell) continue;
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $val = str_replace(["\r\n","\r"], "\n", (string)$val);
        $sheet->setCellValue($cell, $val);
        if (!empty($conf['wrap'])) {
          $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
          $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }
      }
    }
    $joins = $map['joins'] ?? [];
    $data  = applyJoinRules($data, $joins);

    if (!empty($map['repeaters'])) {
      foreach ($map['repeaters'] as $repKey => $repConf) {
        $rows = $data[$repKey] ?? [];
        if (!is_array($rows)) continue;

        $startRow = (int)($repConf['start_row'] ?? 0);
        $rowStep  = (int)($repConf['row_step'] ?? 1);
        $maxRows  = (int)($repConf['max_rows'] ?? count($rows));
        $cols     = $repConf['columns'] ?? [];

        $limit = min(count($rows), $maxRows);
        for ($i=0; $i<$limit; $i++) {
          $row = $rows[$i];
          $r   = $startRow + ($i * $rowStep);
          foreach ($cols as $field => $colLetter) {
            $coord = $colLetter . $r;
            $val   = $row[$field] ?? '';
            $sheet->setCellValue($coord, (string)$val);
          }
        }
      }
    }

    // Photo
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $anchor = $map['photo']['anchor_cell'] ?? 'A1';
        $h = (int)($map['photo']['height_px'] ?? 420);
        $img = new Drawing();
        $img->setName('Photo');
        $img->setDescription('Applicant Photo');
        $img->setPath($ph);
        $img->setHeight($h);
        $img->setCoordinates($anchor);
        $img->setWorksheet($sheet);
      }
    }

    // ===== Page Setup (Excel download stays 2 pages)
    $ps = $sheet->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_B5)
       ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
       ->setFitToWidth(1)
       ->setFitToHeight(1)
       ->setHorizontalCentered(true);

    $sheet->getPageMargins()
          ->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);
    $ps->setPrintArea('A1:O86,P1:X86');

    // Remove any manual breaks to avoid extra pages
    foreach ($sheet->getBreaks() as $coordinate => $type) {
      $sheet->setBreak($coordinate, Worksheet::BREAK_NONE);
    }

    // ===== OUTPUT PATHS =====
    if (!is_dir($outDir)) { @mkdir($outDir, 0750, true); }
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';

    // ===== Save XLS (BIFF8)
    try {
      $xlsWriter = IOFactory::createWriter($spreadsheet, 'Xls');
      $xlsWriter->save($xlsPath);
    } catch (\Throwable $e) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"XLS save failed: ".$e->getMessage()];
    }

    // ===== PRECISE HTML previews that keep cell sizes, merges, and BORDERS
    $htmlLeft  = renderSheetRegionHtml($sheet, 'A', 'O', 1, 86);
    $htmlRight = renderSheetRegionHtml($sheet, 'P', 'X', 1, 86);

    return ['ok'=>true, 'pdf'=>null, 'xls'=>$xlsPath, 'html_left'=>$htmlLeft, 'html_right'=>$htmlRight, 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
  }
}

/** ===== Helpers ===== */

/**
 * Build HTML table for a rectangular sheet region that preserves:
 * - Column widths (Excel width → px)
 * - Row heights (pt → px)
 * - Merged cells (rowspan/colspan)
 * - Borders (per side) with style & color
 */
function renderSheetRegionHtml(Worksheet $sheet, string $colStartLetter, string $colEndLetter, int $rowStart, int $rowEnd): string {
  $cStart = Coordinate::columnIndexFromString(strtoupper($colStartLetter));
  $cEnd   = Coordinate::columnIndexFromString(strtoupper($colEndLetter));

  // Column widths (Excel "characters" → px approx)
  $colPx = [];
  $defaultColW = $sheet->getDefaultColumnDimension()->getWidth();
  if ($defaultColW === null || $defaultColW <= 0) $defaultColW = 8.43; // Excel default
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $w = $dim && $dim->getWidth() !== null ? (float)$dim->getWidth() : (float)$defaultColW;
    $colPx[$c] = excelColWidthToPx($w);
  }

  // Row heights (points → px @96dpi)
  $rowPx = [];
  $defaultRowPt = $sheet->getDefaultRowDimension()->getRowHeight();
  if ($defaultRowPt === -1 || $defaultRowPt === null) $defaultRowPt = 15; // Excel default ~15pt
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $dim = $sheet->getRowDimension($r);
    $pt = $dim && $dim->getRowHeight() > 0 ? (float)$dim->getRowHeight() : (float)$defaultRowPt;
    $rowPx[$r] = pointsToPx($pt);
  }

  // Merges map
  [$mergeTop, $mergeCovered] = buildMergeMaps($sheet, $cStart, $cEnd, $rowStart, $rowEnd);

  // Build table
  $html = [];
  $html[] = '<div class="grid-container" style="display:inline-block;background:#fff;">';
  $html[] = '<table class="xls-preview" style="border-collapse:collapse;table-layout:fixed;">';

  // colgroup with exact widths
  $html[] = '<colgroup>';
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $html[] = '<col style="width:' . (int)$colPx[$c] . 'px;">';
  }
  $html[] = '</colgroup>';

  // rows
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $html[] = '<tr style="height:' . (int)$rowPx[$r] . 'px;">';
    for ($c = $cStart; $c <= $cEnd; $c++) {
      // skip covered cells from a merge
      if (isset($mergeCovered[$r][$c])) continue;

      // detect merged span (if any) for this top-left cell
      $rs = 1; $cs = 1;
      if (isset($mergeTop[$r][$c])) {
        [$rs, $cs] = $mergeTop[$r][$c];
      }

      // Compose border CSS per side (respect merges)
      $stylePieces = [];
      $bTop    = getCellBorderSideCss($sheet, $r,             $c,             'top');
      $bLeft   = getCellBorderSideCss($sheet, $r,             $c,             'left');
      $bRight  = getCellBorderSideCss($sheet, $r,             $c + $cs - 1,   'right');  // right edge of merged area
      $bBottom = getCellBorderSideCss($sheet, $r + $rs - 1,   $c,             'bottom'); // bottom edge of merged area
      if ($bTop)    $stylePieces[] = "border-top:{$bTop}";
      if ($bRight)  $stylePieces[] = "border-right:{$bRight}";
      if ($bBottom) $stylePieces[] = "border-bottom:{$bBottom}";
      if ($bLeft)   $stylePieces[] = "border-left:{$bLeft}";

      $attrs = ' style="padding:0 2px;vertical-align:top;overflow:hidden;white-space:pre-wrap;line-height:1.15;'
             . implode(';', $stylePieces)
             . (empty($stylePieces) ? '' : ';')
             . '"';

      $extra = '';
      if ($rs > 1) $extra .= ' rowspan="'.$rs.'"';
      if ($cs > 1) $extra .= ' colspan="'.$cs.'"';

      // value
      $val = '';
      $addr = Coordinate::stringFromColumnIndex($c) . $r; // SAFE for older versions
      $cell = $sheet->getCell($addr);
      if ($cell) {
        $v = $cell->getValue();
        if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
          $v = $v->getPlainText();
        }
        $val = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
      }

      $html[] = '<td'.$extra.$attrs.'>'.$val.'</td>';
    }
    $html[] = '</tr>';
  }

  $html[] = '</table></div>';
  return implode('', $html);
}

/** Build maps for merged cells within the visible region (clipped to region). */
function buildMergeMaps(Worksheet $sheet, int $cStart, int $cEnd, int $rStart, int $rEnd): array {
  $mergeTop = [];     // [$r][$c] = [rowspan, colspan]
  $covered  = [];     // [$r][$c] = true (skip in output)

  foreach ($sheet->getMergeCells() as $range) {
    [$start, $end] = Coordinate::rangeBoundaries($range);
    [$c1, $r1] = $start; [$c2, $r2] = $end;

    // intersect with our region
    $ic1 = max($c1, $cStart);
    $ir1 = max($r1, $rStart);
    $ic2 = min($c2, $cEnd);
    $ir2 = min($r2, $rEnd);
    if ($ic1 > $ic2 || $ir1 > $ir2) continue; // no overlap

    // mark top-left (first visible cell of the intersection)
    $rowspan = $ir2 - $ir1 + 1;
    $colspan = $ic2 - $ic1 + 1;
    $mergeTop[$ir1][$ic1] = [$rowspan, $colspan];

    // mark covered cells to skip (besides top-left)
    for ($r = $ir1; $r <= $ir2; $r++) {
      for ($c = $ic1; $c <= $ic2; $c++) {
        if ($r === $ir1 && $c === $ic1) continue;
        $covered[$r][$c] = true;
      }
    }
  }

  return [$mergeTop, $covered];
}

/** Get CSS (e.g., "1px solid #000000") for a cell's one border side. */
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
  if ($type === Border::BORDER_NONE || $type === null || $type === '') return '';

  // Map to CSS width & style
  [$w, $s] = mapBorderType($type);
  // Color
  $rgb = $b->getColor() ? strtoupper($b->getColor()->getRGB()) : '000000';
  if (!$rgb || strlen($rgb) !== 6) $rgb = '000000';
  return "{$w} {$s} #" . strtolower($rgb);
}

/** Map Excel border styles to CSS width & style */
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

// === Unit conversions ===
function excelColWidthToPx(float $widthChars): int {
  // Approximation commonly used for Excel width (characters) → pixels @96dpi
  // pixels = floor( (256*width + floor(128/7)) / 256 * 7 )
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int {
  return (int)round($pt * 96 / 72); // 1pt = 1/72 inch, 96dpi
}

function getValueByKeyPath(array $data, string $keyPath) {
  $parts = explode('.', $keyPath);
  $cur = $data;
  foreach ($parts as $p) {
    if (!is_array($cur) || !array_key_exists($p, $cur)) return null;
    $cur = $cur[$p];
  }
  return $cur;
}

function applyJoinRules(array $data, array $joins): array {
  if (empty($joins)) return $data;
  foreach ($joins as $fieldPath => $pattern) {
    $parts = explode('.', $fieldPath);
    if (count($parts) < 2) continue;
    $repKey = $parts[0]; $destKey = $parts[1];
    if (empty($data[$repKey]) || !is_array($data[$repKey])) continue;
    foreach ($data[$repKey] as $i => $row) {
      $val = $pattern;
      foreach ($row as $k => $v) $val = str_replace('{'.$k.'}', (string)$v, $val);
      $val = preg_replace('/\{[^\}]+\}/', '', $val);
      $data[$repKey][$i][$destKey] = trim($val);
    }
  }
  return $data;
}
