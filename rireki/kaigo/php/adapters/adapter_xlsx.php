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

    // Resolve template path
    $tplRel  = $map['template_file'] ?? '';
    $tplPath = realpath(dirname($mappingFile) . '/' . $tplRel);
    if (!$tplPath || !is_readable($tplPath)) {
      return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>"Template not readable: $tplRel"];
    }

    // Load template
    $spreadsheet = IOFactory::load($tplPath);
    $sheet = $spreadsheet->getActiveSheet();
    if (!empty($map['sheet_name']) && $spreadsheet->sheetNameExists($map['sheet_name'])) {
      $sheet = $spreadsheet->getSheetByName($map['sheet_name']);
      $spreadsheet->setActiveSheetIndexByName($map['sheet_name']);
    }

    // ---- Date split
    if (!empty($map['date_now_split'])) {
      $dt = new DateTime('now', new DateTimeZone('Asia/Tokyo'));
      $sheet->setCellValue($map['date_now_split']['year_cell'], $dt->format('Y'));
      $sheet->setCellValue($map['date_now_split']['month_cell'], (int)$dt->format('m'));
      $sheet->setCellValue($map['date_now_split']['day_cell'], (int)$dt->format('d'));
    }

    // ---- Singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValue($cell, (string)$val);
      }
    }

    // ---- Repeaters with {row}
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
          $r = $startRow + ($i * $rowStep);
          foreach ($cols as $field => $tpl) {
            $coord = str_replace(
              ['{row}','{row_plus_1}','{row_plus_2}'],
              [$r, $r+1, $r+2],
              $tpl
            );
            $sheet->setCellValue($coord, (string)($row[$field] ?? ''));
          }
        }
      }
    }

    // ---- Composed rules (e.g., age_from_dob)
    if (!empty($map['composed_rules'])) {
      foreach ($map['composed_rules'] as $rule) {
        if ($rule['calc'] === 'age_from_date') {
          $sy = $sheet->getCell($rule['source']['year'])->getValue();
          $sm = $sheet->getCell($rule['source']['month'])->getValue();
          $sd = $sheet->getCell($rule['source']['day'])->getValue();
          if ($sy && $sm && $sd) {
            $dob = DateTime::createFromFormat('Y-n-j', "$sy-$sm-$sd");
            if ($dob) {
              $age = $dob->diff(new DateTime('now'))->y;
              $sheet->setCellValue($rule['target'], $age);
            }
          }
        }
      }
    }

    // ---- Photo
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $img = new Drawing();
        $img->setPath($ph);
        $img->setCoordinates($map['photo']['anchor_cell']);
        $img->setWidth($map['photo']['max_width_px'] ?? 200);
        $img->setHeight($map['photo']['max_height_px'] ?? 300);
        $img->setWorksheet($sheet);
      }
    }

    // ---- Save XLS
    if (!is_dir($outDir)) @mkdir($outDir, 0750, true);
    $xlsPath = rtrim($outDir,'/') . '/' . $token . '.xls';
    IOFactory::createWriter($spreadsheet, 'Xls')->save($xlsPath);

    return ['ok'=>true, 'xls'=>$xlsPath, 'html_left'=>'', 'html_right'=>'', 'err'=>null];

  } catch (\Throwable $e) {
    return ['ok'=>false, 'xls'=>null, 'html_left'=>'', 'html_right'=>'', 'err'=>$e->getMessage()];
  }
}

// ---- Helpers ----
function getValueByKeyPath(array $data, string $keyPath) {
  $parts = explode('.', $keyPath);
  $cur = $data;
  foreach ($parts as $p) {
    if (!is_array($cur) || !array_key_exists($p, $cur)) return null;
    $cur = $cur[$p];
  }
  return $cur;
}
