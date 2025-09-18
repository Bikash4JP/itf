<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Build XLS from a mapping JSON (Kaigo_Template_XLS.json) + template (kaigo.xlsx).
 * - Fills singles by exact key => cell mapping
 * - Supports repeaters with {row}, {row_plus_1}, ... placeholders
 * - Places applicant photo into merged/boxed area with fit
 * - Fills "today" split (year/month/day) if mapping provides date_now_split
 * - Computes age into mapping 'singles.age_autofill' if DOB provided
 * - Adds dropdown list validations via mapping['dropdowns'] resolving to singles cells
 *
 * Returns: ['ok'=>bool,'xls'=>?string,'pdf'=>null,'err'=>?string]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false,'xls'=>null,'pdf'=>null,'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode((string)file_get_contents($mappingFile), true);
    if (!is_array($map)) {
      return ['ok'=>false,'xls'=>null,'pdf'=>null,'err'=>'Mapping JSON decode failed'];
    }

    // Resolve template relative to mapping file
    $tplRel  = (string)($map['template_file'] ?? '../templates/kaigo.xlsx');
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false,'xls'=>null,'pdf'=>null,'err'=>"Template not readable: $tplRel"];
    }

    // Load template with full formatting intact
    $reader = IOFactory::createReaderForFile($tplPath);
    if (method_exists($reader,'setReadDataOnly')) $reader->setReadDataOnly(false);
    if (method_exists($reader,'setIncludeCharts')) $reader->setIncludeCharts(false);
    if (method_exists($reader,'setPreCalculateFormulas')) $reader->setPreCalculateFormulas(false);

    $book  = $reader->load($tplPath);
    $sheet = $book->getActiveSheet();
    $sheetName = (string)($map['sheet_name'] ?? '');
    if ($sheetName !== '' && $book->sheetNameExists($sheetName)) {
      $sheet = $book->getSheetByName($sheetName);
      $book->setActiveSheetIndexByName($sheetName);
    }

    // 1) TODAY split (JST)
    applyTodaySplit($sheet, $map);

    // 2) SINGLES
    if (!empty($map['singles']) && is_array($map['singles'])) {
      foreach ($map['singles'] as $key => $cellAddr) {
        if (!is_string($cellAddr) || $cellAddr === '') continue;
        if (array_key_exists($key, $data)) {
          $sheet->setCellValueExplicit($cellAddr, (string)$data[$key]);
        }
      }
    }

    // 3) AGE (if DOB provided + target exists)
    applyAgeIfDobPresent($sheet, $map, $data);

    // 4) REPEATERS
    if (!empty($map['repeaters']) && is_array($map['repeaters'])) {
      foreach ($map['repeaters'] as $repKey => $conf) {
        fillRepeater($sheet, $repKey, $conf, $data[$repKey] ?? []);
      }
    }

    // 5) PHOTO
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      placePhoto($sheet, $map['photo'], (string)$data['photo_path']);
    }

    // 6) DROPDOWNS (validations): mapping['dropdowns'] keys map to logical field names.
    // We resolve their target cell via mapping['singles'][that_key].
    applyDropdownValidations($sheet, $map);

    // 7) OUTPUT
    if (!is_dir($outDir)) { @mkdir($outDir, 0775, true); }
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    $writer = IOFactory::createWriter($book, 'Xls');
    if (method_exists($writer,'setPreCalculateFormulas')) $writer->setPreCalculateFormulas(false);
    $writer->save($xlsPath);

    // Cleanup
    $book->disconnectWorksheets(); unset($book);

    return ['ok'=>true,'xls'=>$xlsPath,'pdf'=>null,'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false,'xls'=>null,'pdf'=>null,'err'=>$e->getMessage()];
  }
}

/** ================= Helpers ================= */

