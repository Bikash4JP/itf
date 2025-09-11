<?php
// /home/it-future/www/itf/rireki/php/adapter_xlsx.php
require_once __DIR__ . '/bootstrap.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf as PdfDompdf;
use PhpOffice\PhpSpreadsheet\Settings;

/**
 * Fill an Excel template using mapping JSON and data, then export PDF.
 *
 * @param array  $data         Canonical data (see buildCanonicalData()).
 * @param string $mappingFile  Path to mappings/templateA.json.
 * @param string $outDir       Directory to write PDFs/XLSX (absolute).
 * @param string $token        64-hex token; used as filename.
 * @return array [ 'ok'=>bool, 'pdf'=>string|null, 'xlsx'=>string|null, 'err'=>string|null ]
 */
function rireki_render_pdf(array $data, string $mappingFile, string $outDir, string $token): array {
  try {
    if (!is_readable($mappingFile)) {
      return ['ok'=>false, 'pdf'=>null, 'xlsx'=>null, 'err'=>"Mapping not readable: $mappingFile"];
    }
    $map = json_decode(file_get_contents($mappingFile), true, 512, JSON_THROW_ON_ERROR);

    $templatePath = realpath(dirname($mappingFile) . '/' . ltrim($map['template_file'], './'));
    if (!$templatePath || !is_readable($templatePath)) {
      return ['ok'=>false, 'pdf'=>null, 'xlsx'=>null, 'err'=>"Template not readable."];
    }

    // Load template
    $spreadsheet = IOFactory::load($templatePath);
    $sheetName = $map['sheet_name'] ?? null;
    if ($sheetName && $spreadsheet->sheetNameExists($sheetName)) {
      $sheet = $spreadsheet->getSheetByName($sheetName);
      $spreadsheet->setActiveSheetIndexByName($sheetName);
    } else {
      $sheet = $spreadsheet->getActiveSheet();
    }

    // Singles
    if (!empty($map['singles'])) {
      foreach ($map['singles'] as $key => $cell) {
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        $sheet->setCellValueExplicit($cell, (string)$val);
      }
    }

    // Blocks (multiline with wrap)
    if (!empty($map['blocks'])) {
      foreach ($map['blocks'] as $key => $conf) {
        $cell = $conf['cell'] ?? null;
        if (!$cell) continue;
        $val = getValueByKeyPath($data, $key);
        if ($val === null) continue;
        // normalize newlines
        $val = str_replace(["\r\n","\r"], "\n", (string)$val);
        $sheet->setCellValueExplicit($cell, $val);
        if (!empty($conf['wrap'])) {
          $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
          $sheet->getStyle($cell)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }
      }
    }

    // Join rules (e.g., company_with_title)
    $joins = $map['joins'] ?? [];
    $dataWithJoins = applyJoinRules($data, $joins);

    // Repeaters
    if (!empty($map['repeaters'])) {
      foreach ($map['repeaters'] as $repKey => $repConf) {
        $rows = $dataWithJoins[$repKey] ?? [];
        if (!is_array($rows)) continue;

        $startRow = (int)($repConf['start_row'] ?? 0);
        $rowStep  = (int)($repConf['row_step'] ?? 1);
        $maxRows  = (int)($repConf['max_rows'] ?? count($rows));
        $cols     = $repConf['columns'] ?? [];

        $limit = min(count($rows), $maxRows);
        for ($i=0; $i<$limit; $i++) {
          $rowData = $rows[$i];
          $targetRow = $startRow + ($i * $rowStep);
          foreach ($cols as $field => $colLetter) {
            $val = $rowData[$field] ?? '';
            $coord = $colLetter . $targetRow;
            $sheet->setCellValueExplicit($coord, (string)$val);
          }
        }
      }
    }

    // Photo
    if (!empty($map['photo']) && !empty($data['photo_path'])) {
      $ph = $data['photo_path'];
      if (is_readable($ph)) {
        $anchor = $map['photo']['anchor_cell'] ?? 'A1';
        $w = (int)($map['photo']['width_px'] ?? 300);
        $h = (int)($map['photo']['height_px'] ?? 400);

        $img = new Drawing();
        $img->setName('Photo');
        $img->setDescription('Applicant Photo');
        $img->setPath($ph);
        // PhpSpreadsheet keeps aspect ratio; we try to fit height first
        $img->setHeight($h);
        // If you want width control instead, use setWidth($w)

        $img->setCoordinates($anchor);
        $img->setOffsetX(0);
        $img->setOffsetY(0);
        $img->setWorksheet($sheet);
      }
    }

    // Ensure output dir
    if (!is_dir($outDir)) {
      @mkdir($outDir, 0750, true);
    }

    $xlsxPath = rtrim($outDir,'/') . '/' . $token . '.xlsx';
    $pdfPath  = rtrim($outDir,'/') . '/' . $token . '.pdf';

    // Save XLSX (for audit/debug)
    $xlsxWriter = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $xlsxWriter->save($xlsxPath);

    // PDF export via Dompdf writer
    // Temporary folder for renderer
    $tmp = rireki_path('tmp');
    if (!is_dir($tmp)) { @mkdir($tmp, 0750, true); }
    PdfDompdf::setTemporaryFolder($tmp);

    // If you installed Japanese fonts for Dompdf (e.g., IPAex), set default:
    // PdfDompdf::setDefaultFont('ipaexg'); // uncomment if font installed & known

    // Register and write
    IOFactory::registerWriter('Pdf', PdfDompdf::class);
    $pdfWriter = IOFactory::createWriter($spreadsheet, 'Pdf');
    $pdfWriter->save($pdfPath);

    return ['ok'=>true, 'pdf'=>$pdfPath, 'xlsx'=>$xlsxPath, 'err'=>null];

  } catch (Throwable $e) {
    return ['ok'=>false, 'pdf'=>null, 'xlsx'=>null, 'err'=>$e->getMessage()];
  }
}

