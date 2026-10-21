const toggle = document.querySelector('.theme-toggle');
const root = document.documentElement;
const system = matchMedia('(prefers-color-scheme: dark)');

function applyTheme(preference) {
    root.dataset.themePreference = preference;
    root.dataset.theme = preference === 'system' ? (system.matches ? 'dark' : 'light') : preference;
    if (toggle) {
        const isDark = root.dataset.theme === 'dark';
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute('aria-label', isDark
            ? 'Tema escuro. Alternar para tema claro.'
            : 'Tema claro. Alternar para tema escuro.');
    }
}

if (toggle) {
    applyTheme(root.dataset.themePreference || 'system');
    toggle.hidden = false;
    toggle.addEventListener('click', () => {
        const preference = root.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(preference);
        try { localStorage.setItem('pj-theme', preference); } catch { /* Keep the selection for this page. */ }
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
