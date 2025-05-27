// JavaScript to handle sticky behavior on mobile for the shortcuts
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

window.addEventListener('scroll', debounce(function() {
    const shortcutLinks = document.querySelector('.shortcut-links');
    const header = document.querySelector('#header');
    const headerHeight = header ? header.offsetHeight : 0;
    const introSection = document.querySelector('.topSlider'); // Updated to target the new intro section
    const introBottom = introSection ? (introSection.offsetTop + introSection.offsetHeight) : 0;

    if (window.scrollY >= introBottom - headerHeight) {
        shortcutLinks.classList.add('sticky');
        shortcutLinks.style.top = headerHeight + 'px';
    } else {
        shortcutLinks.classList.remove('sticky');
        shortcutLinks.style.top = 'auto';
    }
}, 50)); // Debounce delay of 50ms