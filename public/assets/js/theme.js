const picker = document.querySelector('.theme-picker');
const select = picker?.querySelector('select');
const root = document.documentElement;
const system = matchMedia('(prefers-color-scheme: dark)');

function applyTheme(preference) {
    root.dataset.themePreference = preference;
    root.dataset.theme = preference === 'system' ? (system.matches ? 'dark' : 'light') : preference;
    if (select) select.value = preference;
}

if (select) {
    applyTheme(root.dataset.themePreference || 'system');
    picker.hidden = false;
    select.addEventListener('change', () => {
        applyTheme(select.value);
        try { localStorage.setItem('pj-theme', select.value); } catch { /* Keep the selection for this page. */ }
    });
    system.addEventListener('change', () => {
        if (root.dataset.themePreference === 'system') applyTheme('system');
    });
    window.addEventListener('storage', (event) => {
        if (event.key === 'pj-theme' || event.key === null) {
            applyTheme(['light', 'dark'].includes(event.newValue) ? event.newValue : 'system');
        }
    });
}
