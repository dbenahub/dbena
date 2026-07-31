import './bootstrap';

/**
 * Tema DBENA — disegerakkan dengan users.theme di pelayan.
 * Nilai dipakai pada <html data-theme> sebelum paint pertama (lihat layout).
 */
window.dbenaSetTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    try { localStorage.setItem('dbena_theme', theme); } catch (e) { /* noop */ }
};

document.addEventListener('livewire:init', () => {
    Livewire.on('theme-changed', ({ theme }) => window.dbenaSetTheme(theme));
});
