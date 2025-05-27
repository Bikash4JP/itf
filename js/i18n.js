document.addEventListener('DOMContentLoaded', function() {
    // Store the original HTML content of all elements with data-i18n attributes
    const elements = document.querySelectorAll('[data-i18n]');
    const originalContents = new Map();
    elements.forEach(element => {
        originalContents.set(element, element.innerHTML);
    });

    // Initialize i18next
    i18next
        .use(i18nextHttpBackend)
        .use(i18nextBrowserLanguageDetector)
        .init({
            lng: 'ja', // Default language
            fallbackLng: false, // Disable fallback to avoid loading translations for 'ja'
            supportedLngs: ['en', 'id', 'vi', 'zh', 'ne', 'tl', 'ko', 'hi', 'bn'], // Languages that have translation files
            backend: {
                loadPath: 'locales/{{lng}}.json'
            },
            detection: {
                order: ['localStorage', 'navigator'],
                caches: ['localStorage']
            },
            interpolation: {
                escapeValue: false // React already escapes by default, not needed for pure HTML
            },
            load: 'currentOnly' // Only load the current language, not fallback
        }, (err, t) => {
            if (err) {
                // If the error is due to missing 'ja' translation (which is expected), proceed anyway
                if (i18next.language === 'ja') {
                    updateContent();
                    return;
                }
                console.error('i18next initialization error:', err);
                return;
            }

            // Function to update translations
            function updateContent() {
                const currentLang = i18next.language;

                if (currentLang === 'ja') {
                    // Revert to original HTML content for Japanese
                    elements.forEach(element => {
                        element.innerHTML = originalContents.get(element);
                    });
                } else {
                    // Apply translations for other languages
                    elements.forEach(element => {
                        const key = element.getAttribute('data-i18n');
                        element.innerHTML = i18next.t(key);
                    });
                }
            }

            // Initial content update
            updateContent();

            // Language selector handling
            const languageSelector = document.getElementById('language-selector');
            if (languageSelector) {
                // Set the selector to the current language
                languageSelector.value = i18next.language || 'ja';

                languageSelector.addEventListener('change', (e) => {
                    const newLang = e.target.value;
                    if (newLang === 'ja') {
                        // For Japanese, revert to original content without loading translations
                        i18next.changeLanguage('ja', () => {
                            updateContent();
                        });
                    } else {
                        // For other languages, load the translation file
                        i18next.changeLanguage(newLang, (err) => {
                            if (err) {
                                console.error('Error changing language:', err);
                                return;
                            }
                            updateContent();
                        });
                    }
                });
            }

            // Listen for language changes
            i18next.on('languageChanged', () => {
                updateContent();
            });
        });
});