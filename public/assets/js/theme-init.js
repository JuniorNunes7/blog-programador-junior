(() => {
    let preference = 'system';
    try {
        const saved = localStorage.getItem('pj-theme');
        if (['light', 'dark', 'system'].includes(saved)) preference = saved;
    } catch { /* Reading stays available when storage is blocked. */ }
    const system = matchMedia('(prefers-color-scheme: dark)');
    document.documentElement.dataset.theme = preference === 'system'
        ? (system.matches ? 'dark' : 'light') : preference;
    document.documentElement.dataset.themePreference = preference;
})();
