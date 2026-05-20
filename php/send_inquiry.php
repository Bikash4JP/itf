<?php
/**
 * send_inquiry.php — お問い合わせフォーム送信ハンドラー
 * 株式会社アイティーエフ
 * Returns JSON: { "ok": true } or { "ok": false, "error": "..." }
 */

// session_start() は必ず最初に（header()より前）
session_start();

// JSONレスポンスヘッダー
header('Content-Type: application/json; charset=utf-8');

// ── 設定 ───────────────────────────────────────────────────
$MAIL_TO   = 'info@it-future.jp';
$MAIL_FROM = 'noreply@it-future.jp';
$MAIL_CC   = 'bikash4jp@gmail.com';
$SITE_NAME = '株式会社アイティーエフ';
$RATE_MAX  = 5;   // 同一IPで1時間に最大5件
// ─────────────────────────────────────────────────────────

// POST のみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// ── CSRF 検証 ───────────────────────────────────────────────
// get_csrf_token.php が保存するキー: 'csrf_token_inquiry'
$submitted = trim($_POST['csrf_token'] ?? '');
$stored    = $_SESSION['csrf_token_inquiry'] ?? '';

if (empty($stored) || empty($submitted) || !hash_equals($stored, $submitted)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'セキュリティトークンが無効です。ページを再読み込みして再送信してください。']);
    exit;
}

// ── ハニーポット（ボット対策）──────────────────────────────
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]); // ボットには成功に見せかける
    exit;
}

// ── レート制限 ─────────────────────────────────────────────
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateKey = 'rate_inquiry_' . md5($ip);

if (!isset($_SESSION[$rateKey])) {
    $_SESSION[$rateKey] = ['count' => 0, 'first' => time()];
}

// 1時間経過でリセット
if ((time() - $_SESSION[$rateKey]['first']) > 3600) {
    $_SESSION[$rateKey] = ['count' => 0, 'first' => time()];
}

$_SESSION[$rateKey]['count']++;

if ($_SESSION[$rateKey]['count'] > $RATE_MAX) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => '短時間に送信が集中しています。しばらく経ってから再度お試しください。']);
    exit;
}

// ── サニタイズ関数 ─────────────────────────────────────────
function s(string $v): string {
    return htmlspecialchars(trim(strip_tags($v)), ENT_QUOTES, 'UTF-8');
}

// ── 入力取得 ───────────────────────────────────────────────
$name    = s($_POST['name']    ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = s($_POST['phone']   ?? '');
$company = s($_POST['company'] ?? '');
$message = s($_POST['message'] ?? '');

// inquiry: 文字列または配列に対応
$rawInquiry = $_POST['inquiry'] ?? '';
if (is_array($rawInquiry)) {
    $inquiry = implode('、', array_map('s', $rawInquiry));
} else {
    $inquiry = s($rawInquiry);
}

// ── バリデーション ─────────────────────────────────────────
$errors = [];

if (mb_strlen($name, 'UTF-8') < 1) {
    $errors[] = '氏名を入力してください';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '有効なメールアドレスを入力してください';
}
$phoneDigits = preg_replace('/[\-\s\(\)]/', '', $phone);
if (!preg_match('/^[0-9]{10,11}$/', $phoneDigits)) {
    $errors[] = '有効な電話番号を入力してください（ハイフンなし10〜11桁）';
}
if (mb_strlen($company, 'UTF-8') < 1) {
    $errors[] = '会社名を入力してください';
}
if (empty($inquiry)) {
    $errors[] = 'お問い合わせ内容を選択してください';
}
// ヘッダーインジェクション防止
foreach ([$name, $company, $email, $phone] as $f) {
    if (preg_match('/[\r\n]/', $f)) {
        $errors[] = '不正な文字が含まれています';
        break;
    }
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(' | ', $errors)]);
    exit;
}

// ── メール本文（社内向け）─────────────────────────────────
$now         = date('Y年m月d日 H:i', time());
$mailSubject = '【ITFお問い合わせ】' . $company . ' - ' . $name;
$msgLine     = empty($message) ? '（記入なし）' : $message;

$mailBody =
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "  {$SITE_NAME} ホームページ お問い合わせ\n" .
    "  受信日時：{$now}\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
    "■ お名前          : {$name}\n" .
    "■ メールアドレス  : {$email}\n" .
    "■ 電話番号        : {$phone}\n" .
    "■ 会社名          : {$company}\n" .
    "■ お問い合わせ内容 : {$inquiry}\n\n" .
    "■ メッセージ\n{$msgLine}\n\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "このメールはホームページの問い合わせフォームから自動送信されました。\n" .
    "返信は差出人メールアドレス ({$email}) 宛てにお願いします。\n";

// ── メールヘッダー（社内向け）──────────────────────────────
$headers =
    "From: {$SITE_NAME} <{$MAIL_FROM}>\r\n" .
    "Reply-To: {$name} <{$email}>\r\n" .
    "Cc: {$MAIL_CC}\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n" .
    "Content-Transfer-Encoding: 8bit\r\n" .
    "X-Mailer: ITF-Contact-Form/2.0\r\n";

// ── 社内メール送信 ─────────────────────────────────────────
$subjectB64 = '=?UTF-8?B?' . base64_encode($mailSubject) . '?=';
$sent = @mail($MAIL_TO, $subjectB64, $mailBody, $headers);

// ── 自動返信（お客様宛て）──────────────────────────────────
$autoSubject = '=?UTF-8?B?' . base64_encode('【アイティーエフ】お問い合わせを受け付けました') . '?=';

$autoBody =
    "{$name} 様\n\n" .
    "この度はアイティーエフのホームページよりお問い合わせいただき、\n" .
    "誠にありがとうございます。\n\n" .
    "以下の内容でお問い合わせを受け付けました。\n" .
    "担当者より折り返しご連絡いたします。\n" .
    "（営業時間：平日 9:30〜18:00）\n\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
    "■ お名前          : {$name}\n" .
    "■ 会社名          : {$company}\n" .
    "■ お問い合わせ内容 : {$inquiry}\n" .
    "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
    "──────────────────────────────────\n" .
    "{$SITE_NAME}（Illuminate The Future）\n" .
    "〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F\n" .
    "TEL：06-6644-1800  Email：{$MAIL_TO}\n" .
    "URL：https://it-future.jp/\n" .
    "──────────────────────────────────\n" .
    "※このメールは自動送信されています。このメールへの返信はお受けできません。\n";

$autoHeaders =
    "From: {$SITE_NAME} <{$MAIL_FROM}>\r\n" .
    "Reply-To: {$MAIL_TO}\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n" .
    "Content-Transfer-Encoding: 8bit\r\n";

@mail($email, $autoSubject, $autoBody, $autoHeaders);

// ── レスポンス ─────────────────────────────────────────────
if ($sent) {
    // CSRF再生成（再送信防止）
    $_SESSION['csrf_token_inquiry'] = bin2hex(random_bytes(32));
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    error_log('[ITF] send_inquiry mail() failed — ' . $name . ' <' . $email . '>');
    echo json_encode([
        'ok'    => false,
        'error' => 'メール送信に失敗しました。お電話（06-6644-1800）またはメール（info@it-future.jp）でお問い合わせください。'
    ]);
}
