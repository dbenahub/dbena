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
    kumpulan: [],
    mulaX: 0,
    mulaY: 0,
    bergerak: false,

    /**
     * Kotak mana yang akan bergerak.
     *
     * Menekan kotak yang SUDAH dipilih menyeret seluruh pilihan. Menekan
     * kotak yang tidak dipilih menyeret kotak itu sahaja — kalau tidak,
     * satu klik tersasar akan mengalihkan setiap kotak dalam carta dan
     * tiada butang buat asal untuk mengembalikannya.
     *
     * Dibaca daripada DOM dan bukan daripada keadaan Alpine, kerana
     * Livewire memaparkan semula kanvas selepas setiap pilihan dan
     * keadaan Alpine yang disalin semasa muat pertama akan menjadi lapuk.
     */
    kumpul(el) {
        if (el.dataset.selected === undefined) return [el];

        return Array.from(document.querySelectorAll('[data-node][data-selected]'));
    },

    mula(event) {
        if (event.button !== undefined && event.button !== 0) return;

        const el = event.currentTarget;

        this.mulaX = event.clientX;
        this.mulaY = event.clientY;
        this.bergerak = false;

        this.kumpulan = this.kumpul(el).map((node) => ({
            el: node,
            id: Number(node.dataset.node),
            x: parseInt(node.style.left, 10) || 0,
            y: parseInt(node.style.top, 10) || 0,
        }));

        // Tangkap penuding supaya seretan bertahan walaupun kursor bergerak
        // lebih laju daripada pengecatan dan meninggalkan kotak.
        try { el.setPointerCapture(event.pointerId); } catch (e) { /* noop */ }
    },

    /**
     * Had dikenakan pada ANJAKAN, bukan pada setiap kotak.
     *
     * Mengapit setiap kotak secara berasingan pada sifar akan meruntuhkan
     * susunan: kotak di tepi kiri berhenti sementara yang lain terus
     * bergerak, dan carta yang disusun dengan teliti menjadi longgokan.
     * Menghentikan SELURUH kumpulan apabila ahli pertama mencecah tepi
     * mengekalkan setiap jarak antara kotak.
     */
    anjakan(event) {
        let dx = event.clientX - this.mulaX;
        let dy = event.clientY - this.mulaY;

        const minX = Math.min(...this.kumpulan.map((n) => n.x));
        const minY = Math.min(...this.kumpulan.map((n) => n.y));

        if (minX + dx < 0) dx = -minX;
        if (minY + dy < 0) dy = -minY;

        return { dx, dy };
    },

    gerak(event) {
        if (this.kumpulan.length === 0) return;

        const { dx, dy } = this.anjakan(event);

        if (Math.abs(dx) > 2 || Math.abs(dy) > 2) this.bergerak = true;

        this.kumpulan.forEach((n) => {
            n.el.style.left = `${n.x + dx}px`;
            n.el.style.top = `${n.y + dy}px`;
        });
    },

    lepas(event) {
        if (this.kumpulan.length === 0) return;

        const { dx, dy } = this.anjakan(event);
        const kumpulan = this.kumpulan;
        const bergerak = this.bergerak;

        this.kumpulan = [];
        this.bergerak = false;

        // Klik tanpa gerakan ialah PILIHAN, bukan seretan. Menghantarnya
        // sebagai gerakan bermakna setiap klik menulis ke pangkalan data
        // dan log audit dipenuhi pergerakan sifar piksel.
        if (! bergerak) {
            window.Livewire.dispatch('org-node-clicked', {
                id: kumpulan[0].id,
                // Ctrl atau Shift menambah kepada pilihan dan bukan
                // menggantikannya — itu isyarat yang sama seperti pengurus
                // fail, jadi tiada apa yang perlu dipelajari.
                additive: event.ctrlKey || event.metaKey || event.shiftKey,
            });

            return;
        }

        window.Livewire.dispatch('org-nodes-moved', {
            moves: kumpulan.map((n) => ({
                id: n.id,
                x: Math.round(n.x + dx),
                y: Math.round(n.y + dy),
            })),
        });
    },
});
