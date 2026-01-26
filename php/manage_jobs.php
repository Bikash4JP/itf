<?php
// /home/it-future/www/itf/php/manage_jobs.php
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

// ✅ Only these admins can access manage_jobs.php
$JOB_ADMIN_USERS = ['osaka_ueda', 'bikash', 'kimura'];
if (!in_array($_SESSION['username'], $JOB_ADMIN_USERS, true)) {
  header("Location: /php/staffdb.php");
  exit;
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/db_connect.php';

$staffList = [];
try {
  $st = $pdo->query("SELECT id, name, username FROM staff ORDER BY name ASC");
  $staffList = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $staffList = [];
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>求人管理（Airtable風）</title>

  <link rel="stylesheet" href="https://it-future.jp/css/staffdb.css">
  <link href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator.min.css" rel="stylesheet">
  <script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>

  <style>
    :root{--border:#e6edf6; --ink:#0b2243; --muted:#667085; --primary:#1e90ff; --primary-d:#1677d3; --success:#10b981;}
    body{margin:0;font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;background:#fff}
    .wrap{max-width:1900px;margin:0 auto;padding:14px 16px}
    .toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:14px 0}
    .btn{padding:10px 12px;border:1px solid #dbe7f5;border-radius:10px;background:#f3f9ff;color:#0b3772;cursor:pointer;text-decoration:none;font-family:inherit;font-size:14px;font-weight:600;transition:.15s}
    .btn:hover{filter:brightness(.97)}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}
    .btn.primary:hover:not(:disabled){background:var(--primary-d)}
    .btn.success{background:var(--success);border-color:var(--success);color:#fff}
    .muted{color:var(--muted);font-size:12px}

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:900;line-height:1;color:#fff}
    .pill.open{background:#16a34a}
    .pill.close{background:#2f1f1f}
    .pill.urgent{background:#dc2626}

    .chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:900;border:1px solid #dbe7f5;background:#f8fafc;color:#0b2243}
    .chip small{opacity:.75;font-weight:800}

    .btnDel{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:6px 10px;border-radius:10px;cursor:pointer;font-weight:900}
    .btnDel:hover{filter:brightness(.98)}

    .drawerBack{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:9998}
    .drawerBack.open{display:block}
    .drawer{position:fixed;top:0;right:0;height:100%;width:min(560px, 96vw);background:#fff;border-left:1px solid var(--border);transform:translateX(100%);transition:.18s;overflow:auto;z-index:9999}
    .drawer.open{transform:translateX(0)}

    .tabulator-row.edited{background:#fffbeb !important}
    .tabulator-row.edited:hover{background:#fef3c7 !important}

    .saveNotif{position:fixed;top:20px;right:20px;padding:12px 20px;background:var(--success);color:#fff;border-radius:10px;font-weight:900;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:10000;opacity:0;transition:.2s;pointer-events:none}
    .saveNotif.show{opacity:1}

    .tabulator .tabulator-header .tabulator-col .tabulator-col-title{text-align:center;font-size:18px;font-weight:900}
    .th-wrap{display:inline-flex;align-items:center;justify-content:center;gap:6px;width:100%}
    .th-public{color:#dc2626;font-weight:900}
    .th-public .th-star{color:#dc2626;font-weight:900}
    .th-unused{color:#2563eb;font-weight:900}
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

<div class="wrap">
  <div class="toolbar">
    <button class="btn primary" id="btnAdd">＋ 新規行（下書き）</button>
    <button class="btn success" id="btnSaveAll">💾 更新（全て保存）</button>
    <button class="btn" id="btnRefresh">↻ 再読込</button>

    <label class="muted">Group:</label>
    <select id="groupBy" class="btn" style="padding:8px 10px">
      <option value="">なし</option>
      <option value="status">状況</option>
      <option value="job_location">住所</option>
      <option value="org_work_type">職種</option>
      <option value="job_staff_id">求人担当</option>
    </select>

    <a class="btn" href="/saiyou.php" target="_blank">公開（求人一覧）を見る</a>
    <span class="muted" id="editHint">セルをクリックして編集 → <strong>更新</strong>ボタンで保存</span>
  </div>

  <div id="jobsGrid"></div>
</div>

<div class="saveNotif" id="saveNotif">✓ 保存しました</div>

<div class="drawerBack" id="drawerBack"></div>
<div class="drawer" id="drawer">
  <div style="padding:14px;border-bottom:1px solid var(--border);display:flex;gap:10px;align-items:center">
    <button class="btn" id="drawerClose">✕</button>
    <div style="display:flex;flex-direction:column">
      <strong id="dTitle" style="color:var(--ink)">求人</strong>
      <span class="muted" id="dSub"></span>
    </div>
  </div>
  <div style="padding:14px">
    <div class="muted">求人票（画像/PDF） ※ ここからアップロード</div>
    <div style="height:1px;background:var(--border);margin:12px 0"></div>

    <form id="uploadForm">
      <input type="file" name="file" id="fileInput" accept="image/*,application/pdf">
      <button class="btn primary" type="submit">アップロード</button>
      <div class="muted">※ JPG/PNG/WEBP/PDF（最大10MB）</div>
    </form>

    <div style="height:1px;background:var(--border);margin:12px 0"></div>
    <div id="filesBox" style="display:flex;flex-wrap:wrap;gap:10px"></div>
  </div>
</div>

<script>
const CSRF = <?= json_encode($_SESSION['csrf_token']) ?>;
const STAFF_LIST = <?= json_encode($staffList, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;

const STAFF_NAME_BY_ID = {};
const STAFF_VALUE_LIST = (function(){
  const arr = [{label:"", value:""}];
  for(const s of STAFF_LIST){
    const id = String(s.id);
    STAFF_NAME_BY_ID[id] = s.name;
    arr.push({label: s.name, value: id});
  }
  return arr;
})();

const statusOpts = ["募集中","急募","募集終"];
const workTypeOpts = ["介護","外食","工業製品","食品製造","その他"];

const prefTop = ["東京都","大阪府","神奈川県","埼玉県","千葉県","愛知県","福岡県","兵庫県","京都府","北海道"];
const prefAll = ["北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"];
const prefectureOpts = [...prefTop, ...prefAll.filter(p=>!prefTop.includes(p))];

const residenceOpts = [{label:"国内", value:"国内"},{label:"国外", value:"国外"},{label:"どちらでもOK", value:"どちらでもOK"}];
const genderOpts = [{label:"男性", value:"男"},{label:"女性", value:"女"},{label:"どちらでもOK", value:"どちらでもOK"}];
const expOpts = [{label:"あり", value:"あり"},{label:"なし", value:"なし"},{label:"どちらでもOK", value:"どちらでもOK"}];
const hijabOpts = [{label:"OK", value:"OK"},{label:"禁止", value:"禁止"}];

const nationalityOpts = ["国籍問わず","日本人","インドネシア人","ネパール人","インド人","ベトナム人","中国人","バングラデシュ人","韓国人","ミャンマー人","その他"];
const smokingOpts = ["禁煙","喫煙可","分煙","不明"];

// tinyint sync options (0/1)
const yesNoOpts = [
  {label:"あり", value:1},
  {label:"なし", value:0},
];

// ✅ 完備/なし
const insuranceCoverOpts = [
  {label:"完備", value:1},
  {label:"なし", value:0},
];

let currentJobId = null;
const editedRows = new Set();
const originalData = new Map();

const TRACK_FIELDS = [
  'company_name','status','request_date','deadline_date',
  'job_staff_id','level','title','summary','content',
  'org_work_type','job_location','work_location_detail',

  'contract_period','probation_period','job_change_scope','workplace_change_scope',
  'work_hours_shift','break_time','overtime','holidays','paid_leave','annual_holidays',

  'current_residence','japanese_level','required_age','gender_pref','experience','skills_certifications',
  'hijab_policy','preferred_nationalities','required_vacancy',

  'salary','salary_basic','tax_pension_insurance','salary_takehome',
  'bonuses','bonus_amount',
  'transportation_charges','transport_amount_limit',
  'rent_support','life_support','visa_support',
  'social_insurance',
  'salary_increment','increment_condition',
  'smoking',
];

function escapeHtml(s){ return String(s ?? "").replace(/[&<>"']/g, (m)=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;" }[m])); }

function thPublic(label){
  return `<span class="th-wrap th-public"><strong class="th-star">*</strong><strong>${escapeHtml(label)}</strong></span>`;
}
function thUnused(label){
  return `<span class="th-wrap th-unused"><strong>${escapeHtml(label)}</strong></span>`;
}

function hashHue(str){ let h=0; str=String(str||""); for(let i=0;i<str.length;i++) h = (h*31 + str.charCodeAt(i)) >>> 0; return h % 360; }
function chip(val){
  const s = (val||"").trim();
  if(!s) return "";
  const hue = hashHue("chip:"+s);
  const bg = `hsl(${hue} 80% 93%)`;
  const bd = `hsl(${hue} 65% 72%)`;
  const fg = `hsl(${hue} 45% 25%)`;
  return `<span class="chip" style="background:${bg};border-color:${bd};color:${fg}">${escapeHtml(s)}</span>`;
}
function staffChipById(id){
  const sid = String(id||"").trim();
  if(!sid) return "";
  const name = STAFF_NAME_BY_ID[sid] || sid;
  const hue = hashHue("staff:"+name);
  const bg = `hsl(${hue} 75% 92%)`;
  const bd = `hsl(${hue} 60% 70%)`;
  const fg = `hsl(${hue} 45% 25%)`;
  return `<span class="chip" style="background:${bg};border-color:${bd};color:${fg}">${escapeHtml(name)}</span>`;
}
function statusPill(val){
  if(val === "募集終") return `<span class="pill close">募集終</span>`;
  if(val === "急募") return `<span class="pill urgent">急募</span>`;
  return `<span class="pill open">募集中</span>`;
}
function yesNoChip(val){
  const v = (val === true) ? 1 : (val === false ? 0 : val);
  if(String(v) === "1") return `<span class="chip"><small>あり</small></span>`;
  if(String(v) === "0") return `<span class="chip"><small>なし</small></span>`;
  return `<span class="chip"><small>-</small></span>`;
}
function coverChip(val){
  const v = (val === true) ? 1 : (val === false ? 0 : val);
  if(String(v) === "1") return `<span class="chip"><small>完備</small></span>`;
  if(String(v) === "0") return `<span class="chip"><small>なし</small></span>`;
  return `<span class="chip"><small>-</small></span>`;
}

function filesPreviewFormatter(cell){
  const arr = cell.getValue() || [];
  if(!Array.isArray(arr) || !arr.length) return `<span class="chip"><small>アップロード</small></span>`;
  const html = arr.slice(0,2).map(f=>{
    if((f.mime||"").startsWith("image/")){
      return `<span style="width:46px;height:34px;border-radius:8px;margin-right:6px;display:inline-block;overflow:hidden;vertical-align:middle;border:1px solid var(--border);background:#fafafa">
        <img src="${f.file_path}" alt="" style="width:100%;height:100%;object-fit:cover">
      </span>`;
    }
    return `<span style="width:46px;height:34px;border-radius:8px;margin-right:6px;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;font-size:10px;border:1px solid var(--border);background:#fafafa;font-weight:900">PDF</span>`;
  }).join("");
  const more = arr.length > 2 ? `<span class="chip"><small>+${arr.length-2}</small></span>` : "";
  return html + more;
}

async function api(action, data=null){
  const url = "jobs_api.php?action=" + encodeURIComponent(action);
  if(!data){
    const r = await fetch(url, {credentials:"same-origin"});
    return r.json();
  }
  const form = new FormData();
  for(const k in data){
    const v = data[k];
    if(Array.isArray(v)) form.append(k, JSON.stringify(v));
    else if(typeof v === 'object' && v !== null) form.append(k, JSON.stringify(v));
    else form.append(k, v);
  }
  form.append("csrf_token", CSRF);
  const r = await fetch(url, {method:"POST", body:form, credentials:"same-origin"});
  return r.json();
}

function normalizeRow(row){
  if(!row) return row;
  if(typeof row.preferred_nationalities === "string"){
    const s = row.preferred_nationalities.trim();
    if(!s){
      row.preferred_nationalities = [];
    }else{
      try {
        const parsed = JSON.parse(s);
        row.preferred_nationalities = Array.isArray(parsed) ? parsed : [String(parsed)];
      } catch(e){
        row.preferred_nationalities = s.split(",").map(x=>x.trim()).filter(Boolean);
      }
    }
  }
  return row;
}

async function loadGrid(){
  const res = await api("list");
  if(!res.ok){ alert(res.error || "Load failed"); return; }

  (res.rows || []).forEach(r=>normalizeRow(r));
  table.setData(res.rows);

  originalData.clear();
  (res.rows || []).forEach(row => originalData.set(row.id, JSON.parse(JSON.stringify(row))));

  editedRows.clear();
  updateEditHint();
}

function showSaveNotif(){
  const el = document.getElementById("saveNotif");
  el.classList.add("show");
  setTimeout(()=>{ el.classList.remove("show"); }, 2000);
}

function updateEditHint(){
  const hint = document.getElementById("editHint");
  if(editedRows.size > 0){
    hint.innerHTML = `<strong style="color:#dc2626">${editedRows.size}行</strong> 編集中 → <strong>更新</strong>ボタンで保存`;
  } else {
    hint.innerHTML = 'セルをクリックして編集 → <strong>更新</strong>ボタンで保存';
  }
}

function markRowEdited(rowId){
  editedRows.add(rowId);
  const row = table.getRow(rowId);
  if(row){
    const elem = row.getElement();
    if(elem) elem.classList.add("edited");
  }
  updateEditHint();
}

function normalizeVal(v){
  if(Array.isArray(v)) return JSON.stringify(v.slice().sort());
  if(v === undefined) return null;
  return v;
}
function rowHasDiff(row){
  const id = row.id;
  const orig = originalData.get(id);
  if(!orig) return true;
  for(const f of TRACK_FIELDS){
    const a = normalizeVal(row[f]);
    const b = normalizeVal(orig[f]);
    if(String(a ?? "") !== String(b ?? "")) return true;
  }
  return false;
}

function openDrawer(job, autoPick=false){
  currentJobId = job.id;
  document.getElementById("dTitle").textContent = `#${job.id} ${job.company_name || "(施設名未入力)"}`;
  document.getElementById("dSub").textContent = `状況: ${job.status || "-"}`;
  document.getElementById("drawerBack").classList.add("open");
  document.getElementById("drawer").classList.add("open");
  loadFiles(job.id).then(()=>{
    if(autoPick){
      const inp = document.getElementById("fileInput");
      inp && inp.click();
    }
  });
}
function closeDrawer(){
  document.getElementById("drawerBack").classList.remove("open");
  document.getElementById("drawer").classList.remove("open");
  currentJobId = null;
}
document.getElementById("drawerBack").addEventListener("click", closeDrawer);
document.getElementById("drawerClose").addEventListener("click", closeDrawer);

async function loadFiles(jobId){
  const r = await fetch(`jobs_api.php?action=files&id=${jobId}`, {credentials:"same-origin"});
  const res = await r.json();
  const box = document.getElementById("filesBox");
  box.innerHTML = "";

  if(!res.ok){ box.textContent = res.error || "読み込み失敗"; return; }
  if(!res.files.length){ box.innerHTML = `<div class="muted">ファイルなし</div>`; return; }

  for(const f of res.files){
    const card = document.createElement("div");
    card.style.cssText = `width:170px;display:flex;flex-direction:column;gap:8px;align-items:stretch;`;

    const preview = document.createElement("div");
    preview.style.cssText = `width:170px;height:120px;border:1px solid var(--border);border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fafafa;cursor:pointer;`;

    const openUrl = f.file_path;

    if((f.mime||"").startsWith("image/")){
      preview.innerHTML = `<img src="${f.file_path}" alt="" style="width:100%;height:100%;object-fit:cover">`;
    }else{
      preview.innerHTML = `
        <div style="font-size:12px;color:#0b3772;font-weight:900;text-align:center;line-height:1.25;padding:10px">
          PDF<br>
          <span style="font-weight:700;font-size:11px;opacity:.85;display:block;word-break:break-all;margin-top:6px">
            ${escapeHtml(f.file_name||"")}
          </span>
        </div>
      `;
    }

    preview.addEventListener("click", ()=> window.open(openUrl, "_blank"));

    const delBtn = document.createElement("button");
    delBtn.type = "button";
    delBtn.textContent = "削除";
    delBtn.style.cssText = `height:34px;border-radius:8px;border:2px solid #ef4444;background:#fff;color:#ef4444;font-weight:900;cursor:pointer;`;

    delBtn.addEventListener("click", async (e)=>{
      e.preventDefault();
      e.stopPropagation();

      if(!confirm(`このファイルを削除しますか？\n${f.file_name || ""}`)) return;

      const res2 = await api("deleteFile", { file_id: f.id, job_id: jobId });
      if(!res2.ok){
        alert(res2.error || "削除失敗");
        return;
      }

      await loadFiles(jobId);
      await loadGrid();
    });

    card.appendChild(preview);
    card.appendChild(delBtn);
    box.appendChild(card);
  }
}

document.getElementById("uploadForm").addEventListener("submit", async (e)=>{
  e.preventDefault();
  if(!currentJobId){ alert("求人を選択してください"); return; }
  const file = document.getElementById("fileInput").files[0];
  if(!file){ alert("ファイルを選択してください"); return; }
  const form = new FormData();
  form.append("id", currentJobId);
  form.append("file", file);
  form.append("csrf_token", CSRF);
  const r = await fetch("jobs_api.php?action=uploadFile", {method:"POST", body:form, credentials:"same-origin"});
  const res = await r.json();
  if(!res.ok){ alert(res.error || "アップロード失敗"); return; }
  document.getElementById("fileInput").value = "";
  await loadFiles(currentJobId);
  await loadGrid();
});

function statusSort(a,b){
  const m = {"急募":0,"募集中":1,"募集終":2};
  return (m[a] ?? 9) - (m[b] ?? 9);
}

const table = new Tabulator("#jobsGrid",{
  height:"74vh",
  layout:"fitColumns",
  reactiveData:true,
  placeholder:"データがありません",
  initialSort:[{column:"status", dir:"asc"}],

  columns:[
    {title:"", field:"__del", width:80, hozAlign:"center", headerSort:false,
      formatter:()=>"<button class='btnDel'>削除</button>",
      cellClick: async (e, cell)=>{
        const row = cell.getRow().getData();
        if(!confirm(`#${row.id} を削除しますか？（DBからも削除）`)) return;
        const res = await api("delete",{id: row.id});
        if(!res.ok){ alert(res.error || "削除失敗"); return; }
        editedRows.delete(row.id);
        originalData.delete(row.id);
        cell.getRow().delete();
        updateEditHint();
      }
    },

    {title: thUnused("施設名"), field:"company_name", editor:"input", width:220},
    {title: thPublic("状況"), field:"status", sorter:statusSort, formatter:(c)=>statusPill(c.getValue()), editor:"list",
      editorParams:{values: statusOpts, freetext:false, autocomplete:false, clearable:false, listOnEmpty:true}, width:120},

    {title: thUnused("職種"), field:"org_work_type", formatter:(c)=>chip(c.getValue()), editor:"list",
      editorParams:{values: workTypeOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:170},

    {title: thUnused("最終更新"), field:"updated_at", sorter:"datetime", width:150},
    {title: thUnused("受注日"), field:"request_date", editor:"input", editorParams:{elementAttributes:{type:"date"}}, width:130},
    {title: thUnused("締切日"), field:"deadline_date", editor:"input", editorParams:{elementAttributes:{type:"date"}}, width:130},
    {title: thUnused("求人担当"), field:"job_staff_id", formatter:(c)=>staffChipById(c.getValue()), editor:"list",
      editorParams:{values: STAFF_VALUE_LIST, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:190},
    {title: thUnused("メモ詳細"), field:"level", editor:"input", width:220},

    {title: thPublic("タイトル"), field:"title", editor:"input", width:260},
    {title: thPublic("キーワード"), field:"summary", editor:"textarea", width:320},
    {title: thPublic("業務内容"), field:"content", editor:"textarea", width:360},

    {title: thPublic("勤務地"), field:"job_location", editor:"list",
      editorParams:{values: prefectureOpts, clearable:true, autocomplete:true, freetext:true}, width:150},

    {title: thPublic("勤務地住所(詳細)"), field:"work_location_detail", editor:"input", width:240},
    {title: thPublic("契約期間"), field:"contract_period", editor:"input", width:180},
    {title: thPublic("試用期間"), field:"probation_period", editor:"input", width:180},
    {title: thPublic("業務変更の範囲"), field:"job_change_scope", editor:"textarea", width:260},
    {title: thPublic("就業場所変更の範囲"), field:"workplace_change_scope", editor:"textarea", width:260},
    {title: thPublic("就業時間(シフト)"), field:"work_hours_shift", editor:"textarea", width:260},
    {title: thPublic("休憩時間"), field:"break_time", editor:"input", width:160},
    {title: thPublic("時間外労働"), field:"overtime", editor:"textarea", width:220},
    {title: thPublic("休日"), field:"holidays", editor:"textarea", width:220},
    {title: thPublic("年次有給休暇"), field:"paid_leave", editor:"textarea", width:240},
    {title: thPublic("年間休日"), field:"annual_holidays", editor:"input", width:160},

    {title: thPublic("応募者の現在地"), field:"current_residence", editor:"list",
      editorParams:{values: residenceOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:170},

    {title: thPublic("日本語レベル"), field:"japanese_level", editor:"input", width:140},
    {title: thPublic("年齢"), field:"required_age", editor:"input", width:120},
    {title: thPublic("性別"), field:"gender_pref", editor:"list",
      editorParams:{values: genderOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:150},

    {title: thPublic("経験"), field:"experience", editor:"list",
      editorParams:{values: expOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:150},

    {title: thPublic("技能・資格"), field:"skills_certifications", editor:"textarea", width:260},

    {title: thUnused("ヒジャブ"), field:"hijab_policy", editor:"list",
      editorParams:{values: hijabOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("国籍"), field:"preferred_nationalities",
      formatter:(cell)=>{
        const v = cell.getValue();
        const arr = Array.isArray(v) ? v : [];
        if(!arr.length) return "";
        return `<span class="chip"><small>${escapeHtml(arr.join(", "))}</small></span>`;
      },
      editor:"list",
      editorParams:{values: nationalityOpts, multiselect:true, clearable:true, freetext:false, autocomplete:false, listOnEmpty:true}, width:320},

    {title: thPublic("喫煙"), field:"smoking", formatter:(c)=>chip(c.getValue()), editor:"list",
      editorParams:{values: smokingOpts, freetext:true, autocomplete:true, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("募集人数"), field:"required_vacancy", editor:"input", width:120},

    {title: thPublic("月給"), field:"salary", editor:"input", width:170},
    {title: thPublic("基本給"), field:"salary_basic", editor:"input", width:140},

    // ✅ 税金・年金・保険等 → 完備/なし
    {title: thPublic("税金・年金・保険等"), field:"tax_pension_insurance", formatter:(c)=>coverChip(c.getValue()), editor:"list",
      editorParams:{values: insuranceCoverOpts, clearable:true, listOnEmpty:true}, width:170},

    {title: thPublic("手取り"), field:"salary_takehome", editor:"input", width:140},

    {title: thPublic("賞与"), field:"bonuses", formatter:(c)=>yesNoChip(c.getValue()), editor:"list",
      editorParams:{values: yesNoOpts, freetext:false, autocomplete:false, clearable:true, listOnEmpty:true}, width:120},

    {title: thPublic("賞与内容"), field:"bonus_amount", editor:"input", width:200},

    {title: thPublic("交通費"), field:"transportation_charges", formatter:(c)=>yesNoChip(c.getValue()), editor:"list",
      editorParams:{values: yesNoOpts, clearable:true, listOnEmpty:true}, width:120},

    {title: thPublic("交通費上限"), field:"transport_amount_limit", editor:"input", width:160},
    {title: thPublic("住宅手当"), field:"rent_support", editor:"input", width:160},

    {title: thPublic("生活支援"), field:"life_support", formatter:(c)=>yesNoChip(c.getValue()), editor:"list",
      editorParams:{values: yesNoOpts, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("ビザ支援"), field:"visa_support", formatter:(c)=>yesNoChip(c.getValue()), editor:"list",
      editorParams:{values: yesNoOpts, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("社会保険"), field:"social_insurance", formatter:(c)=>coverChip(c.getValue()), editor:"list",
      editorParams:{values: insuranceCoverOpts, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("昇給あり"), field:"salary_increment", formatter:(c)=>yesNoChip(c.getValue()), editor:"list",
      editorParams:{values: yesNoOpts, clearable:true, listOnEmpty:true}, width:140},

    {title: thPublic("昇給条件"), field:"increment_condition", editor:"textarea", width:240},

    {title: thUnused("求人票"), field:"files_preview", formatter:filesPreviewFormatter, width:190, headerSort:false,
      cellClick:(e, cell)=>{ const row = cell.getRow().getData(); openDrawer(row, true); }},
  ],
});

table.on("cellEdited", (cell)=>{
  const field = cell.getField();
  if(field === "files_preview" || field === "__del") return;
  const id = cell.getRow().getData().id;
  markRowEdited(id);
});

document.getElementById("btnSaveAll").addEventListener("click", async ()=>{
  if(document.activeElement) document.activeElement.blur();
  await new Promise(r=>setTimeout(r, 0));

  if(editedRows.size === 0){
    const all = table.getData();
    for(const r of all){
      if(rowHasDiff(r)) markRowEdited(r.id);
    }
  }

  if(editedRows.size === 0){
    alert("編集された行がありません。\n\nヒント：セルをクリックして値を変更してください。");
    return;
  }

  const btn = document.getElementById("btnSaveAll");
  const origText = btn.textContent;
  btn.textContent = "保存中...";
  btn.disabled = true;

  let successCount = 0;
  let failCount = 0;
  const failedIds = new Set();

  for(const rowId of Array.from(editedRows)){
    try {
      const row = table.getRow(rowId);
      if(!row){ failedIds.add(rowId); failCount++; continue; }

      const data = row.getData();
      const payload = {...data};

      if(Array.isArray(payload.preferred_nationalities)){
        payload.preferred_nationalities = JSON.stringify(payload.preferred_nationalities);
      }

      const res = await api("updateRow", { id: payload.id, data: JSON.stringify(payload) });

      if(res.ok && res.row){
        normalizeRow(res.row);
        row.update(res.row);
        originalData.set(payload.id, JSON.parse(JSON.stringify(res.row)));
        const elem = row.getElement();
        if(elem) elem.classList.remove("edited");
        successCount++;
      } else {
        failedIds.add(rowId);
        failCount++;
      }
    } catch(err) {
      failedIds.add(rowId);
      failCount++;
    }
  }

  editedRows.clear();
  failedIds.forEach(id=>editedRows.add(id));
  updateEditHint();

  btn.textContent = origText;
  btn.disabled = false;

  if(failCount > 0){
    alert(`保存完了:\n成功: ${successCount}行\n失敗: ${failCount}行`);
  } else {
    showSaveNotif();
  }

  await loadGrid();
});

document.getElementById("btnRefresh").addEventListener("click", ()=>{
  if(editedRows.size > 0){
    if(!confirm("未保存の変更があります。再読込しますか？")) return;
  }
  loadGrid();
});

document.getElementById("btnAdd").addEventListener("click", async ()=>{
  const res = await api("create",{});
  if(!res.ok){ alert(res.error || "作成失敗"); return; }
  await loadGrid();
});

document.getElementById("groupBy").addEventListener("change", (e)=>{
  const v = e.target.value;
  table.setGroupBy(v || false);
});

loadGrid();
</script>
</body>
</html>
