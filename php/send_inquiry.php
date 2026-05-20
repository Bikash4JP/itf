<?php
/**
 * send_inquiry.php — お問い合わせフォーム送信ハンドラー
 * 株式会社アイティーエフ
 *
 * POST /php/send_inquiry.php
 * Receives form data, validates, and sends via server PHP mail().
 * Returns JSON: { "ok": true } or { "ok": false, "error": "..." }
 */

// ── 設定 ──────────────────────────────────────────────────
define('MAIL_TO',      'info@it-future.jp');          // 受信先メール
define('MAIL_FROM',    'noreply@it-future.jp');        // 差出人（サーバードメイン一致）
define('MAIL_CC',      'bikash4jp@gmail.com');         // CC 先
define('SITE_NAME',    '株式会社アイティーエフ');
define('RATE_LIMIT',   3);                             // 同一IPで1時間以内の最大送信数
// ─────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// ── セッション & CSRF ──────────────────────────────────────
session_start();

// CSRF トークン検証（honeypot 兼用）
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token   = $_SESSION['inquiry_csrf'] ?? '';

if (empty($session_token) || !hash_equals($session_token, $submitted_token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF validation failed']);
    exit;
}

// ── ハニーポット（ボット対策）──────────────────────────────
// inquiry.html に hidden フィールド <input name="website" style="display:none"> がある
if (!empty($_POST['website'])) {
    // ボットが入力した場合 — 成功に見せかけてサイレント無視
    echo json_encode(['ok' => true]);
    exit;
}

// ── レート制限 ────────────────────────────────────────────
$ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$key = 'inquiry_rate_' . md5($ip);

if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ['count' => 0, 'first' => time()];
}

$rate = &$_SESSION[$key];

// 1時間経過したらリセット
if (time() - $rate['first'] > 3600) {
    $rate = ['count' => 0, 'first' => time()];
}

$rate['count']++;

if ($rate['count'] > RATE_LIMIT) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => '送信回数の上限に達しました。しばらくしてから再度お試しください。']);
    exit;
}

// ── 入力取得 & サニタイズ ────────────────────────────────
function clean(string $val): string {
    return htmlspecialchars(trim(strip_tags($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = clean($_POST['phone']  ?? '');
$company = clean($_POST['company'] ?? '');
$message = clean($_POST['message'] ?? '');

// inquiry は配列で来る場合も対応
if (is_array($_POST['inquiry'] ?? null)) {
    $inquiry = implode('、', array_map('clean', $_POST['inquiry']));
} else {
    $inquiry = clean($_POST['inquiry'] ?? '');
}

// ── バリデーション ─────────────────────────────────────────
$errors = [];

if (mb_strlen($name) < 1 || mb_strlen($name) > 100) {
    $errors[] = '氏名を入力してください';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) {
    $errors[] = '有効なメールアドレスを入力してください';
}
if (!preg_match('/^[0-9]{10,11}$/', preg_replace('/[-\s]/', '', $phone))) {
    $errors[] = '有効な電話番号を入力してください（10〜11桁）';
}
if (mb_strlen($company) < 1 || mb_strlen($company) > 200) {
    $errors[] = '会社名を入力してください';
}
if (empty($inquiry)) {
    $errors[] = 'お問い合わせ内容を選択してください';
}
// ヘッダーインジェクション対策
foreach ([$name, $company, $email] as $field) {
    if (preg_match('/[\r\n]/', $field)) {
        $errors[] = '不正な文字が含まれています';
        break;
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' / ', $errors)]);
    exit;
}

// ── メール本文作成 ─────────────────────────────────────────
$now          = date('Y年m月d日 H:i', time());
$mail_subject = '【ITFお問い合わせ】' . $company . ' - ' . $name;

$mail_body = <<<BODY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  {$site_name}ホームページ　お問い合わせ
  受信日時：{$now}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お名前
  {$name}

■ メールアドレス
  {$email}

■ 電話番号
  {$phone}

■ 会社名
  {$company}

■ お問い合わせ内容
  {$inquiry}

■ メッセージ
  {$message}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
このメールはホームページのお問い合わせフォームから自動送信されました。
BODY;

// ── 定数を変数に展開（heredoc内）─────────────────────────
$site_name = SITE_NAME;
$mail_body = <<<BODY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  株式会社アイティーエフ ホームページ お問い合わせ
  受信日時：{$now}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

■ お名前         {$name}
■ メールアドレス  {$email}
■ 電話番号        {$phone}
■ 会社名         {$company}
■ お問い合わせ内容 {$inquiry}

■ メッセージ
{$message}

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
このメールはホームページのお問い合わせフォームから自動送信されました。
お問い合わせ者への返信は、上記メールアドレス宛てにお願いします。
BODY;

// ── メールヘッダー ─────────────────────────────────────────
$headers  = 'From: ' . SITE_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
$headers .= 'Reply-To: ' . $name . ' <' . $email . '>' . "\r\n";
$headers .= 'Cc: ' . MAIL_CC . "\r\n";
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$headers .= 'Content-Transfer-Encoding: 8bit' . "\r\n";
$headers .= 'X-Mailer: ITF-Contact-Form/1.0' . "\r\n";

// ── 送信 ──────────────────────────────────────────────────
$subject_encoded = '=?UTF-8?B?' . base64_encode($mail_subject) . '?=';

$sent = mail(MAIL_TO, $subject_encoded, $mail_body, $headers);

// ── 自動返信（お客様宛て）──────────────────────────────────
$auto_reply_subject = '=?UTF-8?B?' . base64_encode('【アイティーエフ】お問い合わせを受け付けました') . '?=';
$auto_reply_body = <<<AUTO
{$name} 様

この度はアイティーエフのホームページよりお問い合わせいただき、
誠にありがとうございます。

以下の内容でお問い合わせを受け付けました。
担当者より折り返しご連絡いたします。
（営業時間：平日 9:30〜18:00）

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
■ お名前         {$name}
■ 会社名         {$company}
■ お問い合わせ内容 {$inquiry}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

──────────────────────────────────
株式会社アイティーエフ（Illuminate The Future）
〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F
TEL：06-6644-1800　Email：info@it-future.jp
URL：https://it-future.jp/
──────────────────────────────────
※このメールは自動送信されています。返信はお受けできません。
AUTO;

$auto_headers  = 'From: ' . SITE_NAME . ' <' . MAIL_FROM . '>' . "\r\n";
$auto_headers .= 'Reply-To: ' . MAIL_TO . "\r\n";
$auto_headers .= 'MIME-Version: 1.0' . "\r\n";
$auto_headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
$auto_headers .= 'Content-Transfer-Encoding: 8bit' . "\r\n";

mail($email, $auto_reply_subject, $auto_reply_body, $auto_headers);

// ── レスポンス ─────────────────────────────────────────────
if ($sent) {
    // CSRFトークンを再生成（再送信防止）
    $_SESSION['inquiry_csrf'] = bin2hex(random_bytes(32));

    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    error_log('[ITF inquiry] mail() failed — to:' . MAIL_TO . ' from:' . $name . ' <' . $email . '>');
    echo json_encode(['ok' => false, 'error' => 'メール送信に失敗しました。お電話（06-6644-1800）またはメール（info@it-future.jp）でお問い合わせください。']);
}
