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
 * Render: XLS (BIFF8) + precise HTML previews (2 pages)
 * Page1: A1:O86, Page2: P1:X86
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // ---- inject today's JP date for header ----
    if (!isset($data['meta'])) $data['meta'] = [];
    if (empty($data['meta']['today_jp'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $data['meta']['today_jp'] = sprintf('%s年　%s月　%s日現在', $dt->format('Y'), $dt->format('m'), $dt->format('d'));
    }

    // Resolve template
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Template not readable: $tplRel"];
    }

    // Load workbook / sheet
    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // ===== Singles =====
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValue($cell, (string)$val);
      }
    }

    // ---- HARD GUARANTEE: write J3 with current date (even if mapping missed it)
    try { $sheet->setCellValue('J3', (string)($data['meta']['today_jp'] ?? '')); } catch (\Throwable $e) {}

    // ===== Appends (label + value) =====
    if (!empty($map['appends'])) {
      foreach ($map['appends'] as $key => $conf) {
        $cell = $conf['cell'] ?? null;
        if (!$cell) continue;
        $val = getValueByKeyPath($data, $key);
        if ($val === null || $val === '') continue;

        $fmt  = $conf['format'] ?? '{orig}{val}';
        $orig = (string)$sheet->getCell($cell)->getValue();

        // If format is static (e.g., "電話 : {val}"), don't rely on orig
        $out  = strtr($fmt, [
          '{orig}' => $orig,
          '{val}'  => (string)$val,
        ]);

        $sheet->setCellValue($cell, $out);
      }
    }

    // ===== Blocks (multiline) =====
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

    // ===== Joins + Repeaters =====
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

    // ===== Photo fit (contain inside merged box) =====
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $anchor = $map['photo']['anchor_cell'] ?? 'M3';
        [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor);
        [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
        [$imgW,$imgH]     = @getimagesize($ph) ?: [600,800];
        $scale = min(max($boxW,1)/$imgW, max($boxH,1)/$imgH);
        $newW  = (int)floor($imgW * $scale);
        $newH  = (int)floor($imgH * $scale);
        $offX  = max(0, (int)floor(($boxW - $newW)/2));
        $offY  = max(0, (int)floor(($boxH - $newH)/2));

        $img = new Drawing();
        $img->setName('Photo');
        $img->setDescription('Applicant Photo');
        $img->setPath($ph);
        $img->setResizeProportional(true);
        $img->setWidthAndHeight($newW, $newH);
        $img->setCoordinates($anchor);
        $img->setOffsetX($offX);
        $img->setOffsetY($offY);
        $img->setWorksheet($sheet);
      }
    }

    // ===== Page Setup (2 pages only) =====
    $ps = $sheet->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_B5)
       ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
       ->setFitToWidth(1)->setFitToHeight(1)->setHorizontalCentered(true);
    $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);
    $ps->setPrintArea('A1:O86,P1:X86');
    foreach ($sheet->getBreaks() as $coordinate => $type) {
      $sheet->setBreak($coordinate, Worksheet::BREAK_NONE);
    }

    // ===== Save XLS =====
    if (!is_dir($outDir)) { @mkdir($outDir, 0750, true); }
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    $xlsWriter = IOFactory::createWriter($spreadsheet, 'Xls');
    $xlsWriter->save($xlsPath);

    // ===== HTML previews =====
    $htmlLeft  = renderSheetRegionHtml($sheet, 'A', 'O', 1, 86);
    $htmlRight = renderSheetRegionHtml($sheet, 'P', 'X', 1, 86);

    return ['ok'=>true, 'pdf'=>null, 'xls'=>$xlsPath, 'html_left'=>$htmlLeft, 'html_right'=>$htmlRight, 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
  }
}

