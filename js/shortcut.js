// JavaScript to handle sticky behavior on mobile for the shortcuts
// HTML structure: .shortcut-links-placeholder > .shortcut-links
// The placeholder stays in flow and reserves space; .shortcut-links goes fixed.
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
    const placeholder = document.querySelector('.shortcut-links-placeholder');
    const shortcutLinks = placeholder ? placeholder.querySelector('.shortcut-links') : document.querySelector('.shortcut-links');
    const header = document.querySelector('#header');
    const headerHeight = header ? header.offsetHeight : 0;
    const introSection = document.querySelector('.topSlider');
    const introBottom = introSection ? (introSection.offsetTop + introSection.offsetHeight) : 0;

    if (!shortcutLinks) return;

    if (window.scrollY >= introBottom - headerHeight) {
        shortcutLinks.classList.add('sticky');
        shortcutLinks.style.top = headerHeight + 'px';
        // Sync placeholder height to actual bar height so flow space is always correct
        if (placeholder) placeholder.style.height = shortcutLinks.offsetHeight + 'px';
    } else {
        shortcutLinks.classList.remove('sticky');
        shortcutLinks.style.top = 'auto';
        if (placeholder) placeholder.style.height = '';
    }
}, 50)); // Debounce delay of 50ms