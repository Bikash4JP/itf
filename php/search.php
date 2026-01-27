<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_path', '/itf');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    echo json_encode(['error' => 'Session expired, please login']);
    exit;
}

require_once 'db_connect.php';

// --- helper: activity log writer (robust fallback) ---
function insertActivityLog(PDO $pdo, string $messageJa, int $staffId, string $username): void {
    $messageJa = trim($messageJa);
    if ($messageJa === '') return;

    // 1) Try richer schema first
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (message_ja, staff_id, username) VALUES (:m, :sid, :u)");
        $stmt->execute([
            ':m' => $messageJa,
            ':sid' => $staffId,
            ':u' => $username,
        ]);
        return;
    } catch (Throwable $e) {
        // ignore and fallback
    }

    // 2) Fallback to minimal schema
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (message_ja) VALUES (:m)");
        $stmt->execute([':m' => $messageJa]);
    } catch (Throwable $e) {
        // If even this fails, silently ignore (don't break main flow)
        error_log("Activity log insert failed: " . $e->getMessage());
    }
}

// Debug connection
try {
    $pdo->query("SELECT 1");
    error_log("Database connection successful");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Whitelist map for dropdown fields (prevents SQL injection)
$fieldWhitelist = [
    'facility'    => '施設名（勤務先）',
    'shokaimoto'  => '紹介元',
    'nationality' => '雇用者情報（国籍）',
    'jlpt'        => 'JLPT状況',
    'gender'      => '雇用者情報（性別）',
];

// --- options endpoint ---
if (isset($_GET['options']) && $_GET['options'] == '1') {
    $key = $_GET['field'] ?? '';
    if (!isset($fieldWhitelist[$key])) {
        echo json_encode(['error' => 'Invalid field']);
        exit;
    }
    $col = $fieldWhitelist[$key];
    try {
        $sql = "SELECT DISTINCT `$col` AS val FROM talents WHERE `$col` IS NOT NULL AND `$col` <> '' ORDER BY `$col`";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        echo json_encode($rows ?: []);
    } catch (PDOException $e) {
        error_log("Options query error: " . $e->getMessage());
        echo json_encode(['error' => 'Options query failed: ' . $e->getMessage()]);
    }
    exit;
}

// Actor info (for logs)
$staff_id = (int)($_SESSION['id'] ?? 0);
$username = (string)($_SESSION['username'] ?? 'unknown');

$results = [];

// ------------------------
// GET search
// ------------------------
if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    error_log("Received query: $query");

    if (!empty($query)) {
        $keywords = array_filter(preg_split('/\s+/', $query));
        $conditions = [];
        $params = [];
        $i = 0;

        foreach ($keywords as $keyword) {
            $param = ":keyword$i";
            $conditions[] = "LOWER(CONCAT_WS('', 採用日時, `施設名（勤務先）`, 管理番号, `担当者（企業）`, 基本契約書, 委託契約書, 紹介元, `受入機関（郵便番号）`, `受入機関（住所）`, 請求書送付先, `受入機関（電話番号）`, 担当責任者, 区分, `受入機関名（所属機関）`, `雇用者情報（アルファベット）`, `雇用者情報（カタカナ）`, `雇用者情報（性別）`, `雇用者情報（国籍）`, `雇用者情報（生年月日）`, 年齢, `雇用者在留番号`, `雇用者在留期限`, 更新回数, X, 入社日, `在留カード最初発行日`, 支援退職日, 状態, 管理費, 紹介料, `住居タイプ`, 不動産会社, 不動産連絡先, 支援者住所, `連絡先①`, AJ, AK, AL, AM, `支援者の家賃`, 共益費, AP, 満了時期, 備考欄, 正担当者, `JLPT状況`, エリア, `受け入れ機関`, 紹介手数料, 四半期, `介護福祉士合格し卒業の方`)) LIKE $param";
            $params[$param] = "%" . strtolower($keyword) . "%";
            $i++;
        }
        $sql = "SELECT DISTINCT * FROM talents WHERE " . implode(' AND ', $conditions);
        error_log("Generated SQL: $sql, Params: " . json_encode($params));
    } else {
        $sql = "SELECT DISTINCT * FROM talents";
        error_log("No query provided, fetching all records: $sql");
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params ?? []);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Query executed, Results count: " . count($results));
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage() . ", SQL: $sql");
        $results = ['error' => 'Database query failed: ' . $e->getMessage()];
    }

    echo json_encode($results);
    exit;
}

