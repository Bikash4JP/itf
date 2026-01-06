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

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/db_connect.php';

// staff dropdown (求人担当)
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

  <!-- Tabulator -->
  <link href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator.min.css" rel="stylesheet">
  <script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js"></script>

  <style>
    :root{
      --border:#e6edf6; --ink:#0b2243; --muted:#667085;
      --primary:#1e90ff; --primary-d:#1677d3;
    }
    body{margin:0;font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,"Noto Sans JP","Hiragino Kaku Gothic ProN",Meiryo,Arial,sans-serif;background:#fff}
    .wrap{max-width:1900px;margin:0 auto;padding:14px 16px}
    .toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin:14px 0}
    .btn{padding:10px 12px;border:1px solid #dbe7f5;border-radius:10px;background:#f3f9ff;color:#0b3772;cursor:pointer;text-decoration:none}
    .btn.primary{background:var(--primary);border-color:var(--primary);color:#fff}
    .btn.primary:hover{background:var(--primary-d)}
    .muted{color:var(--muted);font-size:12px}

    .pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:900;line-height:1;color:#fff}
    .pill.open{background:#16a34a}
    .pill.close{background:#2f1f1f}
    .pill.urgent{background:#dc2626}

    .chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:12px;font-weight:900;border:1px solid #dbe7f5;background:#f8fafc;color:#0b2243}
    .chip small{opacity:.75;font-weight:800}

    .btnDel{
      background:#fee2e2;border:1px solid #fecaca;color:#991b1b;
      padding:6px 10px;border-radius:10px;cursor:pointer;font-weight:900;
    }
    .btnDel:hover{filter:brightness(.98)}

    .drawerBack{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;z-index:9998}
    .drawerBack.open{display:block}
    .drawer{
      position:fixed;top:0;right:0;height:100%;width:min(560px, 96vw);background:#fff;border-left:1px solid var(--border);
      transform:translateX(100%);transition:.18s;overflow:auto;z-index:9999
    }
    .drawer.open{transform:translateX(0)}
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
    <button class="btn" id="btnRefresh">↻ 更新</button>

    <label class="muted">Group:</label>
    <select id="groupBy" class="btn" style="padding:8px 10px">
      <option value="">なし</option>
      <option value="status">状況</option>
      <option value="job_location">住所</option>
      <option value="org_work_type">職種</option>
      <option value="publish_state">公開状態</option>
      <option value="job_staff_id">求人担当</option>
    </select>

    <a class="btn" href="/saiyou.php" target="_blank">公開（求人一覧）を見る</a>
    <span class="muted">セルをクリックして編集 → 自動保存</span>
  </div>

  <div id="jobsGrid"></div>
</div>

<!-- Drawer (求人票 upload / preview) -->
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

// ===== staff values for Tabulator list editor (label/value) =====
const STAFF_NAME_BY_ID = {};
const STAFF_VALUE_LIST = (function(){
  const arr = [{label:"", value:""}];
  for(const s of STAFF_LIST){
    const id = String(s.id);
    STAFF_NAME_BY_ID[id] = s.name;
    arr.push({label: s.name, value: id}); // store id, show name
  }
  return arr;
})();

// ===== Dropdown options =====
// 状況 is already perfect (don't change logic)
const statusOpts = ["募集中","急募","募集終"];

// 職種 (strict dropdown: no freetext)
const workTypeOpts = ["介護","外食","工業製品","食品製造","その他"];

// 住所 (keep as is)
const prefTop = ["東京都","大阪府","神奈川県","埼玉県","千葉県","愛知県","福岡県","兵庫県","京都府","北海道"];
const prefAll = ["北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"];
const prefectureOpts = [...prefTop, ...prefAll.filter(p=>!prefTop.includes(p))];

// 賞与 (keep as is)
const bonusOpts = ["あり","なし"];

// 現在の居住地 (strict)
const residenceOpts = [
  {label:"国内", value:"国内"},
  {label:"国外", value:"国外"},
  {label:"どちらでもOK", value:"どちらでもOK"},
];

// 性別 (UI: 男性/女性, DB: 男/女)
const genderOpts = [
  {label:"男性", value:"男"},
  {label:"女性", value:"女"},
  {label:"どちらでもOK", value:"どちらでもOK"},
];

// 経験 (UI: あり/OK, DB: あり/どちらでもOK)
const expOpts = [
  {label:"あり", value:"あり"},
  {label:"OK", value:"どちらでもOK"},
];

// ヒジャブ (strict)
const hijabOpts = [
  {label:"OK", value:"OK"},
  {label:"禁止", value:"禁止"},
];

// 国籍 checklist (strict multi, no freetext)
const nationalityOpts = [
  "国籍問わず",
  "Japan",
  "Indonesia",
  "Nepal",
  "India",
  "Vietnam",
  "China",
  "Bangladesh",
  "Korea",
  "Myanmar",
  "Others"
];

// 公開状態 (keep)
const publishKeys = ["draft","published","archived"];
const publishLabel = {draft:"下書き", published:"公開", archived:"アーカイブ"};

let currentJobId = null;

// ===== UI helpers =====
function escapeHtml(s){
  return String(s ?? "").replace(/[&<>"']/g, (m)=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;" }[m]));
}
function hashHue(str){
  let h=0; str=String(str||"");
  for(let i=0;i<str.length;i++) h = (h*31 + str.charCodeAt(i)) >>> 0;
  return h % 360;
}
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
function publishPill(val){
  const key = (val || "draft");
  const label = publishLabel[key] || "下書き";
  return `<span class="chip"><small>${escapeHtml(label)}</small></span>`;
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

// ===== API =====
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
    else form.append(k, v);
  }
  form.append("csrf_token", CSRF);
  const r = await fetch(url, {method:"POST", body:form, credentials:"same-origin"});
  return r.json();
}

async function loadGrid(){
  const res = await api("list");
  if(!res.ok){ alert(res.error || "Load failed"); return; }
  table.setData(res.rows);
}

// ===== Drawer (求人票) =====
function openDrawer(job, autoPick=false){
  currentJobId = job.id;
  document.getElementById("dTitle").textContent = `#${job.id} ${job.company_name || "(施設名未入力)"}`;
  document.getElementById("dSub").textContent = `状況: ${job.status || "-"} / 公開: ${job.publish_state || "draft"}`;
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

  if(!res.files.length){
    box.innerHTML = `<div class="muted">ファイルなし</div>`;
    return;
  }

  for(const f of res.files){
    const wrap = document.createElement("div");
    wrap.style.width="160px";
    wrap.style.height="120px";
    wrap.style.border="1px solid var(--border)";
    wrap.style.borderRadius="10px";
    wrap.style.overflow="hidden";
    wrap.style.display="flex";
    wrap.style.alignItems="center";
    wrap.style.justifyContent="center";
    wrap.style.background="#fafafa";
    wrap.style.cursor="pointer";

    const openUrl = f.file_path;

    if((f.mime||"").startsWith("image/")){
      wrap.innerHTML = `<img src="${f.file_path}" alt="" style="width:100%;height:100%;object-fit:cover">`;
    }else{
      wrap.innerHTML = `<div style="font-size:12px;color:#0b3772;font-weight:900;text-align:center;line-height:1.2">
        PDF<br><span style="font-weight:700;font-size:11px;opacity:.85">${escapeHtml(f.file_name||"")}</span>
      </div>`;
    }
    wrap.addEventListener("click", ()=> window.open(openUrl, "_blank"));
    box.appendChild(wrap);
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

// status sort (急募 top, then 募集中, then 募集終)
function statusSort(a,b){
  const m = {"急募":0,"募集中":1,"募集終":2};
  return (m[a] ?? 9) - (m[b] ?? 9);
}

// ===== Tabulator =====
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
        cell.getRow().delete();
      }
    },

    {title:"施設名", field:"company_name", editor:"input", width:220},

    // ✅ 状況 (KEEP)
    {title:"状況", field:"status",
      sorter:statusSort,
      formatter:(c)=>statusPill(c.getValue()),
      editor:"list",
      editorParams:{
        values: statusOpts,
        freetext:false,
        autocomplete:false,
        clearable:false,
        listOnEmpty:true
      },
      width:120
    },

    // ✅ 職種 (moved here right after 状況, strict dropdown)
    {title:"職種", field:"org_work_type",
      formatter:(c)=>chip(c.getValue()),
      editor:"list",
      editorParams:{
        values: workTypeOpts,
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:170
    },

    {title:"最終更新", field:"updated_at", sorter:"datetime", width:150},

    // ✅ date inputs
    {title:"受注日", field:"request_date",
      editor:"input",
      editorParams:{elementAttributes:{type:"date"}},
      width:130
    },
    {title:"締切日", field:"deadline_date",
      editor:"input",
      editorParams:{elementAttributes:{type:"date"}},
      width:130
    },

    // ✅ 求人担当 (FIXED: real dropdown; no typing)
    {title:"求人担当", field:"job_staff_id",
      formatter:(c)=>staffChipById(c.getValue()),
      editor:"list",
      editorParams:{
        values: STAFF_VALUE_LIST,    // <--- important: label/value list
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:190
    },

    {title:"メモ詳細", field:"level", editor:"input", width:220},

    {title:"タイトル", field:"title", editor:"input", width:260},
    {title:"概要", field:"summary", editor:"textarea", width:320},
    {title:"詳細内容", field:"content", editor:"textarea", width:360},

    // ✅ 住所 (KEEP)
    {title:"住所", field:"job_location",
      editor:"list",
      editorParams:{values: prefectureOpts, clearable:true, autocomplete:true, freetext:true},
      width:150
    },

    // ✅ 賞与 (KEEP)
    {title:"賞与", field:"bonuses",
      formatter:(c)=>{
        const v = (c.getValue() || "なし");
        return v === "あり" ? `<span class="chip"><small>あり</small></span>` : `<span class="chip"><small>なし</small></span>`;
      },
      editor:"list",
      editorParams:{values: bonusOpts, clearable:true, autocomplete:true},
      width:120
    },

    {title:"賞与内容", field:"bonus_amount", editor:"input", width:200},
    {title:"月給", field:"salary", editor:"input", width:170},
    {title:"基本給", field:"salary_basic", editor:"input", width:140},
    {title:"手取り", field:"salary_takehome", editor:"input", width:140},
    {title:"交通費上限", field:"transport_amount_limit", editor:"input", width:160},
    {title:"住宅手当", field:"rent_support", editor:"input", width:160},

    // ✅ 国籍 checklist (FIXED: strict multiselect, no freetext)
    {title:"国籍", field:"nationality_pref_json",
      formatter:(cell)=>{
        const v = cell.getValue();
        const arr = Array.isArray(v) ? v : [];
        if(!arr.length) return "";
        return `<span class="chip"><small>${escapeHtml(arr.join(", "))}</small></span>`;
      },
      editor:"list",
      editorParams:{
        values: nationalityOpts,
        multiselect:true,
        clearable:true,
        freetext:false,
        autocomplete:false,
        listOnEmpty:true
      },
      width:320
    },

    // ✅ 現在の居住地 (FIXED strict)
    {title:"現在の居住地", field:"current_residence",
      editor:"list",
      editorParams:{
        values: residenceOpts,
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:170
    },

    // ✅ 性別 (FIXED: show 男性/女性, store 男/女)
    {title:"性別", field:"gender_pref",
      editor:"list",
      editorParams:{
        values: genderOpts,
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:150
    },

    // ✅ 経験 (FIXED: あり / OK)
    {title:"経験", field:"experience",
      editor:"list",
      editorParams:{
        values: expOpts,
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:150
    },

    // ✅ ヒジャブ (FIXED strict)
    {title:"ヒジャブ", field:"hijab_policy",
      editor:"list",
      editorParams:{
        values: hijabOpts,
        freetext:false,
        autocomplete:false,
        clearable:true,
        listOnEmpty:true
      },
      width:140
    },

    {title:"募集人数", field:"required_vacancy", editor:"input", width:120},
    {title:"日本語レベル", field:"japanese_level", editor:"input", width:140},

    {title:"求人票", field:"files_preview",
      formatter:filesPreviewFormatter,
      width:190,
      headerSort:false,
      cellClick:(e, cell)=>{
        const row = cell.getRow().getData();
        openDrawer(row, true);
      }
    },

    {title:"公開状態", field:"publish_state",
      formatter:(c)=>publishPill(c.getValue()),
      editor:"list",
      editorParams:{values: publishKeys, freetext:false, autocomplete:false, clearable:false, listOnEmpty:true},
      width:140
    },
  ],

  cellEdited: async function(cell){
    const row = cell.getRow().getData();
    const field = cell.getField();
    if(field === "files_preview" || field === "__del") return;

    let val = cell.getValue();

    // nationality must be array
    if(field === "nationality_pref_json"){
      if(typeof val === "string"){
        try { const j = JSON.parse(val); if(Array.isArray(j)) val = j; } catch(e){}
      }
      if(val == null) val = [];
      if(!Array.isArray(val)) val = [];
    }

    const res = await api("updateCell",{id: row.id, field: field, value: val});
    if(!res.ok){
      alert(res.error || "保存失敗");
      loadGrid();
      return;
    }
    if(res.row){
      cell.getRow().update(res.row);
    }
  },
});

document.getElementById("btnRefresh").addEventListener("click", loadGrid);
document.getElementById("btnAdd").addEventListener("click", async ()=>{
  const res = await api("create",{});
  if(!res.ok){ alert(res.error || "作成失敗"); return; }
  await loadGrid();
});
document.getElementById("groupBy").addEventListener("change", (e)=>{
  const v = e.target.value;
  table.setGroupBy(v || false);
});

// first load
loadGrid();
</script>

</body>
</html>
