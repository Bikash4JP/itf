<?php
require_once __DIR__ . '/../bootstrap.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Renders both: PDF (mPDF, B5 x 2 pages) and XLS (old .xls format; no ZipArchive needed).
 * - PDF is made by exporting two HTML views (left & right) and stitching in mPDF with JP fonts.
 * - XLS is a single-file Excel (BIFF) to let you verify the exact sheet visually.
 *
 * Returns: ['ok'=>bool, 'pdf'=>?string, 'xls'=>?string, 'err'=>?string]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // Resolve template (relative to mappings/)
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'err'=>"Template not readable: $tplRel"];
    }

    // Load workbook + target sheet
    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // ===== Fill cells =====
    // Singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValue($cell, (string)$val);
      }
    }

    // Blocks (multiline + wrap)
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

    // Joins inside repeaters
    $joins = $map['joins'] ?? [];
    $data  = applyJoinRules($data, $joins);

    // Repeaters
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
        $img->setHeight($h); // keep aspect
        $img->setCoordinates($anchor);
        $img->setWorksheet($sheet);
      }
    }

    // ===== OUTPUT DIR =====
    if (!is_dir($outDir)) { @mkdir($outDir, 0750, true); }
    $pdfPath = rtrim($outDir,'/') . '/' . $token . '.pdf';
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';

    // ===== 1) Save as XLS (no ZipArchive needed) =====
    try {
      $xlsWriter = IOFactory::createWriter($spreadsheet, 'Xls'); // BIFF8
      $xlsWriter->save($xlsPath);
    } catch (\Throwable $e) {
      // non-fatal; still continue to make PDF
      $xlsPath = null;
    }

    // ===== 2) PDF via mPDF with JP fonts & B5 2-page layout =====
    // Make two cropped books (left & right) so HTML shows only required area.

    [$rightMost, $lastRow] = [ $sheet->getHighestColumn(), max(88, $sheet->getHighestRow()) ];

    // LEFT BOOK: keep A..O only
    $leftBook  = clone $spreadsheet;
    $leftSheet = $leftBook->getActiveSheet();
    cropSheetToColumnRange($leftSheet, 'A', 'O', 88);

    // RIGHT BOOK: keep P..rightMost
    $rightBook  = clone $spreadsheet;
    $rightSheet = $rightBook->getActiveSheet();
    cropSheetToColumnRange($rightSheet, 'P', $rightMost, 88);

    // HTML writers
    $leftHtml  = sheetToHtml($leftBook);
    $rightHtml = sheetToHtml($rightBook);

    // mPDF font config — NO STATIC CALLS
    $tmpDir = rireki_path('tmp'); if (!is_dir($tmpDir)) @mkdir($tmpDir, 0750, true);
    $fontsDir = rireki_path('fonts');

    $defaultConfig      = (new \Mpdf\Config\ConfigVariables())->getDefaults();
    $fontDirs           = $defaultConfig['fontDir'];
    $defaultFontConfig  = (new \Mpdf\Config\FontVariables())->getDefaults();
    $fontData           = $defaultFontConfig['fontdata'];

    $defaultFont = 'dejavusans'; // fallback
    if (is_readable($fontsDir . '/ipaexg.ttf')) {
      $fontDirs[] = $fontsDir;
      $fontData['ipaexg'] = [ 'R' => 'ipaexg.ttf', 'B' => 'ipaexg.ttf' ];
      $defaultFont = 'ipaexg';
    } elseif (is_readable($fontsDir . '/NotoSansCJKjp-Regular.otf')) {
      $fontDirs[] = $fontsDir;
      $fontData['notosanscjkjp'] = [ 'R' => 'NotoSansCJKjp-Regular.otf', 'B' => 'NotoSansCJKjp-Regular.otf' ];
      $defaultFont = 'notosanscjkjp';
    }

    $mpdf = new \Mpdf\Mpdf([
      'mode'             => 'utf-8',
      'format'           => 'B5',
      'tempDir'          => $tmpDir,
      'fontDir'          => $fontDirs,
      'fontdata'         => $fontData,
      'default_font'     => $defaultFont,
      'default_font_size'=> 10,
      'margin_top'       => 6,
      'margin_bottom'    => 6,
      'margin_left'      => 6,
      'margin_right'     => 6,
    ]);

    // Minimal CSS
    $pageCss = '<style>
      @page { size: B5 portrait; margin: 6mm; }
      body { font-family: ' . htmlspecialchars($defaultFont, ENT_QUOTES, 'UTF-8') . ', sans-serif; font-size: 10pt; }
      table { border-collapse: collapse; }
      td, th { vertical-align: top; }
    </style>';

    // ===== Stitch pages WITHOUT AddPage (avoid blank first pages)
    $mpdf->WriteHTML(
      $pageCss
      . sanitizeForMpdf($leftHtml)
      . '<pagebreak />'
      . sanitizeForMpdf($rightHtml)
    );

    $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

    return ['ok'=>true, 'pdf'=>$pdfPath, 'xls'=>$xlsPath, 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'xls'=>null, 'err'=>$e->getMessage()];
  }
}

