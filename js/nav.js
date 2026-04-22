// nav.js — Mobile hamburger menu toggle (replaces inline onclick)
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('sp-menu-open');
  if (btn) {
    btn.addEventListener('click', function () {
      var menu = document.getElementById('sp-menu-acc');
      if (menu) menu.classList.toggle('active');
    });
  }
});
