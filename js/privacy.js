i18next
    .use(i18nextHttpBackend)
    .use(i18nextBrowserLanguageDetector)
    .init({
        lng: 'ja',
        fallbackLng: false,
        preload: [],
        ns: ['translation'],
        defaultNS: 'translation',
        backend: {
            loadPath: 'locales/{{lng}}/translation.json',
            load: (languages, namespaces, callback) => {
                console.log('Attempting to load translation for language:', languages[0]);
                if (languages[0] === 'ja') {
                    console.log('Skipping translation file load for ja');
                    callback(null, {});
                } else {
                    console.log('Fetching translation file:', `locales/${languages[0]}/translation.json`);
                    fetch(`locales/${languages[0]}/translation.json`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log(`Translation file loaded for ${languages[0]}:`, data);
                            callback(null, data);
                        })
                        .catch(err => {
                            console.error(`Error loading translation file for ${languages[0]}:`, err);
                            callback(err, {});
                        });
                }
            }
        },
        supportedLngs: ['ja', 'id', 'vi', 'en', 'zh', 'ne', 'tl', 'ko', 'hi', 'bn'],
        escapeValue: false,
        debug: true
    }, (err, t) => {
        if (err) {
            console.error('i18next initialization error:', err);
            storeOriginalContent();
            updateContent();
            return;
        }
        console.log('i18next initialized with language:', i18next.language);
        storeOriginalContent();
        updateContent();
    });

const originalContents = {};

function storeOriginalContent() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        if (!originalContents[key]) {
            originalContents[key] = element.innerHTML;
            console.log(`Stored original content for key '${key}':`, originalContents[key]);
        }
    });
}

function updateContent() {
    document.querySelectorAll('[data-i18n]').forEach(element => {
        const key = element.getAttribute('data-i18n');
        const originalContent = originalContents[key] || element.innerHTML;
        if (i18next.language === 'ja') {
            console.log(`Restoring original content for key '${key}' (language: ja):`, originalContent);
            element.innerHTML = originalContent;
        } else {
            const translated = i18next.t(key);
            console.log(`Translating key '${key}' for language '${i18next.language}':`, translated);
            element.innerHTML = (translated === key || translated === undefined) ? originalContent : translated;
        }
    });
}

document.getElementById('language-selector').addEventListener('change', (event) => {
    const newLang = event.target.value;
    console.log('Switching language to:', newLang);
    i18next.changeLanguage(newLang).then(() => {
        console.log('Language switched to:', i18next.language);
        updateContent();
    }).catch(err => {
        console.error('Error switching language:', err);
        updateContent();
    });
});

// Back to Top Button Functionality (Same as index.html)
document.addEventListener('DOMContentLoaded', () => {
    const backToTopButton = document.getElementById('back-to-top');

    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY;
        const viewportHeight = window.innerHeight;

        if (scrollPosition > viewportHeight) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });

    backToTopButton.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});