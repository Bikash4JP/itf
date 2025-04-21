document.addEventListener('DOMContentLoaded', function() {
    const bars = document.querySelectorAll('.jlpt-bar');
    bars.forEach((bar, index) => {
      bar.style.setProperty('--jlpt-final-height', bar.style.height);
      bar.style.setProperty('--jlpt-order', index);
    });
  });
  // phone call js 
  document.addEventListener("DOMContentLoaded", function () {
    const phoneLink = document.querySelector(".phone-link");

    phoneLink.addEventListener("click", function (e) {
        const isMobile = /Mobi|Android/i.test(navigator.userAgent);
        if (!isMobile) {
            alert("This link will try to start a phone call.");
        }
        // For mobile, the browser will handle `tel:` automatically.
    });
});
// mail redirectors Js =======================================================
document.addEventListener("DOMContentLoaded", function () {
  const mailButton = document.getElementById("mailBtn");

  mailButton.addEventListener("click", function () {
      window.location.href = "mailto:ueda@it-future.jp";
  });
});
