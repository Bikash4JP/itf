<?php
require_once __DIR__ . '/../bootstrap.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;

function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    // Resolve template
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Template not readable: $tplRel"];
    }

    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // Date split
    if (!empty($map['date_now_split'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $sheet->setCellValue($map['date_now_split']['year_cell'],  $dt->format('Y'));
      $sheet->setCellValue($map['date_now_split']['month_cell'], (int)$dt->format('m'));
      $sheet->setCellValue($map['date_now_split']['day_cell'],   (int)$dt->format('d'));
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
          $yAddr = $rule['source']['year'] ?? null;
          $mAddr = $rule['source']['month'] ?? null;
          $dAddr = $rule['source']['day'] ?? null;
          $target= $rule['target'] ?? null;
          if ($yAddr && $mAddr && $dAddr && $target) {
            $sy = (string)$sheet->getCell($yAddr)->getValue();
            $sm = (string)$sheet->getCell($mAddr)->getValue();
            $sd = (string)$sheet->getCell($dAddr)->getValue();
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

    // Photo: anchor at AD3, auto-detect merged range (e.g., AD3:AI8) and fit inside
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $anchor = $map['photo']['anchor_cell'] ?? 'AD3';
        [$c1,$r1,$c2,$r2] = findMergedBox($sheet, $anchor); // finds AD3:AI8 if merged
        [$boxW,$boxH]     = regionPixelSize($sheet, $c1,$c2,$r1,$r2);
        [$imgW,$imgH]     = @getimagesize($ph) ?: [600,800];

        // optional max from mapping
        $maxW = isset($map['photo']['max_width_px'])  ? (int)$map['photo']['max_width_px']  : $boxW;
        $maxH = isset($map['photo']['max_height_px']) ? (int)$map['photo']['max_height_px'] : $boxH;
        $boxW = min($boxW, $maxW);
        $boxH = min($boxH, $maxH);

        $scale = min(max($boxW,1)/$imgW, max($boxH,1)/$imgH);
        $newW = (int)floor($imgW * $scale);
        $newH = (int)floor($imgH * $scale);
        $offX = max(0, (int)floor(($boxW - $newW)/2));
        $offY = max(0, (int)floor(($boxH - $newH)/2));

        $img = new Drawing();
        $img->setName('Photo'); $img->setDescription('Applicant Photo'); $img->setPath($ph);
        $img->setResizeProportional(true);
        $img->setWidthAndHeight($newW, $newH);
        $img->setCoordinates(Coordinate::stringFromColumnIndex($c1) . $r1); // top-left of merged
        $img->setOffsetX($offX); $img->setOffsetY($offY);
        $img->setWorksheet($sheet);
      }
    }

    // Page setup (optional)
    $ps = $sheet->getPageSetup();
    $ps->setPaperSize(PageSetup::PAPERSIZE_B5)
       ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
       ->setFitToWidth(1)->setFitToHeight(1)
       ->setHorizontalCentered(true);
    $sheet->getPageMargins()->setTop(0.25)->setBottom(0.25)->setLeft(0.25)->setRight(0.25);
    $sheet->setShowGridlines(false);
    $sheet->setPrintGridlines(false);

    // Save .xls
    if (!is_dir($outDir)) @mkdir($outDir, 0750, true);
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    IOFactory::createWriter($spreadsheet, 'Xls')->save($xlsPath);

    return ['ok'=>true, 'xls'=>$xlsPath, 'html_left'=>'', 'html_right'=>'', 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
  }
}

/** ===== Helper funcs ===== */
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
function excelColWidthToPx(float $widthChars): int {
  $pixels = (int)floor(((256 * $widthChars + (int)floor(128/7)) / 256) * 7);
  if ($pixels <= 0) $pixels = 1;
  return $pixels;
}
function pointsToPx(float $pt): int { return (int)round($pt * 96 / 72); }
