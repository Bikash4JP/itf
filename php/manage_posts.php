<?php
// /home/it-future/www/itf/php/manage_posts.php
ini_set('session.cookie_path', '/itf');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . '/db_connect.php';

// Fetch posts (jobs first, newest first)
$stmt = $pdo->query("
  SELECT *
  FROM posts
  ORDER BY (post_type='job') DESC, date DESC, id DESC
");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Simple search by title or summary
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
  $needle = mb_strtolower($q);
  $posts = array_values(array_filter($posts, function($p) use ($needle){
    $hay = mb_strtolower(($p['title'] ?? '') . ' ' . ($p['summary'] ?? '') . ' ' . ($p['company_name'] ?? ''));
    return strpos($hay, $needle) !== false;
  }));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>投稿管理 - スタッフダッシュボード</title>
  <link rel="stylesheet" href="https://it-future.jp/css/staffdb.css">
  <link rel="stylesheet" href="https://it-future.jp/css/search.css">
  <style>
    :root{
      --ink:#0b2243; --muted:#667085; --border:#e6edf6; --bg:#f7fbff; --card:#ffffff;
      --primary:#1e90ff; --primary-d:#1677d3; --ok:#16a34a; --bad:#dc2626; --amber:#f59e0b;
    }
    .hero{background:var(--bg);border-bottom:1px solid var(--border)}
    .hero .wrap{max-width:1200px;margin:0 auto;padding:20px 16px}
    .hero h1{margin:0;color:var(--ink)}

    .wrap{max-width:1200px;margin:0 auto;padding:18px 16px}

    /* toolbar */
    .toolbar{display:flex;gap:12px;align-items:center;margin:6px 0 16px 0;flex-wrap:wrap}
    .search{flex:1;min-width:260px;display:flex;align-items:center;border:2px solid #1d96db;border-radius:999px;height:50px;overflow:hidden}
    .search input{border:none;outline:none;width:100%;padding:0 14px;font-size:16px}
    .filter{display:flex;gap:8px}
    .chip{padding:8px 10px;border:1px solid var(--border);border-radius:999px;background:#fff;color:#0b3772;cursor:pointer}
    .chip.active{background:#eafff3;border-color:#bbf7d0;color:#065f46}

    /* grid */
    .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
    @media (max-width:1000px){ .cards{grid-template-columns:repeat(2,1fr)} }
    @media (max-width:680px){ .cards{grid-template-columns:1fr} }

    .card{
      background:var(--card);border:1px solid var(--border);border-radius:14px;padding:16px;
      box-shadow:0 3px 12px rgba(10,60,150,.06);display:flex;flex-direction:column;gap:8px
    }
    .card .type{font-size:12px;color:#1e40af;background:#eff6ff;border:1px solid #dbeafe;border-radius:999px;padding:2px 8px;display:inline-block}
    .card h3{margin:4px 0 0 0;font-size:18px;color:var(--ink);line-height:1.35}
    .meta{font-size:12.5px;color:var(--muted);display:flex;gap:10px;flex-wrap:wrap}
    .sum{color:#374151;font-size:14px;line-height:1.5;overflow-wrap:anywhere}

    .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:6px}
    .btn{display:inline-block;padding:8px 12px;border-radius:10px;border:1px solid #dbe7f5;background:#f3f9ff;color:#0b3772;text-decoration:none}
    .btn:hover{background:#e9f5ff}
    .btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}
    .btn.primary:hover{background:var(--primary-d)}
    .btn.ok{background:#e8fff2;border-color:#bbf7d0;color:#166534}
    .btn.bad{background:#fee2e2;border-color:#fecaca;color:#991b1b}

    .empty{padding:32px;border:1px dashed var(--border);border-radius:12px;background:#fff;color:var(--muted)}
  </style>
</head>
<body>
  <header>
        <div class="logo"><a href="https://it-future.jp/"><img src="https://it-future.jp/images/logo.png" alt="ITF Logo"></a></div>
        <nav>
            <ul>
                <li><a href="staffdb.php">ホーム</a></li>
                <li><a href="profile.php">プロフィール</a></li>
                <li><a href="logout.php">ログアウト</a></li>
            </ul>
        </nav>
    </header>

  <section class="hero">
    <div class="wrap">
      <h1>投稿管理</h1>
    </div>
  </section>

  <div class="wrap">
    <form class="toolbar" method="get" action="manage_posts.php">
      <div class="search">
        <input type="text" name="q" value="<?=htmlspecialchars($q,ENT_QUOTES,'UTF-8')?>" placeholder="タイトル / 概要 / 会社名 を検索">
      </div>
    </form>

    <?php if (count($posts) === 0): ?>
      <div class="empty">投稿が見つかりませんでした。</div>
    <?php else: ?>
      <div class="cards">
        <?php foreach ($posts as $p): ?>
          <div class="card">
            <span class="type"><?= $p['post_type']==='job' ? '求人' : 'ニュース' ?></span>
            <h3><?= htmlspecialchars($p['title'] ?? '') ?></h3>
            <div class="meta">
              <span>投稿日: <?= htmlspecialchars($p['date'] ?? '') ?></span>
              <span>投稿者: <?= htmlspecialchars($p['posted_by'] ?? '') ?></span>
              <?php if ($p['post_type']==='job'): ?>
                <span>勤務地: <?= htmlspecialchars($p['job_location'] ?? '-') ?></span>
                <span>カテゴリ: <?= htmlspecialchars($p['job_category'] ?? '-') ?></span>
              <?php endif; ?>
            </div>
            <?php if (!empty($p['summary'])): ?>
              <div class="sum"><?= nl2br(htmlspecialchars($p['summary'])) ?></div>
            <?php endif; ?>

            <div class="actions">
              <?php if ($p['post_type']==='job'): ?>
                <a class="btn primary" href="edit_job.php?id=<?= (int)$p['id'] ?>">求人を編集</a>
                <a class="btn" href="job_details.php?job_id=<?= (int)$p['id'] ?>" target="_blank">公開ページを見る</a>
              <?php else: ?>
                <a class="btn primary" href="edit_post.php?id=<?= (int)$p['id'] ?>">編集</a>
                <a class="btn" href="../news.html" target="_blank">公開ページを見る</a>
              <?php endif; ?>
              <a class="btn bad" href="delete_post.php?id=<?= (int)$p['id'] ?>" onclick="return confirm('この投稿を削除しますか？')">削除</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