/** ====== Helpers ====== */

/** Convert a whole Spreadsheet (first sheet active) to HTML string */
function sheetToHtml(\PhpOffice\PhpSpreadsheet\Spreadsheet $book): string {
  $writer = new \PhpOffice\PhpSpreadsheet\Writer\Html($book);
  $writer->setSheetIndex($book->getActiveSheetIndex());
  $writer->setPreCalculateFormulas(false);
  $writer->setUseInlineCss(true);

  ob_start();
  $writer->save('php://output');
  return ob_get_clean();
}

/**
 * Keep only [fromCol..toCol] columns and top N rows; delete the rest.
 * Example: cropSheetToColumnRange($sheet, 'A','O', 88) keeps A..O & rows 1..88.
 */
function cropSheetToColumnRange(Worksheet $sheet, string $fromCol, string $toCol, int $rowsKeep): void {
  $fromIdx = Coordinate::columnIndexFromString(strtoupper($fromCol));
  $toIdx   = Coordinate::columnIndexFromString(strtoupper($toCol));
  $highest = Coordinate::columnIndexFromString($sheet->getHighestColumn());

  // delete columns RIGHT of range
  if ($toIdx < $highest) {
    $colStart = Coordinate::stringFromColumnIndex($toIdx + 1);
    $sheet->removeColumn($colStart, $highest - $toIdx);
  }

  // delete columns LEFT of range
  if ($fromIdx > 1) {
    $sheet->removeColumn('A', $fromIdx - 1);
  }

  // delete rows below rowsKeep
  $highestRow = $sheet->getHighestRow();
  if ($rowsKeep < $highestRow) {
    $sheet->removeRow($rowsKeep + 1, $highestRow - $rowsKeep);
  }

  // set print area to the new bounds
  $newHighestCol = $sheet->getHighestColumn();
  $sheet->getPageSetup()->setPrintArea('A1:' . $newHighestCol . $rowsKeep);
}

/**
 * Strip Spreadsheet HTML writer's forced page rules so mPDF doesn't inject blank pages.
 */
function sanitizeForMpdf(string $html): string {
  // Remove UTF-8 BOM if present
  $html = preg_replace('/^\xEF\xBB\xBF/', '', $html);

  // Drop @page rules and any explicit page size
  $html = preg_replace('/@page\s*\{[^}]*\}/i', '', $html);
  $html = preg_replace('/\bsize\s*:\s*[^;]+;?/i', '', $html);

  // Remove forced page-break styles
  $html = preg_replace('/page-break-(before|after)\s*:\s*always;?/i', '', $html);
  $html = preg_replace('#<br[^>]*style="[^"]*page-break-[^"]*"[^>]*/?>#i', '<br />', $html);
  $html = preg_replace('#style="[^"]*page-break-[^"]*"#i', '', $html);

  // Trim extra whitespace
  return trim($html);
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
