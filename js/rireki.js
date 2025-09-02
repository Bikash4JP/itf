// js/rireki.js
(function () {
  let initDone = false;

  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }
  document.addEventListener("turbo:load", () => !initDone && init());

  onReady(() => !initDone && init());

  function init() {
    if (initDone) return;
    initDone = true;

    // ======= Helpers =======
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    // Build options
    function fillNumberOptions(select, start, end, { pad = false, emptyFirst = true } = {}) {
      if (!select) return;
      select.innerHTML = "";
      if (emptyFirst) {
        const opt = document.createElement("option");
        opt.value = "";
        opt.textContent = "";
        select.appendChild(opt);
      }
      const step = start <= end ? 1 : -1;
      for (let v = start; step > 0 ? v <= end : v >= end; v += step) {
        const opt = document.createElement("option");
        opt.value = String(v);
        opt.textContent = pad ? String(v).padStart(2, "0") : String(v);
        select.appendChild(opt);
      }
    }

    // Unique key for Rails nested_attributes (e.g., "new_1690000000123_1")
    function newKey(prefix) {
      const n = Date.now();
      const rand = Math.floor(Math.random() * 100000);
      return `new_${prefix}_${n}_${rand}`;
    }

    // ======= Populate date selects (current date) =======
    const currentYearSel = $("#current_date_year");
    const currentMonthSel = $("#current_date_month");
    const currentDaySel = $("#current_date_day");

    const now = new Date();
    const currentYear = now.getFullYear();
    fillNumberOptions(currentYearSel, 2015, currentYear + 5, { emptyFirst: true });
    fillNumberOptions(currentMonthSel, 1, 12, { pad: false, emptyFirst: true });
    fillNumberOptions(currentDaySel, 1, 31, { pad: false, emptyFirst: true });

    // Preselect today's date
    if (currentYearSel) currentYearSel.value = String(currentYear);
    if (currentMonthSel) currentMonthSel.value = String(now.getMonth() + 1);
    if (currentDaySel) currentDaySel.value = String(now.getDate());

    // ======= Populate birthday selects & age calc =======
    const birthYearSel = $("#birth_year");
    const birthMonthSel = $("#birth_month");
    const birthDaySel = $("#birth_day");
    const ageSpan = $("#age");

    fillNumberOptions(birthYearSel, currentYear, 1925, { emptyFirst: true }); // descending years
    fillNumberOptions(birthMonthSel, 1, 12, { emptyFirst: true });
    fillNumberOptions(birthDaySel, 1, 31, { emptyFirst: true });

    function calcAge() {
      if (!birthYearSel || !birthMonthSel || !birthDaySel || !ageSpan) return;
      const y = parseInt(birthYearSel.value, 10);
      const m = parseInt(birthMonthSel.value, 10);
      const d = parseInt(birthDaySel.value, 10);
      if (!y || !m || !d) {
        ageSpan.textContent = "";
        return;
      }
      const dob = new Date(y, m - 1, d);
      const today = new Date();
      let age = today.getFullYear() - dob.getFullYear();
      const mdiff = today.getMonth() - dob.getMonth();
      if (mdiff < 0 || (mdiff === 0 && today.getDate() < dob.getDate())) age--;
      ageSpan.textContent = String(age);
    }

    [birthYearSel, birthMonthSel, birthDaySel].forEach(sel => {
      if (sel) sel.addEventListener("change", calcAge);
    });
    calcAge();

    // ======= Photo upload & drag-drop =======
    const photoArea = $("#photo-area");
    const fileInput = $("#photo-input");
    const photoPreview = $("#photo-preview");
    const photoPlaceholder = $("#photo-placeholder");
    const existingPhoto = $("#existing-photo");

    function setPreview(src) {
      if (photoPreview) {
        photoPreview.src = src;
        photoPreview.style.display = "block";
      }
      if (photoPlaceholder) photoPlaceholder.style.display = "none";
      if (existingPhoto) existingPhoto.style.display = "none";
    }

    function clearPreview() {
      if (photoPreview) {
        photoPreview.src = "";
        photoPreview.style.display = "none";
      }
      if (existingPhoto) {
        existingPhoto.style.display = "block";
        if (photoPlaceholder) photoPlaceholder.style.display = "none";
      } else {
        if (photoPlaceholder) photoPlaceholder.style.display = "block";
      }
    }

    function handleFile(file) {
      if (!file) return;
      if (file.type && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = e => setPreview(e.target.result);
        reader.readAsDataURL(file);
      } else {
        clearPreview();
      }
    }

    if (photoArea && fileInput) {
      if (existingPhoto) {
        photoPlaceholder && (photoPlaceholder.style.display = "none");
        existingPhoto.style.display = "block";
      }
      photoArea.addEventListener("click", () => fileInput.click());
      fileInput.addEventListener("change", (e) => handleFile(e.target.files[0]));

      photoArea.addEventListener("dragover", (e) => {
        e.preventDefault(); e.stopPropagation();
        photoArea.classList.add("drag-over");
      });
      photoArea.addEventListener("dragleave", (e) => {
        e.preventDefault(); e.stopPropagation();
        photoArea.classList.remove("drag-over");
      });
      photoArea.addEventListener("drop", (e) => {
        e.preventDefault(); e.stopPropagation();
        photoArea.classList.remove("drag-over");
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
          const file = files[0];
          // reflect to input
          const dt = new DataTransfer();
          dt.items.add(file);
          fileInput.files = dt.files;
          handleFile(file);
        }
      });
    }

    // ======= Dynamic rows: 学歴 / 職歴 / 免許・資格 =======
    const educationBody = $("#education_tbody");
    const employmentBody = $("#employment_tbody");
    const certificatesBody = $("#certificates_tbody");

    // Create a <select> with 1-12 months
    function createMonthSelect(name, id) {
      const sel = document.createElement("select");
      sel.className = "select optional text-align-right";
      sel.name = name;
      sel.id = id;
      fillNumberOptions(sel, 1, 12, { emptyFirst: true });
      return sel;
    }

    // Create a number <input> for year (1925..2035)
    function createYearInput(name, id) {
      const inp = document.createElement("input");
      inp.type = "number";
      inp.className = "numeric integer optional auto-select-when-focus text-align-right";
      inp.name = name;
      inp.id = id;
      inp.step = "1";
      inp.min = "1925";
      inp.max = "2035";
      return inp;
    }

    // Create a number <input> for sort
    function createSortInput(name, id) {
      const inp = document.createElement("input");
      inp.type = "number";
      inp.className = "numeric integer optional auto-select-when-focus text-align-right";
      inp.name = name;
      inp.id = id;
      inp.step = "1";
      return inp;
    }

    // Create a text <input> for content
    function createContentInput(name, id, placeholder) {
      const inp = document.createElement("input");
      inp.type = "text";
      inp.className = "string optional";
      inp.name = name;
      inp.id = id;
      inp.placeholder = placeholder || "";
      return inp;
    }

    function buildRow(section) {
      const tr = document.createElement("tr");
      tr.className = "nested-fields";
      const key = newKey(section);

      // names follow Rails nested attributes convention:
      // resume_form[<section>_attributes][<key>][field]
      const prefix = `resume_form[${section}_attributes][${key}]`;

      const tdYear = document.createElement("td");
      const divYear = document.createElement("div");
      divYear.className = `input integer optional resume_form_${section}_year`;
      const yearInput = createYearInput(`${prefix}[year]`, `${section}_${key}_year`);
      divYear.appendChild(yearInput);
      tdYear.appendChild(divYear);

      const tdMonth = document.createElement("td");
      const divMonth = document.createElement("div");
      divMonth.className = `input select optional resume_form_${section}_month`;
      const monthSelect = createMonthSelect(`${prefix}[month]`, `${section}_${key}_month`);
      divMonth.appendChild(monthSelect);
      tdMonth.appendChild(divMonth);

      const tdContent = document.createElement("td");
      // content
      const divContent = document.createElement("div");
      divContent.className = `input string optional resume_form_${section}_content`;
      let placeholder = "";
      if (section === "education_histories") placeholder = "例：山口短期大学　情報メディア学科　卒業";
      if (section === "employment_histories") placeholder = "例：株式会社NTQジャパン　入社";
      if (section === "certificates") placeholder = "例：日本語能力試験 JLPT N1";
      const contentInput = createContentInput(`${prefix}[content]`, `${section}_${key}_content`, placeholder);
      divContent.appendChild(contentInput);

      // sort label + input
      const sortLabel = document.createElement("span");
      sortLabel.textContent = "表示順";
      const divSort = document.createElement("div");
      divSort.className = `input integer optional resume_form_${section}_sort`;
      const sortInput = createSortInput(`${prefix}[sort]`, `${section}_${key}_sort`);
      divSort.appendChild(sortInput);

      // _destroy hidden (for consistency)
      const destroyInput = document.createElement("input");
      destroyInput.type = "hidden";
      destroyInput.name = `${prefix}[_destroy]`;
      destroyInput.value = "false";

      // remove link
      const remove = document.createElement("a");
      remove.href = "#";
      remove.className = "remove-row";
      remove.textContent = "削除";
      remove.addEventListener("click", (e) => {
        e.preventDefault();
        tr.remove();
      });

      // assemble tdContent
      tdContent.appendChild(divContent);
      tdContent.appendChild(sortLabel);
      tdContent.appendChild(divSort);
      tdContent.appendChild(destroyInput);
      tdContent.appendChild(remove);

      tr.appendChild(tdYear);
      tr.appendChild(tdMonth);
      tr.appendChild(tdContent);

      return tr;
    }

    function handleAdd(section) {
      let body;
      let sectionKey;
      if (section === "education") {
        body = educationBody;
        sectionKey = "education_histories";
      } else if (section === "employment") {
        body = employmentBody;
        sectionKey = "employment_histories";
      } else if (section === "certificates") {
        body = certificatesBody;
        sectionKey = "certificates";
      } else {
        return;
      }
      const row = buildRow(sectionKey);
      body && body.appendChild(row);
    }

    $$(".add-row").forEach(a => {
      a.addEventListener("click", (e) => {
        e.preventDefault();
        const section = e.currentTarget.getAttribute("data-section");
        handleAdd(section);
      });
    });

    // Optional: add one empty row for better UX
    // handleAdd("education");
    // handleAdd("employment");
    // handleAdd("certificates");
  }
})();
