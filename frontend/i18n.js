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
 
// Fill the language dropdown from the languages that have translations, and hide it if there's only one.
function updateLanguageSelect() {
    const select = document.getElementById('lang-select');
    if (!select) return;
    const langs = availableLanguages();
    select.hidden = langs.length < 2;
    if (select.hidden) return;
 
    if (select.options.length !== langs.length) {
        select.innerHTML = '';
        langs.forEach(code => {
            const option = document.createElement('option');
            option.value = code;
            option.lang = code;                       // so screen readers pronounce each name correctly
            option.textContent = LANGUAGE_NAMES[code] || code;
            select.appendChild(option);
        });
    }
    select.value = I18N.current;
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
    document.querySelectorAll('[data-i18n-alt]').forEach(el => {
        const text = lookupTranslation(el.dataset.i18nAlt);
        if (text !== undefined) el.setAttribute('alt', text);
    });
    updateLanguageSelect();
    try { localStorage.setItem('carebp-lang', lang); } catch {}
    // Lets script.js re-render anything it built itself (cards, popups, status text).
    document.dispatchEvent(new CustomEvent('carebp-languagechange', { detail: { lang } }));
}
 
// Initialize the language on page load, and set up the dropdown.
(function initI18n() {
    const select = document.getElementById('lang-select');
    if (select) select.addEventListener('change', () => applyLanguage(select.value));
    let saved = null;
    try { saved = localStorage.getItem('carebp-lang'); } catch {}
    // English is already in the HTML, so only swap if the visitor previously chose another language.
    if (saved && saved !== I18N.fallback) applyLanguage(saved);
    updateLanguageSelect();
})();