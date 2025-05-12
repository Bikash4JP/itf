i18next
      .use(i18nextHttpBackend)
      .use(i18nextBrowserLanguageDetector)
      .init({
          lng: 'ja',
          fallbackLng: 'ja',
          backend: {
              loadPath: '/IDT/locales/{{lng}}/translation.json'
          },
          escapeValue: false
      }, (err, t) => {
          if (err) {
              console.error('i18next initialization error:', err);
              return;
          }
          console.log('i18next initialized with language:', i18next.language);
          updateContent();
      });

  function updateContent() {
      document.querySelectorAll('[data-i18n]').forEach(element => {
          const key = element.getAttribute('data-i18n');
          element.innerHTML = i18next.t(key);
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
      });
  });