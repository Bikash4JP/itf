// /rireki/kaigo/js/preview.js
// Public maker registration validation on preview page
(function(){
  const form = document.getElementById('openResumeRegisterForm');
  if (!form) return;

  const p1 = document.getElementById('reg_pass');
  const p2 = document.getElementById('reg_pass2');
  const hint = document.getElementById('pass_hint');
  const btn = document.getElementById('confirmDownloadBtn');

  function setHint(msg, ok){
    if (!hint) return;
    hint.textContent = msg || '';
    hint.style.color = ok ? '#1f7a1f' : '#a12a2a';
  }

  function validate(){
    const v1 = (p1?.value || '');
    const v2 = (p2?.value || '');
    let ok = true;

    if (v1.length < 8) {
      ok = false;
      setHint('パスワードは8文字以上で入力してください。', false);
    } else if (v2.length > 0 && v1 !== v2) {
      ok = false;
      setHint('パスワードが一致しません。', false);
    } else if (v2.length > 0 && v1 === v2) {
      setHint('パスワード確認OK', true);
    } else {
      setHint('', true);
    }

    if (btn) btn.disabled = !ok;
  }

  if (btn) btn.disabled = true;

  form.addEventListener('input', validate);
  form.addEventListener('submit', function(e){
    // final check
    const v1 = (p1?.value || '');
    const v2 = (p2?.value || '');
    if (v1.length < 8 || v1 !== v2) {
      e.preventDefault();
      validate();
      alert('パスワードを確認してください。');
      return false;
    }
    return true;
  });

  validate();
})();
