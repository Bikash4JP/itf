<?php
// /rireki/kaigo/php/canonical.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/validators.php';

function buildCanonicalData(array $post, ?array $files): array {

  // Singles
  $singles = [
    'name_romaji'      => trim($post['name_romaji'] ?? ''),
    'name_kana'        => trim($post['name_kana'] ?? ''),
    'dob_year'         => trim($post['dob_year'] ?? ''),
    'dob_month'        => trim($post['dob_month'] ?? ''),
    'dob_day'          => trim($post['dob_day'] ?? ''),
    'birthplace'       => trim($post['birthplace'] ?? ''),
    'postal'           => trim($post['postal'] ?? ''),
    'address'          => trim($post['address'] ?? ''),
    'nationality'      => trim($post['nationality'] ?? ''),
    'gender'           => trim($post['gender'] ?? ''),
    'religion'         => trim($post['religion'] ?? ''),
    'marital_status'   => trim($post['marital_status'] ?? ''),
    'contact_phone'    => trim($post['contact_phone'] ?? ''),
    'email'            => trim($post['email'] ?? ''),
    'height_cm'        => trim($post['height_cm'] ?? ''),
    'weight_kg'        => trim($post['weight_kg'] ?? ''),
    'passport_has'     => trim($post['passport_has'] ?? ''),
    'passport_exp_year'=> trim($post['passport_exp_year'] ?? ''),
    'passport_exp_month'=>trim($post['passport_exp_month'] ?? ''),
    'passport_exp_day' => trim($post['passport_exp_day'] ?? ''),
    'passport_no'      => trim($post['passport_no'] ?? ''),
    'past_travel_history' => trim($post['past_travel_history'] ?? ''),
    'recent_entry_year'=> trim($post['recent_entry_year'] ?? ''),
    'recent_entry_month'=>trim($post['recent_entry_month'] ?? ''),
    'recent_entry_day' => trim($post['recent_entry_day'] ?? ''),
    'recent_exit_year' => trim($post['recent_exit_year'] ?? ''),
    'recent_exit_month'=> trim($post['recent_exit_month'] ?? ''),
    'recent_exit_day'  => trim($post['recent_exit_day'] ?? ''),
    'current_status'   => trim($post['current_status'] ?? ''),
    'status_from_year' => trim($post['status_from_year'] ?? ''),
    'status_from_month'=> trim($post['status_from_month'] ?? ''),
    'status_from_day'  => trim($post['status_from_day'] ?? ''),
    'status_to_year'   => trim($post['status_to_year'] ?? ''),
    'status_to_month'  => trim($post['status_to_month'] ?? ''),
    'status_to_day'    => trim($post['status_to_day'] ?? ''),
    'reason_for_resignation'=> trim($post['reason_for_resignation'] ?? ''),
    'self_pr'          => trim($post['self_pr'] ?? ''),
    'motivation'       => trim($post['motivation'] ?? ''),
    'preferences'      => trim($post['preferences'] ?? ''),
    'jp_comm_level'    => trim($post['jp_comm_level'] ?? ''),
    'kanji_rw'         => trim($post['kanji_rw'] ?? ''),
    'blood_type'       => trim($post['blood_type'] ?? ''),
    'english_level'    => trim($post['english_level'] ?? ''),
    'acquaintances_in_japan' => trim($post['acquaintances_in_japan'] ?? ''),
    'jp_friends_count' => trim($post['jp_friends_count'] ?? ''),
    'home_country_friends_in_japan' => trim($post['home_country_friends_in_japan'] ?? ''),
    'smoking'          => trim($post['smoking'] ?? ''),
    'alcohol'          => trim($post['alcohol'] ?? ''),
    'tattoo'           => trim($post['tattoo'] ?? ''),
    'clothes_size'     => trim($post['clothes_size'] ?? ''),
    'shoe_size'        => trim($post['shoe_size'] ?? ''),
    'prayer'           => trim($post['prayer'] ?? ''),
    'fasting'          => trim($post['fasting'] ?? ''),
    'food_rules'       => trim($post['food_rules'] ?? ''),
    'hijab'            => trim($post['hijab'] ?? ''),
    'work_duration_intent' => trim($post['work_duration_intent'] ?? ''),
    'studying_japanese_now' => trim($post['studying_japanese_now'] ?? ''),
    'studying_specialty_now'=> trim($post['studying_specialty_now'] ?? ''),
    'other_agency_or_facility_interview' => trim($post['other_agency_or_facility_interview'] ?? ''),
  ];

  // Education (rows 17–20)
  $eduFromY = $post['edu_from_year'] ?? [];
  $eduFromM = $post['edu_from_month'] ?? [];
  $eduToY   = $post['edu_to_year'] ?? [];
  $eduToM   = $post['edu_to_month'] ?? [];
  $eduInst  = $post['edu_institution'] ?? [];
  $eduFac   = $post['edu_faculty'] ?? [];

  $education = [];
  $N = max(count($eduFromY), count($eduFromM), count($eduToY), count($eduToM), count($eduInst), count($eduFac));
  for ($i=0; $i<$N; $i++) {
    if (trim(($eduFromY[$i]??'').($eduFromM[$i]??'').($eduToY[$i]??'').($eduToM[$i]??'').($eduInst[$i]??'').($eduFac[$i]??''))==='') continue;
    $education[] = [
      'from_year'=> trim($eduFromY[$i] ?? ''),
      'from_month'=>trim($eduFromM[$i] ?? ''),
      'to_year'  => trim($eduToY[$i] ?? ''),
      'to_month' => trim($eduToM[$i] ?? ''),
      'institution'=> trim($eduInst[$i] ?? ''),
      'faculty' => trim($eduFac[$i] ?? ''),
    ];
  }

  // Work (rows 22,25,28,31)
  $workFromY = $post['work_from_year'] ?? [];
  $workFromM = $post['work_from_month'] ?? [];
  $workToY   = $post['work_to_year'] ?? [];
  $workToM   = $post['work_to_month'] ?? [];
  $workOrg   = $post['work_org'] ?? [];
  $workTitle = $post['work_title'] ?? [];
  $workStart = $post['work_time_start'] ?? [];
  $workEnd   = $post['work_time_end'] ?? [];
  $workDays  = $post['work_days_per_week'] ?? [];

  $workBlocks = [];
  $W = max(count($workFromY),count($workFromM),count($workToY),count($workToM),count($workOrg),count($workTitle));
  for ($i=0; $i<$W; $i++) {
    if (trim(($workFromY[$i]??'').($workFromM[$i]??'').($workOrg[$i]??''))==='') continue;
    $workBlocks[] = [
      'from_year'=>trim($workFromY[$i] ?? ''),
      'from_month'=>trim($workFromM[$i] ?? ''),
      'to_year'  =>trim($workToY[$i] ?? ''),
      'to_month' =>trim($workToM[$i] ?? ''),
      'org'      =>trim($workOrg[$i] ?? ''),
      'job_title'=>trim($workTitle[$i] ?? ''),
      'work_time_start'=>trim($workStart[$i] ?? ''),
      'work_time_end'  =>trim($workEnd[$i] ?? ''),
      'work_days_per_week'=>trim($workDays[$i] ?? ''),
    ];
  }

  // Licenses (rows 36–40)
  $licY = $post['lic_year'] ?? [];
  $licM = $post['lic_month'] ?? [];
  $licN = $post['lic_name'] ?? [];
  $licenses = [];
  $L = max(count($licY), count($licM), count($licN));
  for ($i=0; $i<$L; $i++) {
    if (trim(($licY[$i]??'').($licM[$i]??'').($licN[$i]??''))==='') continue;
    $licenses[] = [
      'cert_year'=> trim($licY[$i] ?? ''),
      'cert_month'=>trim($licM[$i] ?? ''),
      'cert_name'=> trim($licN[$i] ?? ''),
    ];
  }

  // Photo
  $photoPath = $post['photo_path'] ?? '';
  if ($files && isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
    $dir = rireki_path('uploads/photos');
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $ext = strtolower(pathinfo($files['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png'])) $ext = 'jpg';
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    move_uploaded_file($files['photo']['tmp_name'], $dest);
    $photoPath = $dest;
  }

  return array_merge($singles, [
    'education'   => $education,
    'work_blocks' => $workBlocks,
    'licenses'    => $licenses,
    'photo_path'  => $photoPath,
  ]);
}
