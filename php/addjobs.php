<?php
// /home/it-future/www/itf/php/addjobs.php
ini_set('session.cookie_path', '/');
ini_set('session.cookie_domain', '.it-future.jp');
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['username'])) {
  header("Location: /php/login.php");
  exit;
}
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>✙求人情報を追加</title>
  <link rel="stylesheet" href="/css/staffdb.css">
  <style>
    body{font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;margin:20px}
    .wrap{max-width:960px;margin:0 auto}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px}
    h1{font-size:20px;margin:0 0 12px}
    form label{display:block;margin:10px 0 6px;font-weight:600}
    input[type=text],input[type=number],select,textarea{
      width:100%;padding:10px;border:1px solid #d1d5db;border-radius:8px;font-size:14px
    }
    textarea{min-height:140px}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .grid-1{display:grid;grid-template-columns:1fr;gap:12px}
    .btns{display:flex;gap:10px;margin-top:14px}
    .btn{padding:10px 14px;border:1px solid #dbe7f5;border-radius:8px;background:#f3f9ff;color:#0c4a7a;text-decoration:none}
    .btn.primary{background:#1e90ff;border-color:#1e90ff;color:#fff}
    small.hint{color:#64748b}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <h1>✙ 求人情報を追加</h1>
      <form id="jobsFormSubmit" action="submit_post.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
        <input type="hidden" name="form_type" value="jobs">
        <input type="hidden" name="date" value="<?=date('Y-m-d')?>">
        <input type="hidden" name="posted_by" value="<?=htmlspecialchars($_SESSION['username'])?>">
        <input type="hidden" name="staff_id" value="<?=htmlspecialchars($_SESSION['id'])?>">

        <label>タイトル *</label>
        <input type="text" name="title" required>

        <label>概要（ハッシュタグを入力：例「#介護 #大阪 #特定技能」） *</label>
        <textarea name="summary" placeholder="#介護 #大阪 #特定技能" required></textarea>

        <label>内容 *</label>
        <textarea name="content" required></textarea>

        <div class="grid">
          <div>
            <label>組織名 / 会社名（社内管理用・公開しません） *</label>
            <input type="text" name="company_name" placeholder="株式会社〇〇 など" required>
            <small class="hint">求人詳細ページでは表示しません（社内のみ）。</small>
          </div>
          <div>
            <label>勤務地（都道府県） *</label>
            <select name="job_location" required>
              <?php
              $prefs = ["北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"];
              foreach($prefs as $p){ echo '<option value="'.htmlspecialchars($p).'">'.$p.'</option>'; }
              ?>
            </select>
          </div>
          <div>
            <label>職種カテゴリ *</label>
            <select name="job_category" required>
              <option value="介護">介護</option>
              <option value="レストラン">レストラン</option>
              <option value="事務">事務</option>
              <option value="工場作業員">工場作業員</option>
            </select>
          </div>
          <div>
            <label>雇用形態 *</label>
            <select name="job_type" required>
              <option value="正社員">正社員</option>
              <option value="パートタイム">パートタイム</option>
              <option value="契約社員">契約社員</option>
            </select>
          </div>
          <div>
            <label>給与（自由入力・例：370万～420万） *</label>
            <input type="text" name="salary" placeholder="例：年収500万円～700万円 / 370万～420万" required>
          </div>
          <div>
            <label>必要日本語レベル *</label>
            <select name="japanese_level" required>
              <option value="N1">N1</option><option value="N2">N2</option>
              <option value="N3">N3</option><option value="N4">N4</option><option value="N5">N5</option>
            </select>
          </div>
        </div>

        <div class="grid">
          <div>
            <label>経験 *</label>
            <input type="text" name="experience" placeholder="例：介護経験1年以上" required>
          </div>
          <div>
            <label>年間最低休暇日数 *</label>
            <input type="number" name="minimum_leave_per_year" required>
          </div>
          <!-- employee_size REMOVED from form -->
          <div>
            <label>募集人数 *</label>
            <input type="number" name="required_vacancy" required>
          </div>
        </div>

        <!-- 福利など -->
        <div class="grid">
          <div>
            <label>賞与</label>
            <select name="bonuses">
              <option value="0">なし</option>
              <option value="1">あり</option>
            </select>
          </div>
          <div>
            <label>賞与額（任意）</label>
            <input type="text" name="bonus_amount">
          </div>
          <div>
            <label>住宅手当</label>
            <select name="living_support">
              <option value="0">なし</option>
              <option value="1">あり</option>
            </select>
          </div>
          <div>
            <label>住宅手当額（任意）</label>
            <!-- DB column name is rent_support -->
            <input type="text" name="rent_support" placeholder="例：月1万円 など">
          </div>
          <div>
            <label>保険</label>
            <select name="insurance">
              <option value="0">なし</option>
              <option value="1">あり</option>
            </select>
          </div>
          <div>
            <label>交通費</label>
            <select name="transportation_charges">
              <option value="0">なし</option>
              <option value="1">あり</option>
            </select>
          </div>
          <div>
            <label>月額上限（交通費・任意）</label>
            <!-- DB column name is transport_amount_limit -->
            <input type="text" name="transport_amount_limit" placeholder="例：上限2万円">
          </div>
          <div>
            <label>昇給</label>
            <select name="salary_increment">
              <option value="0">なし</option>
              <option value="1">あり</option>
            </select>
          </div>
          <div style="grid-column:1/-1">
            <label>昇給条件（任意）</label>
            <textarea name="increment_condition" placeholder="評価・勤続年数により年1回 など"></textarea>
          </div>
        </div>

        <!-- New Preference / Memo block -->
        <div class="grid-1">
          <div>
            <label>優先したい国籍（任意・ハッシュタグ推奨）</label>
            <textarea name="preferred_nationalities" placeholder="#ネパール #ベトナム #インドネシア など"></textarea>
          </div>
          <div>
            <label>応募者の現在地（任意）</label>
            <select name="preferred_candidate_status">
              <option value="">指定なし</option>
              <option value="日本在住">日本在住</option>
              <option value="海外在住">海外在住</option>
              <option value="どちらでも">どちらでも</option>
            </select>
          </div>
          <div>
            <label>メモ（任意）</label>
            <textarea name="job_memo" placeholder="面接方法、必要書類、勤務開始希望など"></textarea>
          </div>
        </div>

        <div class="btns">
          <a class="btn" href="/php/staffdb.php">← 戻る</a>
          <button type="submit" class="btn primary">投稿</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
