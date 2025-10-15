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
 * Build XLS (BIFF8) and HTML previews (Page1: A1–O86, Page2: P1–X86).
 * Returns:
 *  ['ok'=>bool, 'pdf'=>null, 'xls'=>?string, 'html_left'=>string, 'html_right'=>string, 'err'=>?string]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // Ensure today string for J3
    if (!isset($data['meta'])) $data['meta'] = [];
    if (empty($data['meta']['today_jp'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $data['meta']['today_jp'] = sprintf('%s年　%s月　%s日現在', $dt->format('Y'), $dt->format('m'), $dt->format('d'));
    }

    // Resolve template path (relative to mappings/)
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Template not readable: $tplRel"];
    }

    // Load template
    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // ---- Singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValue($cell, (string)$val);
      }
    }
    // Ensure J3 even if mapping forgot it
    try { $sheet->setCellValue('J3', (string)($data['meta']['today_jp'] ?? '')); } catch (\Throwable $e) {}

    // ---- Appends (e.g., M16 "電話 : {val}")
    if (!empty($map['appends'])) {
      foreach ($map['appends'] as $key => $conf) {
        $cell = $conf['cell'] ?? null;
        if (!$cell) continue;
        $val = getValueByKeyPath($data, $key);
        if ($val === null || $val === '') continue;
        $fmt  = $conf['format'] ?? '{orig}{val}';
        $orig = (string)$sheet->getCell($cell)->getValue();
        $out  = strtr($fmt, [
          '{orig}' => $orig,
          '{val}'  => (string)$val,
        ]);
        $sheet->setCellValue($cell, $out);
      }
    }

    // ---- Blocks (multiline)
    if (!empty($map['blocks'])) {
      foreach ($map['blocks'] as $key => $conf) {
        $cell = $conf['cell'] ?? null; if (!$cell) continue;
        $val = getValueByKeyPath($data, $key); if ($val === null) continue;
        $val = str_replace(["\r\n","\r"], "\n", (string)$val);
        $sheet->setCellValue($cell, $val);
        if (!empty($conf['wrap'])) {
          $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
          $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }
      }
    }

    // ---- Repeaters (after join rules)
    $data = applyJoinRules($data, $map['joins'] ?? []);
    if (!empty($map['repeaters'])) {
      foreach ($map['repeaters'] as $repKey => $repConf) {
        $rows = $data[$repKey] ?? []; if (!is_array($rows)) continue;

        $startRow = (int)($repConf['start_row'] ?? 0);
        $rowStep  = (int)($repConf['row_step'] ?? 1);
        $maxRows  = (int)($repConf['max_rows'] ?? count($rows));
        $cols     = $repConf['columns'] ?? [];
        $limit    = min(count($rows), $maxRows);

        for ($i=0; $i<$limit; $i++) {
          $rowData = $rows[$i] ?? [];
          $r       = $startRow + ($i * $rowStep);

          foreach ($cols as $field => $tpl) {
            $val = (string)($rowData[$field] ?? '');
            if ($val === '' && $val !== '0') continue;
            // Accept "B", "B{row}", "B{ROW}", "Q{row_plus_2}", etc.
            $coord = resolveCellCoord($tpl, $r);
            $sheet->setCellValue($coord, $val);
          }
        }
      }
    }

    // ---- Photo fit (stretch to box with 2px padding)
    $photoOverlay = null; // for preview overlay
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $anchor = $map['photo']['anchor_cell'] ?? 'M3';
        [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
        [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);

        // padding 2px each side
        $pad  = 2;
        $newW = max(1, $boxW - 2*$pad);
        $newH = max(1, $boxH - 2*$pad);
        $offX = $pad;
        $offY = $pad;

        // Put into XLS (stretched, not proportional)
        $img = new Drawing();
        $img->setName('Photo');
        $img->setDescription('Applicant Photo');
        $img->setPath($ph);
        $img->setResizeProportional(false); // stretch to fit
        $img->setWidthAndHeight($newW, $newH);
        $img->setCoordinates(Coordinate::stringFromColumnIndex($c1) . $r1); // top-left of merged
        $img->setOffsetX($offX);
        $img->setOffsetY($offY);
        $img->setWorksheet($sheet);

        // HTML overlay (preview) — stretch as well
        $mime = 'image/jpeg';
        $gi   = @getimagesize($ph);
        if (is_array($gi) && !empty($gi['mime'])) $mime = $gi['mime'];
        $bin = @file_get_contents($ph);
        if ($bin !== false) {
          $dataUri = 'data:' . $mime . ';base64,' . base64_encode($bin);
          $anchorTopLeft = Coordinate::stringFromColumnIndex($c1) . $r1;
          // +2px for our TD left padding in HTML renderer
          $photoOverlay = [
            'addr'   => $anchorTopLeft,
            'src'    => $dataUri,
            'width'  => $newW,
            'height' => $newH,
            'offX'   => $offX + 2,
            'offY'   => $offY,
          ];
        }
      }
    }

    // ---- Page setup (B5, 2 pages)
    $ps = $sheet->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_B5)
       ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
       ->setFitToWidth(1)->setFitToHeight(1)
       ->setHorizontalCentered(true);
    $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);
    $ps->setPrintArea('A1:O86,P1:X86');

    // ---- Save XLS (BIFF8)
    if (!is_dir($outDir)) @mkdir($outDir, 0750, true);
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    IOFactory::createWriter($spreadsheet, 'Xls')->save($xlsPath);

    // ---- HTML previews (pass overlay to proper page)
    $overlayLeft = null; $overlayRight = null;
    if ($photoOverlay) {
      [$colIdx] = Coordinate::coordinateFromString($photoOverlay['addr']);
      $colNum = Coordinate::columnIndexFromString($colIdx);
      if ($colNum >= Coordinate::columnIndexFromString('A') && $colNum <= Coordinate::columnIndexFromString('O')) {
        $overlayLeft = $photoOverlay;
      } elseif ($colNum >= Coordinate::columnIndexFromString('P') && $colNum <= Coordinate::columnIndexFromString('X')) {
        $overlayRight = $photoOverlay;
      }
    }

    $htmlLeft  = renderSheetRegionHtml($sheet, 'A', 'O', 1, 86, $overlayLeft);
    $htmlRight = renderSheetRegionHtml($sheet, 'P', 'X', 1, 86, $overlayRight);

    return ['ok'=>true, 'pdf'=>null, 'xls'=>$xlsPath, 'html_left'=>$htmlLeft, 'html_right'=>$htmlRight, 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
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
             . 'width:'.$overlay['width'].'px;height:'.$overlay['height'].'px;object-fit:fill;border:0;"/>'
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

/** ============== Photo region helpers ============== */
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

/** ============== Units ============== */
function excelColWidthToPx(float $widthChars): int {
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int { return (int)round($pt * 96 / 72); }

/** ============== Data helpers ============== */
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

/**
 * Resolve coordinates in mapping templates:
 *  - "B" + row -> "B38"
 *  - "B{row}" / "B{ROW}" -> "B38"
 *  - "Q{row_plus_2}" (any case) -> "Q40" (if row=38)
 */
function resolveCellCoord(string $tpl, int $row): string {
  $t = trim($tpl);

  // Pure column letters? ("B", "AC", etc.) -> append row
  if (preg_match('/^[A-Za-z]{1,3}$/', $t)) {
    return strtoupper($t) . $row;
  }

  // Replace case-insensitive {row} / {row_plus_n}
  $repls = [
    '{row}'         => $row,
    '{row_plus_1}'  => $row + 1,
    '{row_plus_2}'  => $row + 2,
    '{row_plus_3}'  => $row + 3,
    '{ROW}'         => $row,
    '{ROW_PLUS_1}'  => $row + 1,
    '{ROW_PLUS_2}'  => $row + 2,
    '{ROW_PLUS_3}'  => $row + 3,
  ];
  $coord = strtr($t, $repls);

  // If something unknown remained in braces, strip it and append row (fail-safe)
  if (preg_match('/\{[^}]+\}/', $coord)) {
    $coord = preg_replace('/\{[^}]+\}/', '', $coord) . $row;
  }

  // Final sanity
  if (!preg_match('/^[A-Za-z]{1,3}\d+$/', $coord)) {
    throw new RuntimeException("Invalid mapping coordinate after resolve: {$tpl} -> {$coord}");
  }
  return strtoupper($coord);
}

/** ============== Upload helper ============== */
function moveUploadedPhoto(array $file): string {
  $dir = rireki_path('uploads/photos');
  if (!is_dir($dir)) @mkdir($dir, 0750, true);
  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = rtrim($dir,'/') . '/' . $name;

  if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) {
    throw new RuntimeException('Photo too large');
  }
  if (class_exists('finfo')) {
    $f = new finfo(FILEINFO_MIME_TYPE);
    $mime = $f->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png'], true)) {
      throw new RuntimeException('Photo type not allowed');
    }
  }
  if (!move_uploaded_file($file['tmp_name'], $dest)) {
    throw new RuntimeException('Failed to move photo');
  }
  return $dest;
}