function applyTodaySplit(Worksheet $sheet, array $map): void {
  $tz  = new \DateTimeZone('Asia/Tokyo');
  $now = new \DateTime('now', $tz);
  $Y = $now->format('Y'); $M = $now->format('n'); $D = $now->format('j');

  if (!empty($map['date_now_split']) && is_array($map['date_now_split'])) {
    $yCell = (string)($map['date_now_split']['year_cell']  ?? '');
    $mCell = (string)($map['date_now_split']['month_cell'] ?? '');
    $dCell = (string)($map['date_now_split']['day_cell']   ?? '');
    if ($yCell) $sheet->setCellValueExplicit($yCell, $Y);
    if ($mCell) $sheet->setCellValueExplicit($mCell, $M);
    if ($dCell) $sheet->setCellValueExplicit($dCell, $D);
  } else {
    // Fallback to AC2/AF2/AH2 if mapping lacks date_now_split
    $sheet->setCellValueExplicit('AC2', $Y);
    $sheet->setCellValueExplicit('AF2', $M);
    $sheet->setCellValueExplicit('AH2', $D);
  }
}

function applyAgeIfDobPresent(Worksheet $sheet, array $map, array $data): void {
  // Determine target cell
  $ageCell = null;
  if (!empty($map['singles']['age_autofill'])) {
    $ageCell = (string)$map['singles']['age_autofill'];
  } elseif (!empty($map['singles']['personal.age'])) {
    $ageCell = (string)$map['singles']['personal.age'];
  }
  if (!$ageCell) return;

  // Read DOB from singles keys we wrote from POST-flattened data
  $dy = (string)($data['dob_year']  ?? '');
  $dm = (string)($data['dob_month'] ?? '');
  $dd = (string)($data['dob_day']   ?? '');
  if ($dy === '' || $dm === '' || $dd === '') return;

  try {
    $tz  = new \DateTimeZone('Asia/Tokyo');
    $now = new \DateTime('now', $tz);
    $dob = new \DateTime(sprintf('%04d-%02d-%02d', (int)$dy, (int)$dm, (int)$dd), $tz);
    $age = (int)$now->format('Y') - (int)$dob->format('Y');
    $ann = new \DateTime($now->format('Y') . sprintf('-%02d-%02d', (int)$dm, (int)$dd), $tz);
    if ($now < $ann) $age--;
    if ($age < 0) $age = 0;
    $sheet->setCellValueExplicit($ageCell, (string)$age);
  } catch (\Throwable $e) {
    // ignore
  }
}

/**
 * Fill a repeater block.
 * Conf must contain: start_row, row_step, max_rows, columns (field => pattern)
 * Pattern supports {row}, {row_plus_1}, {row_plus_2} ...
 */
function fillRepeater(Worksheet $sheet, string $repKey, array $conf, array $rows): void {
  if (!is_array($conf) || empty($conf['columns'])) return;
  $startRow = (int)($conf['start_row'] ?? 0);
  $rowStep  = (int)($conf['row_step']  ?? 1);
  $maxRows  = (int)($conf['max_rows']  ?? count($rows));
  $cols     = $conf['columns'];

  $limit = min((int)count($rows), $maxRows);
  for ($i=0; $i<$limit; $i++) {
    $base = $startRow + ($i * $rowStep);
    $row  = (array)$rows[$i];

    foreach ($cols as $field => $pattern) {
      $val = array_key_exists($field, $row) ? (string)$row[$field] : '';
      if ($val === '') continue;

      $addr = expandRowPattern((string)$pattern, $base);
      if ($addr === '') continue;
      $sheet->setCellValueExplicit($addr, $val);
    }
  }
}

/**
 * Expand "B{row}", "AD{row_plus_1}" style placeholders to concrete addresses.
 */
function expandRowPattern(string $pattern, int $baseRow): string {
  if ($pattern === '') return '';
  $addr = $pattern;
  // {row}
  $addr = str_replace('{row}', (string)$baseRow, $addr);
  // {row_plus_N}
  $addr = preg_replace_callback('/\{row_plus_(\d+)\}/', function($m) use ($baseRow){
    return (string)($baseRow + (int)$m[1]);
  }, $addr);
  // Quick sanity check: like "B22" or "AD23"
  if (!preg_match('/^[A-Z]{1,3}\d+$/', $addr)) return '';
  return $addr;
}

/**
 * Place photo with simple "fit into merged box" if merge_range provided,
 * or anchor to a single cell (anchor_cell). Keeps aspect ratio.
 */
