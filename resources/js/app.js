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

/**
 * Kembangkan textarea mengikut kandungannya.
 *
 * Pelan tindakan boleh menjadi beberapa ayat. Ketinggian tetap bermakna
 * pengguna menulis ke dalam tingkap sempit dan tidak dapat membaca semula
 * apa yang mereka tulis — teks lama hilang ke atas tanpa sebarang tanda.
 *
 * Had atas dikawal oleh max-height dalam CSS; selepas itu ia menatal.
 */
window.dbenaAutoGrow = (el) => {
    if (! el) return;
    el.style.height = 'auto';
    el.style.height = `${el.scrollHeight}px`;
};

/**
 * Livewire menggantikan nod DOM apabila data berubah — contohnya selepas
 * sync atau tukar bulan. Ketinggian yang dikira sebelum itu tidak lagi
 * sepadan dengan kandungan baharu, jadi kira semula.
 */
document.addEventListener('livewire:navigated', () => {
    document.querySelectorAll('textarea[data-autogrow]').forEach(window.dbenaAutoGrow);
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', ({ el }) => {
        if (el.matches?.('textarea[data-autogrow]')) window.dbenaAutoGrow(el);
        el.querySelectorAll?.('textarea[data-autogrow]').forEach(window.dbenaAutoGrow);
    });
});
