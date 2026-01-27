<?php
// delete_post.php

ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

date_default_timezone_set('Asia/Tokyo');

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/activity_logger.php';

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($post_id === false || $post_id <= 0) {
    header("Location: manage_posts.php");
    exit;
}

try {
    // fetch post info
    $stmt = $pdo->prepare("SELECT post_type, title, image FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        header("Location: error.php?message=" . urlencode("Post not found or you do not have permission to delete this post."));
        exit;
    }

    $postType = (string)($post['post_type'] ?? '');
    $title    = (string)($post['title'] ?? '');
    $image    = (string)($post['image'] ?? '');

    // delete image if exists (supports "uploads/..." OR absolute)
    if ($image !== '') {
        $docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '/home/it-future/www/itf', '/');

        $abs = $image;
        if (!str_starts_with($image, '/') && !preg_match('/^[A-Za-z]:\\\\/u', $image)) {
            // relative path like "uploads/news/xx.jpg"
            $abs = $docroot . '/' . ltrim($image, '/');
        }

        if (is_file($abs)) {
            @unlink($abs);
        }
    }

    // delete row
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND staff_id = ?");
    $stmt->execute([$post_id, $_SESSION['id']]);

    // ✅ log activity (after delete ok)
    $actor = (string)($_SESSION['username'] ?? '');
    $actorId = (int)($_SESSION['id'] ?? 0);

    if ($postType === 'news') {
        $msg = sprintf('%s が お知らせ「%s」を削除しました。', $actor, $title);
        log_activity($pdo, [
            'actor_type'     => 'staff',
            'actor_staff_id' => $actorId,
            'actor_username' => $actor,
            'action'         => 'news_delete',
            'entity_type'    => 'news',
            'entity_id'      => $post_id,
            'message_ja'     => $msg,
        ]);
    } else {
        $msg = sprintf('%s が 投稿（%s）「%s」を削除しました。', $actor, $postType, $title);
        log_activity($pdo, [
            'actor_type'     => 'staff',
            'actor_staff_id' => $actorId,
            'actor_username' => $actor,
            'action'         => 'post_delete',
            'entity_type'    => $postType,
            'entity_id'      => $post_id,
            'message_ja'     => $msg,
        ]);
    }

    header("Location: manage_posts.php");
    exit;

} catch (PDOException $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
