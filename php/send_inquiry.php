<?php
/**
 * send_inquiry.php — お問い合わせフォーム送信ハンドラー
 * 株式会社アイティーエフ  |  php/send_inquiry.php
 *
 * Uses mb_send_mail() with -f envelope sender so the Return-Path
 * matches info@it-future.jp — required to pass Gmail SPF checks
 * on Sakura Internet shared hosting.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// ── 設定 ─────────────────────────────────────────────────────────────
const MAIL_TO = 'info@it-future.jp';
const MAIL_NOTIFY = 'bikash@it-future.jp';
const MAIL_FROM = 'noreply@it-future.jp'; // display From (envelope stays info@ via -f)
const ENVELOPE = '-f info@it-future.jp'; // sets Return-Path for SPF
const SITE_NAME = '株式会社アイティーエフ';
const SITE_URL = 'https://it-future.jp/';
const SITE_TEL = '06-6644-1800';
const RATE_MAX = 5;
// ─────────────────────────────────────────────────────────────────────

mb_language('Japanese');
mb_internal_encoding('UTF-8');

function reply(bool $ok, string $error = ''): void
{
    http_response_code(200);
    echo json_encode(
        $ok ? ['ok' => true] : ['ok' => false, 'error' => $error],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(false, 'Method Not Allowed');
}

// ── CSRF ──────────────────────────────────────────────────────────────
$submitted = trim($_POST['csrf_token'] ?? '');
$stored = $_SESSION['csrf_token_inquiry'] ?? '';
if (empty($stored) || empty($submitted) || !hash_equals($stored, $submitted)) {
    reply(false, 'セキュリティトークンが無効です。ページを再読み込みして再送信してください。');
}

// ── ハニーポット ──────────────────────────────────────────────────────
if (!empty($_POST['url_field'])) {
    reply(true);
}

// ── レート制限 ────────────────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateKey = 'rate_inquiry_' . md5($ip);
if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'first' => time()];
}
if ((time() - $_SESSION[$rateKey]['first']) > 3600) {
    $_SESSION[$rateKey] = ['count' => 0, 'first' => time()];
}
$_SESSION[$rateKey]['count']++;
if ($_SESSION[$rateKey]['count'] > RATE_MAX) {
    reply(false, '短時間に送信が集中しています。しばらく経ってから再度お試しください。');
}

// ── サニタイズ ────────────────────────────────────────────────────────
function clean(string $v): string
{
    return str_replace(
        ["\r", "\n"],
        '',
        htmlspecialchars(trim(strip_tags($v)), ENT_QUOTES, 'UTF-8')
    );
}

// ── 入力取得 ──────────────────────────────────────────────────────────
$name = clean($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = clean($_POST['phone'] ?? '');
$company = clean($_POST['company'] ?? '');
$message = clean($_POST['message'] ?? '');

$rawInquiry = $_POST['inquiry'] ?? '';
$inquiry = is_array($rawInquiry)
    ? implode('、', array_map('clean', $rawInquiry))
    : clean($rawInquiry);

// ── バリデーション ────────────────────────────────────────────────────
$errors = [];
if (mb_strlen($name) < 1)
    $errors[] = '氏名を入力してください';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = '有効なメールアドレスを入力してください';
if (!preg_match('/^\d{10,11}$/', preg_replace('/[\-\s\(\)]/', '', $phone)))
    $errors[] = '有効な電話番号を入力してください（10〜11桁）';
if (mb_strlen($company) < 1)
    $errors[] = '会社名を入力してください';
if (empty($inquiry))
    $errors[] = 'お問い合わせ内容を選択してください';

if (!empty($errors)) {
    reply(false, implode(' | ', $errors));
}

// ── From ヘッダー (Base64エンコード) ──────────────────────────────────
$fromEncoded = '=?UTF-8?B?' . base64_encode(SITE_NAME) . '?=';
$fromHeader = $fromEncoded . ' <' . MAIL_FROM . '>';

// ── メール本文 ────────────────────────────────────────────────────────
$now = date('Y年m月d日 H:i');
$msgLine = empty($message) ? '（記入なし）' : $message;

$mailBody =
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "  " . SITE_NAME . " お問い合わせ\n" .
    "  受信日時：{$now}\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
    "■ お名前           : {$name}\n" .
    "■ メールアドレス   : {$email}\n" .
    "■ 電話番号         : {$phone}\n" .
    "■ 会社名           : {$company}\n" .
    "■ お問い合わせ内容 : {$inquiry}\n\n" .
    "■ メッセージ\n{$msgLine}\n\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "返信先：{$email}\n" .
    "※このメールはフォームから自動送信されました。\n";

$mailSubject = '【ITFお問い合わせ】' . $company . ' - ' . $name;

$baseHeaders =
    "From: {$fromHeader}\r\n" .
    "Reply-To: {$name} <{$email}>\r\n" .
    "X-Mailer: ITF-Contact-Form/3.0\r\n";

// ── ① info@it-future.jp へ送信 ────────────────────────────────────────
// 5th param (-f) sets the envelope sender = Return-Path.
// Without this, Sakura's sendmail sets it to a system address,
// causing Gmail SPF failure and silent discard.
$sent1 = mb_send_mail(MAIL_TO, $mailSubject, $mailBody, $baseHeaders, ENVELOPE);
error_log('[ITF] To=' . MAIL_TO . ' ' . ($sent1 ? 'OK' : 'FAIL') . ' @ ' . date('Y-m-d H:i:s'));

if (!$sent1) {
    reply(false, 'メール送信に失敗しました。お電話（' . SITE_TEL . '）または ' . MAIL_TO . ' でお問い合わせください。');
}

// ── ② bikash@it-future.jp へ個別送信 ─────────────────────────────────
$sent2 = mb_send_mail(MAIL_NOTIFY, $mailSubject, $mailBody, $baseHeaders, ENVELOPE);
error_log('[ITF] To=' . MAIL_NOTIFY . ' ' . ($sent2 ? 'OK' : 'FAIL') . ' @ ' . date('Y-m-d H:i:s'));

// ── ③ 自動返信（お客様宛て）──────────────────────────────────────────
$autoSubject = '【アイティーエフ】お問い合わせを受け付けました';
$autoBody =
    "{$name} 様\n\n" .
    "この度はアイティーエフのホームページよりお問い合わせいただき、\n" .
    "誠にありがとうございます。\n\n" .
    "以下の内容でお問い合わせを受け付けました。\n" .
    "担当者より折り返しご連絡いたします。\n" .
    "（営業時間：平日 9:30〜18:00）\n\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "■ お名前           : {$name}\n" .
    "■ 会社名           : {$company}\n" .
    "■ お問い合わせ内容 : {$inquiry}\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
    "──────────────────────────────────\n" .
    SITE_NAME . "（Illuminate The Future）\n" .
    "〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F\n" .
    "TEL：" . SITE_TEL . "  Email：" . MAIL_TO . "\n" .
    "URL：" . SITE_URL . "\n" .
    "──────────────────────────────────\n" .
    "※このメールは自動送信です。このメールへの返信はお受けできません。\n";

$autoHeaders =
    "From: {$fromHeader}\r\n" .
    "Reply-To: " . MAIL_TO . "\r\n" .
    "X-Mailer: ITF-Contact-Form/3.0\r\n";

mb_send_mail($email, $autoSubject, $autoBody, $autoHeaders, ENVELOPE);

// ── 成功 ──────────────────────────────────────────────────────────────
$_SESSION['csrf_token_inquiry'] = bin2hex(random_bytes(32));
reply(true);