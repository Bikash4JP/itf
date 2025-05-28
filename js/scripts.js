document.addEventListener('DOMContentLoaded', function() {
    const bars = document.querySelectorAll('.jlpt-bar');
    bars.forEach((bar, index) => {
      bar.style.setProperty('--jlpt-final-height', bar.style.height);
      bar.style.setProperty('--jlpt-order', index);
    });
});

// Phone call JS 
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

// Mail redirectors JS
document.addEventListener("DOMContentLoaded", function () {
    const mailButton = document.getElementById("mailBtn");

    // Add null check to prevent errors on pages without mailButton
    if (mailButton) {
        mailButton.addEventListener("click", function () {
            window.location.href = "mailto:info@it-future.jp";
        });
    }
});

// Back to Top Button Functionality
document.addEventListener('DOMContentLoaded', () => {
    const backToTopButton = document.getElementById('back-to-top');

    // Show/hide button based on scroll position
    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY;
        const viewportHeight = window.innerHeight;

        if (scrollPosition > viewportHeight) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });

    // Smooth scroll to top on click
    backToTopButton.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    const rellaxElements = document.querySelectorAll('.rellax');
    if (rellaxElements.length > 0) {
        console.log("Rellax elements found, initializing Rellax...");
        new Rellax('.rellax', {
            speed: -2,
            center: false,
            wrapper: null,
            relativeToWrapper: false,
            round: true,
            vertical: true,
            horizontal: false,
            breakpoints: [576, 768, 1201]
        });
    } else {
        console.log("No Rellax elements found on this page, skipping initialization.");
    }
});