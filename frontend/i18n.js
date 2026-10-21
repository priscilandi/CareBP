const I18N = { current: 'en', fallback: 'en'};
const LANGUAGE_NAMES = {en: 'English', es: 'Español'};   // each language's name in itself

// Claude Generated 
// Languages that actually have entries in the translations files (an empty dictionary doesn't count).
function availableLanguages() {
    const all = window.TRANSLATIONS || {};
    return Object.keys(all).filter(code => Object.keys(all[code]).length > 0);
}

// Text for a key in the current language, then English. Returns undefined if neither has it.
function lookupTranslation(key) {
    const all = window.TRANSLATIONS || {};
    return all[I18N.current]?.[key] || all[I18N.fallback]?.[key];
}

// For strings built in script.js: the translation, or the key itself if not found (easy to spot).
function t(key) {
    return lookupTranslation(key) ?? key;
}
// End Claude Generated
 
// Update the language toggle button to show the next language in the list, and hide it if there's only one language available.
function updateLanguageToggle() {
    const button = document.getElementById('lang-toggle');
    if (!button) 
        return;
    const langs = availableLanguages();
    button.hidden = langs.length < 2;

    if(button.hidden) {
        return;
    }
    const next = langs[(langs.indexOf(I18N.current) + 1) % langs.length];
    const label = button.querySelector('[data-lang-label]') || button;
    label.textContent = LANGUAGE_NAMES[next] || next;
    button.lang = next;                       // so screen readers pronounce it correctly
    button.dataset.nextLang = next;
}

// Apply a language to the page, updating all elements with data-i18n attributes. Saves the choice in localStorage. 
function applyLanguage(lang) {
    if (!availableLanguages().includes(lang)) lang = I18N.fallback;
    I18N.current = lang;
    document.documentElement.lang = lang;
 
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const text = lookupTranslation(el.dataset.i18n);
        if (text !== undefined) el.textContent = text;     // missing key: leave the HTML text alone
    });
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const text = lookupTranslation(el.dataset.i18nPlaceholder);
        if (text !== undefined) el.placeholder = text;
    });
    document.querySelectorAll('[data-i18n-aria-label]').forEach(el => {
        const text = lookupTranslation(el.dataset.i18nAriaLabel);
        if (text !== undefined) el.setAttribute('aria-label', text);
    });
    updateLanguageToggle();
    try { localStorage.setItem('carebp-lang', lang); } catch {}
}
 
// Initialize the language on page load, and set up the toggle button.
(function initI18n() {
    const button = document.getElementById('lang-toggle');
    if (button) button.addEventListener('click', () => applyLanguage(button.dataset.nextLang || I18N.fallback));
    let saved = null;
    try { saved = localStorage.getItem('carebp-lang'); } catch {}
    // English is already in the HTML, so only swap if the visitor previously chose another language.
    if (saved && saved !== I18N.fallback) applyLanguage(saved);
    updateLanguageToggle();
})();