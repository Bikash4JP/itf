// postal_autofill.js — Japan zip code lookup via server-side PHP proxy (avoids CORS/CSP)
(function () {
  const inp  = document.getElementById('postal');
  const auto = document.getElementById('address_auto');
  const hint = document.getElementById('postalHint');
  const btn  = document.getElementById('btnPostal');
  if (!inp || !auto || !hint) return;

  async function lookup() {
    const zip = inp.value.replace(/[^\d]/g, '');
    if (zip.length !== 7) {
      hint.textContent = zip.length > 0 ? '7桁の数字を入力してください' : '';
      hint.style.color = '#7dc8ff';
      return;
    }
    hint.textContent = '🔍 検索中...';
    hint.style.color = '#7dc8ff';
    try {
      // Use server-side proxy to avoid browser CSP / CORS blocks
      const r = await fetch('/rireki/kaigo/php/postal_proxy.php?zip=' + zip, {
        credentials: 'same-origin'
      });
      if (!r.ok) throw new Error('proxy_error');
      const j = await r.json();
      if (!j.results || !j.results[0]) {
        hint.textContent = '⚠ 該当する住所が見つかりませんでした';
        hint.style.color = '#f85149';
        return;
      }
      const res  = j.results[0];
      const addr = (res.address1 || '') + (res.address2 || '') + (res.address3 || '');
      // Set value
      auto.value = addr;
      // ✅ Fire input + change events so autosave picks up the new value
      auto.dispatchEvent(new Event('input',  { bubbles: true }));
      auto.dispatchEvent(new Event('change', { bubbles: true }));
      hint.textContent = '✓ 自動入力: ' + addr;
      hint.style.color = '#3fb950';
    } catch (_) {
      hint.textContent = '⚠ 住所を取得できませんでした。手動で入力してください。';
      hint.style.color = '#f85149';
    }
  }

  if (btn) btn.addEventListener('click', lookup);
  inp.addEventListener('blur', lookup);
})();