/** ====== HTML render helpers (sizes/merges/borders preserved) ====== */
function renderSheetRegionHtml(Worksheet $sheet, string $colStartLetter, string $colEndLetter, int $rowStart, int $rowEnd): string {
  $cStart = Coordinate::columnIndexFromString(strtoupper($colStartLetter));
  $cEnd   = Coordinate::columnIndexFromString(strtoupper($colEndLetter));

  // Column widths
  $colPx = [];
  $defaultColW = $sheet->getDefaultColumnDimension()->getWidth();
  if ($defaultColW === null || $defaultColW <= 0) $defaultColW = 8.43;
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $w = $dim && $dim->getWidth() !== null ? (float)$dim->getWidth() : (float)$defaultColW;
    $colPx[$c] = excelColWidthToPx($w);
  }

  // Row heights
  $rowPx = [];
  $defaultRowPt = $sheet->getDefaultRowDimension()->getRowHeight();
  if ($defaultRowPt === -1 || $defaultRowPt === null) $defaultRowPt = 15;
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

  // colgroup widths
  $html[] = '<colgroup>';
  for ($c = $cStart; $c <= $cEnd; $c++) {
    $html[] = '<col style="width:' . (int)$colPx[$c] . 'px;">';
  }
  $html[] = '</colgroup>';

  // rows/cells
  for ($r = $rowStart; $r <= $rowEnd; $r++) {
    $html[] = '<tr style="height:' . (int)$rowPx[$r] . 'px;">';
    for ($c = $cStart; $c <= $cEnd; $c++) {
      if (isset($mergeCovered[$r][$c])) continue;

      $rs = 1; $cs = 1;
      if (isset($mergeTop[$r][$c])) { [$rs, $cs] = $mergeTop[$r][$c]; }

      // Borders per side (only visible ones)
      $stylePieces = [];
      $bTop    = getCellBorderSideCss($sheet, $r,             $c,             'top');
      $bLeft   = getCellBorderSideCss($sheet, $r,             $c,             'left');
      $bRight  = getCellBorderSideCss($sheet, $r,             $c + $cs - 1,   'right');
      $bBottom = getCellBorderSideCss($sheet, $r + $rs - 1,   $c,             'bottom');
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
      $addr = Coordinate::stringFromColumnIndex($c) . $r;
      $cell = $sheet->getCell($addr);
      if ($cell) {
        $v = $cell->getValue();
        if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) $v = $v->getPlainText();
        $val = htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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
        if ($r === $ir1 && $c === $ic1) continue;
        $covered[$r][$c] = true;
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

// ========== common helpers ==========
function excelColWidthToPx(float $widthChars): int {
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int { return (int)round($pt * 96 / 72); }

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

/* ===== image-fit helpers ===== */
function findMergedBox(Worksheet $sheet, string $anchor): array {
  [$ac,$ar] = Coordinate::coordinateFromString($anchor);
  $acIdx = Coordinate::columnIndexFromString($ac);
  $r1=$ar; $r2=$ar; $c1=$acIdx; $c2=$acIdx;
  foreach ($sheet->getMergeCells() as $range) {
    [$s,$e] = Coordinate::rangeBoundaries($range); [$cS,$rS] = $s; [$cE,$rE] = $e;
    if ($acIdx >= $cS && $acIdx <= $cE && $ar >= $rS && $ar <= $rE) { $c1=$cS; $c2=$cE; $r1=$rS; $r2=$rE; break; }
  }
  return [$c1,$r1,$c2,$r2];
}
function regionPixelSize(Worksheet $sheet, int $c1, int $c2, int $r1, int $r2): array {
  $w=0; for ($c=$c1; $c<=$c2; $c++) {
    $letter = Coordinate::stringFromColumnIndex($c);
    $dim = $sheet->getColumnDimension($letter);
    $cw = $dim && $dim->getWidth()!==null ? (float)$dim->getWidth() : (float)($sheet->getDefaultColumnDimension()->getWidth() ?? 8.43);
    $w += excelColWidthToPx($cw);
  }
  $h=0; for ($r=$r1; $r<=$r2; $r++) {
    $dim = $sheet->getRowDimension($r);
    $pt = $dim && $dim->getRowHeight()>0 ? (float)$dim->getRowHeight() : (float)($sheet->getDefaultRowDimension()->getRowHeight() ?? 15);
    $h += pointsToPx($pt);
  }
  return [$w,$h];
}

/** ====== helpers for submit_rireki.php ====== */
function buildCanonicalData(array $post, ?array $files): array {
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

  // Education
  $education = [];
  $years  = $post['edu_year']  ?? [];
  $months = $post['edu_month'] ?? [];
  $schools= $post['edu_school']?? [];
  $n = max(count($years), count($months), count($schools));
  for ($i=0; $i<$n; $i++) {
    $education[] = [
      'year'   => trim($years[$i]   ?? ''),
      'month'  => trim($months[$i]  ?? ''),
      'school' => trim($schools[$i] ?? ''),
    ];
  }

  // Experience
  $experience = [];
  $ey = $post['exp_year']   ?? [];
  $em = $post['exp_month']  ?? [];
  $ec = $post['exp_company']?? [];
  $et = $post['exp_title']  ?? [];
  $n  = max(count($ey), count($em), count($ec), count($et));
  for ($i=0; $i<$n; $i++) {
    $experience[] = [
      'year'    => trim($ey[$i] ?? ''),
      'month'   => trim($em[$i] ?? ''),
      'company' => trim($ec[$i] ?? ''),
      'title'   => trim($et[$i] ?? ''),
    ];
  }

  // Licenses
  $licenses = [];
  $ly = $post['lic_year']  ?? [];
  $lm = $post['lic_month'] ?? [];
  $ln = $post['lic_name']  ?? [];
  $n  = max(count($ly), count($lm), count($ln));
  for ($i=0; $i<$n; $i++) {
    $licenses[] = [
      'year'        => trim($ly[$i] ?? ''),
      'month'       => trim($lm[$i] ?? ''),
      'certificate' => trim($ln[$i] ?? ''),
    ];
  }

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
    'experience' => array_values(array_filter($experience, fn($r)=>trim(($r['year']??'').($r['month']??'').($r['company']??'').($r['title']??'')) !== '')),
    'licenses'   => array_values(array_filter($licenses,   fn($r)=>trim(($r['year']??'').($r['month']??'').($r['certificate']??'')) !== '')),
    'pr'         => $pr,
    'preferences'=> $preferences,
    'photo_path' => $photoPath,
  ];
}

function moveUploadedPhoto(array $file): string {
  $dir = rireki_path('uploads/photos');
  if (!is_dir($dir)) @mkdir($dir, 0750, true);
  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = rtrim($dir,'/') . '/' . $name;

  if (!empty($file['size']) && $file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Photo too large');
  if (class_exists('finfo')) {
    $f = new finfo(FILEINFO_MIME_TYPE);
    $mime = $f->file($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png'], true)) throw new RuntimeException('Photo type not allowed');
  }
  if (!move_uploaded_file($file['tmp_name'], $dest)) throw new RuntimeException('Failed to move photo');
  return $dest;
}
