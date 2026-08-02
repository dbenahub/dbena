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

/**
 * Kepadatan paparan — 'selesa' atau 'padat'.
 *
 * Disimpan dalam localStorage dan bukan pada pengguna, kerana ia pilihan
 * PERANTI: orang yang sama mahu lapang di desktop dan padat di telefon.
 * Menyimpannya di pelayan akan memaksa satu pilihan pada kedua-duanya.
 */
window.dbenaSetDensity = (density) => {
    document.documentElement.dataset.density = density;
    try { localStorage.setItem('dbena_density', density); } catch (e) { /* noop */ }
};

/**
 * Seret kotak dalam Carta Organisasi.
 *
 * Alpine memegang kedudukan SEMENTARA seretan supaya kotak bergerak pada
 * kadar 60fps tanpa satu pun panggilan pelayan. Livewire hanya diberitahu
 * apabila jari atau tetikus dilepaskan.
 *
 * Menghantar setiap piksel ke Livewire akan menjadikan seretan tersekat-
 * sekat dan membanjiri pelayan dengan ratusan penulisan untuk satu
 * pergerakan — dan kotak akan ketinggalan di belakang kursor sehingga
 * seretan terasa rosak.
 */
window.cartaOrganisasi = () => ({
    id: null,
    mulaX: 0,
    mulaY: 0,
    asalX: 0,
    asalY: 0,
    el: null,

    mula(event, id, x, y) {
        // Butang kanan dan klik tengah bukan seretan.
        if (event.button !== undefined && event.button !== 0) return;

        this.id = id;
        this.el = event.currentTarget;
        this.mulaX = event.clientX;
        this.mulaY = event.clientY;
        this.asalX = x;
        this.asalY = y;

        // Tangkap penuding supaya seretan bertahan walaupun kursor
        // bergerak lebih laju daripada pengecatan dan meninggalkan kotak.
        try { this.el.setPointerCapture(event.pointerId); } catch (e) { /* noop */ }
    },

    gerak(event) {
        if (this.id === null || ! this.el) return;

        // Kanvas tidak boleh mempunyai koordinat negatif: kotak yang
        // diseret melepasi tepi kiri atau atas menjadi tidak boleh dicapai
        // dan kelihatan seolah-olah ia telah dipadam.
        const x = Math.max(0, this.asalX + (event.clientX - this.mulaX));
        const y = Math.max(0, this.asalY + (event.clientY - this.mulaY));

        this.el.style.left = `${x}px`;
        this.el.style.top = `${y}px`;
    },

    lepas(event) {
        if (this.id === null || ! this.el) return;

        const x = Math.max(0, this.asalX + (event.clientX - this.mulaX));
        const y = Math.max(0, this.asalY + (event.clientY - this.mulaY));

        const id = this.id;
        this.id = null;
        this.el = null;

        // Klik tanpa gerakan ialah PILIHAN, bukan seretan. Menghantarnya
        // sebagai gerakan bermakna setiap klik menulis ke pangkalan data
        // dan log audit dipenuhi pergerakan sifar piksel.
        if (Math.abs(x - this.asalX) < 3 && Math.abs(y - this.asalY) < 3) {
            window.Livewire.dispatch('org-node-clicked', { id });

            return;
        }

        window.Livewire.dispatch('org-node-moved', { id, x: Math.round(x), y: Math.round(y) });
    },
});
