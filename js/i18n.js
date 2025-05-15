i18next
  .use(i18nextHttpBackend)
  .use(i18nextBrowserLanguageDetector)
  .init({
    lng: 'ja', // Default language is Japanese
    fallbackLng: false, // Disable fallback to prevent automatic file load
    // Skip loading translations for 'ja'
    preload: [], // Prevent preloading any languages
    ns: ['translation'], // Namespace for translations
    defaultNS: 'translation',
    backend: {
      loadPath: 'locales/{{lng}}/translation.json', // Path to translation files
      // Custom load function to skip loading for 'ja'
      load: (languages, namespaces, callback) => {
        console.log('Attempting to load translation for language:', languages[0]);
        if (languages[0] === 'ja') {
          // Skip loading translation file for 'ja'
          console.log('Skipping translation file load for ja');
          callback(null, {});
        } else {
          // Load translation file for other languages
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
    // Include 'ja' in supported languages, but it won't load a file due to custom load function
    supportedLngs: ['ja', 'id', 'vi', 'en', 'zh', 'ne', 'tl', 'ko', 'hi', 'bn'],
    escapeValue: false, // React already safes from XSS
    debug: true // Enable debug logging to see translation loading
  }, (err, t) => {
    if (err) {
      console.error('i18next initialization error:', err);
      // Proceed with updateContent even if there's an error
      storeOriginalContent();
      updateContent();
      return;
    }
    console.log('i18next initialized with language:', i18next.language);
    // Store original content before any updates
    storeOriginalContent();
    updateContent();
  });

// Object to store the original HTML content of each element
const originalContents = {};

function storeOriginalContent() {
  document.querySelectorAll('[data-i18n]').forEach(element => {
    const key = element.getAttribute('data-i18n');
    if (!originalContents[key]) { // Only store if not already set to prevent overwriting
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
      // For Japanese, always restore the original HTML content
      console.log(`Restoring original content for key '${key}' (language: ja):`, originalContent);
      element.innerHTML = originalContent;
    } else {
      // For other languages, apply the translation
      const translated = i18next.t(key);
      console.log(`Translating key '${key}' for language '${i18next.language}':`, translated);
      // If the translation is the same as the key or undefined, fall back to original content
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
    // Ensure updateContent is called even if there's an error
    updateContent();
  });
});