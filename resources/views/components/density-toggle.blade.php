{{--
    Suis kepadatan paparan.

    Dashboard ini dibina untuk skrin lebar: jadual sebelas lajur, peta
    jalan raya 1240px, kad dengan pelapik lapang. Pada telefon semuanya
    muat, tetapi hanya sedikit yang muat pada satu skrin sekali gus —
    pengguna menatal melepasi dua tajuk sebelum sampai ke nombor pertama.

    Mod PADAT mengecilkan pelapik, jurang dan saiz teks, dan menukar peta
    perjalanan kepada senarai menegak. Kandungan yang sama, kira-kira 40%
    kurang menatal.

    Pilihan disimpan dalam localStorage dan dipakai pada <html> SEBELUM
    paint pertama, sama seperti tema — jika tidak, halaman berkelip dari
    lapang ke padat setiap kali dimuatkan.
--}}
<button type="button"
        x-data="{
            padat: document.documentElement.dataset.density === 'padat',
            tukar() {
                this.padat = ! this.padat;
                window.dbenaSetDensity(this.padat ? 'padat' : 'selesa');
            },
        }"
        x-on:click="tukar()"
        :aria-pressed="padat ? 'true' : 'false'"
        :title="padat ? '{{ __('app.density_comfortable') }}' : '{{ __('app.density_compact') }}'"
        :aria-label="padat ? '{{ __('app.density_comfortable') }}' : '{{ __('app.density_compact') }}'"
        class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] transition-colors"
        :style="padat
            ? 'background: oklch(0.78 0.12 85); color: oklch(0.15 0.02 260)'
            : 'background: var(--hover-bg3); color: var(--t60)'">
    <i class="ph-duotone text-[17px]"
       :class="padat ? 'ph-rows' : 'ph-list-dashes'" aria-hidden="true"></i>
</button>
