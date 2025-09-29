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

// Include database connection
require_once 'db_connect.php';

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

// --- NEW: options endpoint for distinct dropdown values ---
// Usage: GET /php/search.php?options=1&field=facility  (or shokaimoto|nationality|jlpt|gender)
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

// Fetch staff name (kept for logging or future use)
$staff_id = $_SESSION['id'];
try {
    $stmt = $pdo->prepare("SELECT name FROM staff WHERE id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    $staff_name = $staff ? htmlspecialchars($staff['name']) : htmlspecialchars($_SESSION['username']);
} catch (PDOException $e) {
    $staff_name = htmlspecialchars($_SESSION['username']);
    error_log("Database error (staff name): " . $e->getMessage());
}

$results = [];
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
            // NOTE: The CONCAT_WS list mirrors your columns. If your schema differs (e.g., 受け入れ期間 vs 受け入れ機関),
            // adjust the column name below accordingly.
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
} elseif (isset($_POST['add']) || isset($_POST['update'])) {
    $name = $_POST['name'] ?? '';
    $values = json_decode($_POST['values'] ?? '[]', true);

    if (isset($_POST['add']) && !empty($name) && !empty($values)) {
        $columns = ['採用日時', '施設名（勤務先）', '管理番号', '担当者（企業）', '基本契約書', '委託契約書', '紹介元', '受入機関（郵便番号）', '受入機関（住所）', '請求書送付先', '受入機関（電話番号）', '担当責任者', '区分', '受入機関名（所属機関）', '雇用者情報（アルファベット）', '雇用者情報（カタカナ）', '雇用者情報（性別）', '雇用者情報（国籍）', '雇用者情報（生年月日）', '年齢', '雇用者在留番号', '雇用者在留期限', '更新回数', 'X', '入社日', '在留カード最初発行日', '支援退職日', '状態', '管理費', '紹介料', '住居タイプ', '不動産会社', '不動産連絡先', '支援者住所', '連絡先①', 'AJ', 'AK', 'AL', 'AM', '支援者の家賃', '共益費', 'AP', '満了時期', '備考欄', '正担当者', 'JLPT状況', 'エリア', '受け入れ機関', '紹介手数料', '四半期', '介護福祉士合格し卒業の方'];
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO talents (" . implode(',', $columns) . ") VALUES ($placeholders)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $results = ['status' => 'success', 'message' => 'Added successfully'];
        } catch (PDOException $e) {
            error_log("Add error: " . $e->getMessage() . ", Values: " . json_encode($values) . ", SQL: $sql");
            $results = ['error' => 'Add failed: ' . $e->getMessage()];
        }
    } elseif (isset($_POST['update']) && !empty($name) && !empty($values)) {
        $columns = ['採用日時', '施設名（勤務先）', '管理番号', '担当者（企業）', '基本契約書', '委託契約書', '紹介元', '受入機関（郵便番号）', '受入機関（住所）', '請求書送付先', '受入機関（電話番号）', '担当責任者', '区分', '受入機関名（所属機関）', '雇用者情報（アルファベット）', '雇用者情報（カタカナ）', '雇用者情報（性別）', '雇用者情報（国籍）', '雇用者情報（生年月日）', '年齢', '雇用者在留番号', '雇用者在留期限', '更新回数', 'X', '入社日', '在留カード最初発行日', '支援退職日', '状態', '管理費', '紹介料', '住居タイプ', '不動産会社', '不動産連絡先', '支援者住所', '連絡先①', 'AJ', 'AK', 'AL', 'AM', '支援者の家賃', '共益費', 'AP', '満了時期', '備考欄', '正担当者', 'JLPT状況', 'エリア', '受け入れ機関', '紹介手数料', '四半期', '介護福祉士合格し卒業の方'];
        $setClause = implode(' = ?, ', $columns) . ' = ?';
        $sql = "UPDATE talents SET $setClause WHERE `雇用者情報（アルファベット）` = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($values, [$name]));
            $results = ['status' => 'success', 'message' => 'Updated successfully'];
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage() . ", Values: " . json_encode($values) . ", Name: $name, SQL: $sql");
            $results = ['error' => 'Update failed: ' . $e->getMessage()];
        }
    } else {
        $results = ['error' => 'Invalid request'];
    }
}

echo json_encode($results);