// ------------------------
// POST add/update (called by addstaff/editstaff)
// ------------------------
if (isset($_POST['add']) || isset($_POST['update'])) {

    $name = $_POST['name'] ?? ''; // for add = alphabet name, for update = original_name
    $values = json_decode($_POST['values'] ?? '[]', true);

    $columns = [
        '採用日時', '施設名（勤務先）', '管理番号', '担当者（企業）', '基本契約書', '委託契約書', '紹介元',
        '受入機関（郵便番号）', '受入機関（住所）', '請求書送付先', '受入機関（電話番号）', '担当責任者',
        '区分', '受入機関名（所属機関）', '雇用者情報（アルファベット）', '雇用者情報（カタカナ）',
        '雇用者情報（性別）', '雇用者情報（国籍）', '雇用者情報（生年月日）', '年齢', '雇用者在留番号',
        '雇用者在留期限', '更新回数', 'X', '入社日', '在留カード最初発行日', '支援退職日', '状態',
        '管理費', '紹介料', '住居タイプ', '不動産会社', '不動産連絡先', '支援者住所', '連絡先①',
        'AJ', 'AK', 'AL', 'AM', '支援者の家賃', '共益費', 'AP', '満了時期', '備考欄', '正担当者',
        'JLPT状況', 'エリア', '受け入れ機関', '紹介手数料', '四半期', '介護福祉士合格し卒業の方'
    ];

    if (!is_array($values) || count($values) !== count($columns)) {
        echo json_encode(['error' => 'Invalid values (columns mismatch)']);
        exit;
    }

    // Build associative array newData[col] = value
    $newData = [];
    foreach ($columns as $idx => $col) {
        $newData[$col] = $values[$idx] ?? '';
    }

    // Worker display name for logs
    $workerNameAlpha = trim((string)($newData['雇用者情報（アルファベット）'] ?? ''));
    if ($workerNameAlpha === '') $workerNameAlpha = trim((string)$name);

    // ADD
    if (isset($_POST['add']) && $workerNameAlpha !== '') {
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO talents (" . implode(',', $columns) . ") VALUES ($placeholders)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);

            // ✅ activity log
            $msg = "【人材DB】{$username} さんが「{$workerNameAlpha}」の雇用者情報を新規登録しました。";
            insertActivityLog($pdo, $msg, $staff_id, $username);

            echo json_encode(['status' => 'success', 'message' => 'Added successfully']);
            exit;
        } catch (PDOException $e) {
            error_log("Add error: " . $e->getMessage() . ", Values: " . json_encode($values) . ", SQL: $sql");
            echo json_encode(['error' => 'Add failed: ' . $e->getMessage()]);
            exit;
        }
    }

    // UPDATE
    if (isset($_POST['update']) && !empty($name)) {

        // fetch old row first for diff
        $oldRow = [];
        try {
            $st = $pdo->prepare("SELECT * FROM talents WHERE `雇用者情報（アルファベット）` = ? LIMIT 1");
            $st->execute([$name]);
            $oldRow = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $oldRow = [];
        }

        $setClause = implode(' = ?, ', $columns) . ' = ?';
        $sql = "UPDATE talents SET $setClause WHERE `雇用者情報（アルファベット）` = ?";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($values, [$name]));

            // ✅ activity log (diff)
            // compare oldRow vs newData
            $changes = [];
            foreach ($columns as $col) {
                $before = isset($oldRow[$col]) ? trim((string)$oldRow[$col]) : '';
                $after  = isset($newData[$col]) ? trim((string)$newData[$col]) : '';

                // normalize null-like
                if ($before === 'NULL') $before = '';
                if ($after === 'NULL') $after = '';

                if ($before !== $after) {
                    $changes[] = $col;
                }
            }

            // If name changed (alphabet), show log as well
            $newAlpha = trim((string)($newData['雇用者情報（アルファベット）'] ?? ''));
            $displayName = $newAlpha !== '' ? $newAlpha : $name;

            if (!empty($changes)) {
                // keep log short
                $max = 6;
                $list = array_slice($changes, 0, $max);
                $more = count($changes) > $max ? ' ほか' . (count($changes) - $max) . '件' : '';

                $msg = "【人材DB】{$username} さんが「{$displayName}」の情報を更新しました。変更: " . implode('、', $list) . $more . "。";
            } else {
                $msg = "【人材DB】{$username} さんが「{$displayName}」の情報を更新しました。";
            }

            insertActivityLog($pdo, $msg, $staff_id, $username);

            echo json_encode(['status' => 'success', 'message' => 'Updated successfully']);
            exit;
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage() . ", Values: " . json_encode($values) . ", Name: $name, SQL: $sql");
            echo json_encode(['error' => 'Update failed: ' . $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['error' => 'Invalid request']);
    exit;
}

echo json_encode(['error' => 'Invalid request']);