function placePhoto(Worksheet $sheet, array $photoMap, string $filePath): void {
  if (!is_readable($filePath)) return;

  $anchor = (string)($photoMap['anchor_cell'] ?? 'AD3');
  $merge  = (string)($photoMap['merge_range'] ?? '');
  $maxW   = (int)($photoMap['max_width_px']  ?? 300);
  $maxH   = (int)($photoMap['max_height_px'] ?? 420);

  $img = new Drawing();
  $img->setName('Photo');
  $img->setDescription('Applicant Photo');
  $img->setPath($filePath);

  $targetW = $maxW; $targetH = $maxH;

  if ($merge && strpos($merge, ':') !== false) {
    // Try to estimate pixel size of merged block
    [$start, $end] = explode(':', strtoupper($merge));
    [$c1, $r1] = splitCellAddr($start);
    [$c2, $r2] = splitCellAddr($end);

    // Sum columns width (rough px), rows height (rough px)
    $from = Coordinate::columnIndexFromString($c1);
    $to   = Coordinate::columnIndexFromString($c2);

    $wPx = 0;
    for ($i=$from; $i<=$to; $i++) {
      $col = Coordinate::stringFromColumnIndex($i);
      $w   = $sheet->getColumnDimension($col)->getWidth();
      $wPx += ($w > 0 ? (int)round($w * 7) : 64); // approx fallback
    }
    $hPx = 0;
    for ($r=$r1; $r<=$r2; $r++) {
      $h = $sheet->getRowDimension($r)->getRowHeight();
      $hPx += ($h > 0 ? (int)round($h * 1.33) : 18); // approx fallback
    }
    if ($wPx > 0) $targetW = min($targetW, $wPx);
    if ($hPx > 0) $targetH = min($targetH, $hPx);
  }

  // Keep aspect ratio
  $info = @getimagesize($filePath);
  if (is_array($info) && !empty($info[0]) && !empty($info[1])) {
    $scale = min($targetW / $info[0], $targetH / $info[1]);
    $img->setWidth((int)floor($info[0] * $scale));
    $img->setHeight((int)floor($info[1] * $scale));
  } else {
    $img->setHeight($targetH);
  }

  $img->setCoordinates($anchor);
  $img->setWorksheet($sheet);
}

/** Split like "AD23" => ["AD", 23] */
function splitCellAddr(string $addr): array {
  if (preg_match('/^([A-Z]+)(\d+)$/', strtoupper($addr), $m)) {
    return [$m[1], (int)$m[2]];
  }
  return ['A', 1];
}

/**
 * Apply dropdown list validations from mapping['dropdowns'].
 * Keys are logical names; we look up the target cell from mapping['singles'][key].
 */
function applyDropdownValidations(Worksheet $sheet, array $map): void {
  if (empty($map['dropdowns']) || empty($map['singles'])) return;

  foreach ($map['dropdowns'] as $key => $list) {
    if (!isset($map['singles'][$key])) continue;
    $cell = (string)$map['singles'][$key];
    if ($cell === '' || !is_array($list) || empty($list)) continue;

    $dv = $sheet->getCell($cell)->getDataValidation();
    $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
    $dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
    $dv->setAllowBlank(true);
    $dv->setShowInputMessage(true);
    $dv->setShowErrorMessage(true);
    $dv->setShowDropDown(true);

    // Build CSV-style quoted list
    $escaped = array_map(function($s){
      $s = (string)$s;
      $s = str_replace('"','""',$s);
      return '"' . $s . '"';
    }, $list);

    $dv->setFormula1('=' . implode(',', $escaped));
  }
}

/**
 * Optional: crop to A..AI and rows 1..67. Keep as utility for preview code.
 */
function cropSheetToExactRange(Worksheet $sheet, string $fromCol, string $toCol, int $maxRow): void {
  // Remove columns to the right of $toCol
  $toIndex = Coordinate::columnIndexFromString($toCol);
  $colCount = $sheet->getHighestColumn();
  $allCols  = Coordinate::columnIndexFromString($colCount);
  if ($toIndex < $allCols) {
    // remove starting from col after $toCol
    $sheet->removeColumn(Coordinate::stringFromColumnIndex($toIndex + 1), $allCols - $toIndex);
  }
  // Remove rows after $maxRow
  $highestRow = $sheet->getHighestRow();
  if ($maxRow < $highestRow) {
    $sheet->removeRow($maxRow + 1, $highestRow - $maxRow);
  }
}
