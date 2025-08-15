<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header('Content-Type: application/json');
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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Fetch staff name
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

header('Content-Type: application/json');

$results = [];
if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    error_log("Received query: $query");
    if (!empty($query)) {
        // Split query into keywords and handle multiple spaces
        $keywords = array_filter(preg_split('/\s+/', $query)); // Split by any whitespace
        $conditions = [];
        $params = [];
        $i = 0;
        foreach ($keywords as $keyword) {
            $param = ":keyword$i";
            $conditions[] = "CONCAT_WS('', 採用日時, 施設名（勤務先）, 雇用者情報（アルファベット）, 管理番号, 担当者（企業）, 基本契約書, 委託契約書, 紹介元, 受入機関（郵便番号）, 受入機関（住所）, 請求書送付先, 受入機関（電話番号）, 担当責任者, 区分, 受入機関名（所属機関）, 雇用者情報（カタカナ）, 雇用者情報（性別）, 雇用者情報（国籍）, 雇用者情報（生年月日）, 年齢, 雇用者在留番号, 雇用者在留期限, 更新回数, X, 入社日, 在留カード最初発行日, 支援退職日, 状態, 管理費, 紹介料, 住居タイプ, 不動産会社, 不動産連絡先, 支援者住所, 連絡先①, AJ, AK, AL, AM, 支援者の家賃, 共益費, AP, 満了時期, 備考欄, 正担当者, JLPT, エリア, 受け入れ期間, 紹介手数料, 四半期, AY, AZ, BA, BB, BC, BD, BE, BF, BG, BH) LIKE $param";
            $params[$param] = "%" . strtolower($keyword) . "%"; // Case-insensitive
            $i++;
        }
        $sql = "SELECT DISTINCT * FROM talents WHERE " . implode(' AND ', $conditions);
        error_log("Generated SQL: $sql, Params: " . json_encode($params));
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Query executed, Results count: " . count($results));
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . ", SQL: $sql");
            $results = ['error' => 'Database query failed: ' . $e->getMessage()];
        }
    } else {
        $results = ['error' => 'Query parameter is empty'];
    }
} elseif (isset($_POST['add']) || isset($_POST['update'])) {
    $name = $_POST['name'] ?? '';
    $values = json_decode($_POST['values'] ?? '[]', true);

    if (isset($_POST['add']) && !empty($name) && !empty($values)) {
        $sql = "INSERT INTO talents (採用日時, 施設名（勤務先）, 雇用者情報（アルファベット）, 管理番号, 担当者（企業）, 基本契約書, 委託契約書, 紹介元, 受入機関（郵便番号）, 受入機関（住所）, 請求書送付先, 受入機関（電話番号）, 担当責任者, 区分, 受入機関名（所属機関）, 雇用者情報（カタカナ）, 雇用者情報（性別）, 雇用者情報（国籍）, 雇用者情報（生年月日）, 年齢, 雇用者在留番号, 雇用者在留期限, 更新回数, X, 入社日, 在留カード最初発行日, 支援退職日, 状態, 管理費, 紹介料, 住居タイプ, 不動産会社, 不動産連絡先, 支援者住所, 連絡先①, AJ, AK, AL, AM, 支援者の家賃, 共益費, AP, 満了時期, 備考欄, 正担当者, JLPT, エリア, 受け入れ期間, 紹介手数料, 四半期, AY, AZ, BA, BB, BC, BD, BE, BF, BG, BH) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($values);
            $results = ['status' => 'success', 'message' => 'Added successfully'];
        } catch (PDOException $e) {
            error_log("Add error: " . $e->getMessage() . ", Values: " . json_encode($values));
            $results = ['error' => 'Add failed: ' . $e->getMessage()];
        }
    } elseif (isset($_POST['update']) && !empty($name) && !empty($values)) {
        $sql = "UPDATE talents SET 採用日時 = ?, 施設名（勤務先） = ?, 雇用者情報（アルファベット） = ?, 管理番号 = ?, 担当者（企業） = ?, 基本契約書 = ?, 委託契約書 = ?, 紹介元 = ?, 受入機関（郵便番号） = ?, 受入機関（住所） = ?, 請求書送付先 = ?, 受入機関（電話番号） = ?, 担当責任者 = ?, 区分 = ?, 受入機関名（所属機関） = ?, 雇用者情報（カタカナ） = ?, 雇用者情報（性別） = ?, 雇用者情報（国籍） = ?, 雇用者情報（生年月日） = ?, 年齢 = ?, 雇用者在留番号 = ?, 雇用者在留期限 = ?, 更新回数 = ?, X = ?, 入社日 = ?, 在留カード最初発行日 = ?, 支援退職日 = ?, 状態 = ?, 管理費 = ?, 紹介料 = ?, 住居タイプ = ?, 不動産会社 = ?, 不動産連絡先 = ?, 支援者住所 = ?, 連絡先① = ?, AJ = ?, AK = ?, AL = ?, AM = ?, 支援者の家賃 = ?, 共益費 = ?, AP = ?, 満了時期 = ?, 備考欄 = ?, 正担当者 = ?, JLPT = ?, エリア = ?, 受け入れ期間 = ?, 紹介手数料 = ?, 四半期 = ?, AY = ?, AZ = ?, BA = ?, BB = ?, BC = ?, BD = ?, BE = ?, BF = ?, BG = ?, BH = ? WHERE 雇用者情報（アルファベット） = ?";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge($values, [$name]));
            $results = ['status' => 'success', 'message' => 'Updated successfully'];
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage() . ", Values: " . json_encode($values) . ", Name: $name");
            $results = ['error' => 'Update failed: ' . $e->getMessage()];
        }
    } else {
        $results = ['error' => 'Invalid request'];
    }
}

echo json_encode($results);
?>