/** ============== Canonical data builder (expanded) ============== */
function buildCanonicalData(array $post, ?array $files): array {
  // Personal / Address / Contact (phone as string)
  $personal = [
    'name_kana' => trim($post['personal_name_kana'] ?? ''),
    'name_kanji'=> trim($post['personal_name_kanji'] ?? ''),
    'dob_yyyy'  => trim($post['dob_yyyy'] ?? ''),
    'dob_mm'    => trim($post['dob_mm'] ?? ''),
    'dob_dd'    => trim($post['dob_dd'] ?? ''),
    'age'       => trim($post['age'] ?? ''),
    'gender'    => trim($post['gender'] ?? ''),
  ];
  $address = [
    'kana'     => trim($post['address_kana'] ?? ''),
    'postcode' => trim($post['postcode'] ?? ''),
    'full'     => trim($post['address_full'] ?? ''),
  ];
  $contact = [
    'phone' => trim($post['phone'] ?? ''),
    'email' => trim($post['email'] ?? ''),
  ];

  // EDUCATION (入学 + 卒業/退学 line)
  $sy  = $post['edu_start_year']  ?? [];
  $sm  = $post['edu_start_month'] ?? [];
  $sn  = $post['edu_school_name'] ?? [];
  $sf  = $post['edu_faculty']     ?? [];
  $sl  = $post['edu_level']       ?? [];
  $ss  = $post['edu_status']      ?? []; // 在学中 / 卒業 / 退学
  $ey  = $post['edu_end_year']    ?? [];
  $em  = $post['edu_end_month']   ?? [];

  $education = [];
  $N = max(count($sy),count($sm),count($sn),count($sf),count($sl),count($ss),count($ey),count($em));
  for ($i=0; $i<$N; $i++) {
    $startY = trim($sy[$i] ?? ''); $startM = trim($sm[$i] ?? '');
    $name   = trim($sn[$i] ?? '');
    $fac    = trim($sf[$i] ?? '');
    $level  = trim($sl[$i] ?? '');
    $status = trim($ss[$i] ?? '在学中');
    $endY   = trim($ey[$i] ?? ''); $endM   = trim($em[$i] ?? '');

    if ($startY.$startM.$name.$fac.$level.$status.$endY.$endM === '') continue;

    $suffix = '';
    if ($fac !== '')   $suffix .= ' ' . $fac;
    if ($level !== '') $suffix .= ' ' . $level;

    if ($startY !== '' || $startM !== '' || $name !== '') {
      $education[] = [
        'year'   => $startY,
        'month'  => $startM,
        'school' => trim($name . $suffix . ' 入学'),
      ];
    }
    $isEnd = (preg_match('/^(卒|修了|退)/u', $status) || preg_match('/^(grad|drop)/i', $status));
    if ($isEnd && ($endY !== '' || $endM !== '')) {
      $education[] = [
        'year'   => $endY,
        'month'  => $endM,
        'school' => trim($name . $suffix . ' ' . ($status === '退学' ? '退学' : '卒業')),
      ];
    }
  }

  // EXPERIENCE (入社 + 退職 line)
  $xsy = $post['exp_start_year']   ?? [];
  $xsm = $post['exp_start_month']  ?? [];
  $xco = $post['exp_company']      ?? [];
  $xti = $post['exp_title']        ?? [];
  $xst = $post['exp_status']       ?? []; // 在職中 / 退職
  $xey = $post['exp_end_year']     ?? [];
  $xem = $post['exp_end_month']    ?? [];

  $experience = [];
  $E = max(count($xsy), count($xsm), count($xco), count($xti), count($xst), count($xey), count($xem));
  for ($i=0; $i<$E; $i++) {
    $syear = trim($xsy[$i] ?? ''); $smon = trim($xsm[$i] ?? '');
    $comp  = trim($xco[$i] ?? ''); $title = trim($xti[$i] ?? '');
    $stat  = trim($xst[$i] ?? '在職中');
    $eyear = trim($xey[$i] ?? ''); $emon  = trim($xem[$i] ?? '');

    if ($syear.$smon.$comp.$title.$stat.$eyear.$emon === '') continue;

    $labelIn  = '入社';
    $labelOut = '退職';

    // 入社 line
    $titleIn = trim($title . ' ' . $labelIn);
    $txtIn   = trim($comp . ' ' . $titleIn);
    $experience[] = ['year'=>$syear, 'month'=>$smon, 'company'=>$comp, 'title'=>$titleIn, 'text'=>$txtIn];

    // 退職 line
    $needEnd = (preg_match('/^(退)/u', $stat) || preg_match('/^(resign|quit|leave)/i', $stat));
    if ($needEnd && ($eyear !== '' || $emon !== '')) {
      $titleOut = trim($title . ' ' . $labelOut);
      $txtOut   = trim($comp . ' ' . $titleOut);
      $experience[] = ['year'=>$eyear, 'month'=>$emon, 'company'=>$comp, 'title'=>$titleOut, 'text'=>$txtOut];
    }
  }

  // LICENSES
  $licenses = [];
  $ly = $post['lic_year']  ?? [];
  $lm = $post['lic_month'] ?? [];
  $ln = $post['lic_name']  ?? [];
  $nL  = max(count($ly), count($lm), count($ln));
  for ($i=0; $i<$nL; $i++) {
    $licenses[] = [
      'year'        => trim($ly[$i] ?? ''),
      'month'       => trim($lm[$i] ?? ''),
      'certificate' => trim($ln[$i] ?? ''),
    ];
  }

  // PR / Preferences
  $pr = ['self_pr' => trim($post['self_pr'] ?? '')];
  $preferences = ['hopes' => trim($post['hopes'] ?? '')];

  // Photo
  $photoPath = $post['photo_path'] ?? '';
  if ($files && isset($files['photo']) && isset($files['photo']['error']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
    try { $photoPath = moveUploadedPhoto($files['photo']); } catch (\Throwable $e) {}
  }

  return [
    'personal'   => $personal,
    'address'    => $address,
    'contact'    => $contact,
    'education'  => array_values(array_filter($education,  fn($r)=>trim(($r['year']??'').($r['month']??'').($r['school']??'')) !== '')),
    'experience' => array_values(array_filter($experience, fn($r)=>trim(($r['year']??'').($r['month']??'').(($r['text']??'') . ($r['company']??'') . ($r['title']??''))) !== '')),
    'licenses'   => array_values(array_filter($licenses,   fn($r)=>trim(($r['year']??'').($r['month']??'').($r['certificate']??'')) !== '')),
    'pr'         => $pr,
    'preferences'=> $preferences,
    'photo_path' => $photoPath,
  ];
}