/** Helpers **/

/** Resolve nested key path like 'personal.name_kanji' */
function getValueByKeyPath(array $data, string $keyPath) {
  $parts = explode('.', $keyPath);
  $cur = $data;
  foreach ($parts as $p) {
    if (!is_array($cur) || !array_key_exists($p, $cur)) return null;
    $cur = $cur[$p];
  }
  return $cur;
}

/** Apply join rules to build derived fields inside repeaters. */
function applyJoinRules(array $data, array $joins): array {
  if (empty($joins)) return $data;
  $out = $data;

  foreach ($joins as $fieldPath => $pattern) {
    // Example: "experience.company_with_title": "{company}（{title}）"
    $parts = explode('.', $fieldPath);
    if (count($parts) < 2) continue;
    $repKey = $parts[0]; // e.g., "experience"
    $destKey = $parts[1]; // e.g., "company_with_title"

    if (!isset($out[$repKey]) || !is_array($out[$repKey])) continue;

    foreach ($out[$repKey] as $i => $row) {
      $val = $pattern;
      // replace {field} from row
      foreach ($row as $k => $v) {
        $val = str_replace('{'.$k.'}', (string)$v, $val);
      }
      // remove unreplaced tokens
      $val = preg_replace('/\{[^\}]+\}/', '', $val);
      $out[$repKey][$i][$destKey] = trim($val);
    }
  }
  return $out;
}

/**
 * Build canonical array from POST (and uploaded photo).
 * Expects field names documented in comments.
 */
function buildCanonicalData(array $post, ?array $files): array {
  // Singles
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

  // Arrays: education
  $education = [];
  $eduYears  = $post['edu_year']  ?? [];
  $eduMonths = $post['edu_month'] ?? [];
  $eduSchool = $post['edu_school']?? [];
  $nEdu = max(count($eduYears), count($eduMonths), count($eduSchool));
  for ($i=0; $i<$nEdu; $i++) {
    $education[] = [
      'year'   => trim($eduYears[$i]  ?? ''),
      'month'  => trim($eduMonths[$i] ?? ''),
      'school' => trim($eduSchool[$i] ?? ''),
    ];
  }

  // Arrays: experience
  $experience = [];
  $expYears   = $post['exp_year']   ?? [];
  $expMonths  = $post['exp_month']  ?? [];
  $expCompany = $post['exp_company']?? [];
  $expTitle   = $post['exp_title']  ?? [];
  $nExp = max(count($expYears), count($expMonths), count($expCompany), count($expTitle));
  for ($i=0; $i<$nExp; $i++) {
    $experience[] = [
      'year'    => trim($expYears[$i]   ?? ''),
      'month'   => trim($expMonths[$i]  ?? ''),
      'company' => trim($expCompany[$i] ?? ''),
      'title'   => trim($expTitle[$i]   ?? ''),
    ];
  }

  // Arrays: licenses
  $licenses = [];
  $licYears = $post['lic_year']  ?? [];
  $licMonths= $post['lic_month'] ?? [];
  $licNames = $post['lic_name']  ?? [];
  $nLic = max(count($licYears), count($licMonths), count($licNames));
  for ($i=0; $i<$nLic; $i++) {
    $licenses[] = [
      'year'       => trim($licYears[$i]  ?? ''),
      'month'      => trim($licMonths[$i] ?? ''),
      'certificate'=> trim($licNames[$i]  ?? ''),
    ];
  }

  $pr = [
    'self_pr' => trim($post['self_pr'] ?? ''),
  ];
  $preferences = [
    'hopes' => trim($post['hopes'] ?? ''),
  ];

  // Photo upload path (already moved before calling this can also be acceptable)
  $photoPath = $post['photo_path'] ?? ''; // fallback
  if ($files && isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
    $photoPath = moveUploadedPhoto($files['photo']);
  }

  return [
    'personal'   => $personal,
    'address'    => $address,
    'contact'    => $contact,
    'education'  => array_values(array_filter($education,  fn($r)=>implode('', $r) !== '')),
    'experience' => array_values(array_filter($experience, fn($r)=>implode('', $r) !== '')),
    'licenses'   => array_values(array_filter($licenses,   fn($r)=>implode('', $r) !== '')),
    'pr'         => $pr,
    'preferences'=> $preferences,
    'photo_path' => $photoPath,
  ];
}

/** Save uploaded photo to uploads/photos and return absolute path. */
function moveUploadedPhoto(array $file): string {
  $dir = rireki_path('uploads/photos');
  if (!is_dir($dir)) @mkdir($dir, 0750, true);

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png'], true)) $ext = 'jpg';

  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = rtrim($dir,'/') . '/' . $name;

  // Basic size/type checks
  if ($file['size'] > 5 * 1024 * 1024) throw new RuntimeException('Photo too large');
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
  if (!in_array($mime, ['image/jpeg','image/png'], true)) {
    throw new RuntimeException('Photo type not allowed');
  }
  if (!move_uploaded_file($file['tmp_name'], $dest)) {
    throw new RuntimeException('Failed to move photo');
  }
  return $dest;
}
