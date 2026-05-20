<?php
// ================================================================
// POST: お問い合わせフォーム送信ハンドラー
// GET:  ニュース一覧取得（従来通り）
// ================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    session_start();
    header('Content-Type: application/json; charset=utf-8');

    // ── ハニーポット（ボット対策）──────────────────────────────
    if (!empty($_POST['url_field'])) {
        echo json_encode(['ok' => true]);
        exit;
    }

    // ── レート制限（1時間に5件）──────────────────────────────
    $ip = md5($_SERVER['REMOTE_ADDR'] ?? '0');
    $key = 'rl_' . $ip;
    if (!isset($_SESSION[$key]))
        $_SESSION[$key] = ['n' => 0, 't' => time()];
    if (time() - $_SESSION[$key]['t'] > 3600)
        $_SESSION[$key] = ['n' => 0, 't' => time()];
    $_SESSION[$key]['n']++;
    if ($_SESSION[$key]['n'] > 5) {
        http_response_code(429);
        echo json_encode(['ok' => false, 'error' => '送信回数の上限に達しました。しばらくしてから再度お試しください。']);
        exit;
    }

    // ── サニタイズ ───────────────────────────────────────────
    function xss(string $v): string
    {
        return htmlspecialchars(trim(strip_tags($v)), ENT_QUOTES, 'UTF-8');
    }

    $name = xss($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = xss($_POST['phone'] ?? '');
    $company = xss($_POST['company'] ?? '');
    $message = xss($_POST['message'] ?? '');
    $raw = $_POST['inquiry'] ?? '';
    $inquiry = is_array($raw) ? implode('、', array_map('xss', $raw)) : xss($raw);

    // ── バリデーション ───────────────────────────────────────
    $err = [];
    if (mb_strlen($name) < 1)
        $err[] = '氏名を入力してください';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $err[] = '有効なメールアドレスを入力してください';
    if (!preg_match('/^[0-9]{10,11}$/', preg_replace('/[\-\s]/', '', $phone)))
        $err[] = '有効な電話番号を入力してください（10〜11桁）';
    if (mb_strlen($company) < 1)
        $err[] = '会社名を入力してください';
    if (empty($inquiry))
        $err[] = 'お問い合わせ内容を選択してください';
    foreach ([$name, $email, $company] as $f) {
        if (preg_match('/[\r\n]/', $f)) {
            $err[] = '不正な文字が含まれています';
            break;
        }
    }

    if (!empty($err)) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => implode(' | ', $err)]);
        exit;
    }

    // ── メール設定 ───────────────────────────────────────────
    $MAIL_TO = 'info@it-future.jp';
    $MAIL_FROM = 'noreply@it-future.jp';
    $MAIL_CC = 'bikash4jp@gmail.com';
    $SITE = '株式会社アイティーエフ';
    $now = date('Y年m月d日 H:i');
    $msg = empty($message) ? '（記入なし）' : $message;

    // ── 社内向けメール本文 ────────────────────────────────────
    $body =
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
        " {$SITE} お問い合わせ受信\n" .
        " 受信日時：{$now}\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
        "お名前　　：{$name}\n" .
        "メール　　：{$email}\n" .
        "電話番号　：{$phone}\n" .
        "会社名　　：{$company}\n" .
        "お問い合わせ内容：{$inquiry}\n\n" .
        "メッセージ：\n{$msg}\n\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
        "返信先：{$email}\n";

    $subj = '=?UTF-8?B?' . base64_encode("【ITFお問い合わせ】{$company} - {$name}") . '?=';
    $hdrs =
        "From: {$SITE} <{$MAIL_FROM}>\r\n" .
        "Reply-To: {$name} <{$email}>\r\n" .
        "Cc: {$MAIL_CC}\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n";

    $sent = @mail($MAIL_TO, $subj, $body, $hdrs);

    // ── 自動返信（お客様宛て）────────────────────────────────
    $aBody =
        "{$name} 様\n\n" .
        "この度はアイティーエフへのお問い合わせありがとうございます。\n" .
        "担当者より折り返しご連絡いたします。（平日9:30〜18:00）\n\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
        "お名前：{$name}\n" .
        "会社名：{$company}\n" .
        "お問い合わせ内容：{$inquiry}\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n" .
        "{$SITE}（Illuminate The Future）\n" .
        "〒556-0017 大阪府大阪市浪速区湊町1-4-38 近鉄新難波ビル10F\n" .
        "TEL：06-6644-1800  Email：{$MAIL_TO}\n" .
        "URL：https://it-future.jp/\n\n" .
        "※このメールは自動送信です。このメールへの返信はお受けできません。\n";

    $aSubj = '=?UTF-8?B?' . base64_encode('【アイティーエフ】お問い合わせを受け付けました') . '?=';
    $aHdrs =
        "From: {$SITE} <{$MAIL_FROM}>\r\n" .
        "Reply-To: {$MAIL_TO}\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n" .
        "Content-Transfer-Encoding: 8bit\r\n";

    @mail($email, $aSubj, $aBody, $aHdrs);

    // ── レスポンス ───────────────────────────────────────────
    if ($sent) {
        echo json_encode(['ok' => true]);
    } else {
        http_response_code(500);
        error_log('[ITF inquiry] mail() failed: ' . $name . ' <' . $email . '>');
        echo json_encode(['ok' => false, 'error' => 'メール送信に失敗しました。お電話（06-6644-1800）またはメール（info@it-future.jp）でお問い合わせください。']);
    }
    exit;
}

// ================================================================
// GET: ニュース一覧取得（従来通り）
// ================================================================

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db_connect.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_type = 'news' ORDER BY date DESC");
    $stmt->execute();
    $newsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($newsData as &$item) {
        if (!empty($item['image'])) {
            $uploads_dir = __DIR__ . '/../uploads/';
            if (!file_exists($uploads_dir)) {
                mkdir($uploads_dir, 0775, true);
            }
            $item['image'] = str_replace('../uploads/', 'uploads/', $item['image']);
            $image_path = __DIR__ . '/../' . $item['image'];
            if (!file_exists($image_path)) {
                $item['image'] = null;
            }
        } else {
            $item['image'] = null;
        }
    }
    unset($item);

    echo json_encode($newsData);
} catch (PDOException $e) {
    file_put_contents("/home/it-future/www/itf/logs/db_error.log", "Fetch News Error: " . $e->getMessage() . " | Time: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(["error" => "データベースエラー: " . $e->getMessage()]);
}
