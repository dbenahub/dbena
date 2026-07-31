# Prompt Pembangunan — DBENA Executive Dashboard (Laravel + Livewire)

> **Cara guna:** Salin keseluruhan kandungan di bawah tajuk "## PROMPT UNTUK CLAUDE CODE" ke dalam sesi Claude Code, di root repo kosong. Pastikan fail berikut boleh diakses dalam folder yang sama:
> `PRD.md` · `ANALISIS_KOMPONEN.md` · `Login.dc.html` · `Admin Login.dc.html` · `Executive Dashboard.dc.html` · `Admin Panel.dc.html` · `TrendChart.dc.html` · `assets/logo-dbena.png`
>
> **Versi:** 2.0 · 27 Julai 2026

---

## PROMPT UNTUK CLAUDE CODE

Anda seorang jurutera Laravel senior. Tugasan: **bina semula sepenuhnya** sistem dashboard prestasi jualan DBENA SDN BHD daripada prototaip UI statik (`.dc.html`) kepada aplikasi web produksi menggunakan **Laravel (versi stabil terkini), PHP 8.4, MySQL 8.4, Blade, Livewire 3, Tailwind CSS v4**.

### Dokumen wajib dibaca DAHULU (ikut urutan ini)

1. **`ANALISIS_KOMPONEN.md`** — inventori teknikal lengkap prototaip: 98 fungsi JS, 76 state property, 26 token tema (× dark/light), 45 ikon Phosphor, 15 kumpulan formula bisnes, senarai data seeder tepat, dan **12 fungsi "dead code"** yang logiknya wujud tetapi markupnya dibuang. **Ini rujukan paling terperinci — baca sepenuhnya sebelum menulis sebarang kod.**
2. **`PRD.md`** — spesifikasi produk: modul, matriks kebenaran role, skema DB 16 jadual, formula turunan, keperluan responsive, dan **26 item adjustment** yang WAJIB diperbetulkan.
3. **5 fail `.dc.html`** — untuk ketepatan visual pixel-level: setiap nilai `oklch()`, padding, border-radius, font-size, dan struktur `sc-if`/`sc-for`. **Jangan anggar dari ingatan — buka fail dan salin nilai sebenar.**

### 3 Keputusan Reka Bentuk yang telah disahkan pengguna

| # | Keputusan |
|---|---|
| **D1** | **Language switcher penuh BM ⇄ EN** — satu bahasa sahaja dipapar mengikut pilihan pengguna. BUKAN gaya prototaip yang papar "Log Masuk / Sign In" serentak. Semua string UI melalui `lang/ms/*.php` & `lang/en/*.php`; data dinamik guna lajur `_ms`/`_en`. |
| **D2** | **Bina SEMUA ciri "dead code"** — Kad Profil + upload avatar, Jadual Projek per servis, Modal Sambung Google Sheet, Modal Raw Data JSON, Modal Tambah PIC. Logiknya sudah wujud dalam prototaip (rujuk `ANALISIS_KOMPONEN.md` §5.12); markupnya sahaja perlu dibina semula. |
| **D3** | **Data penuh setiap bulan/tahun dari MySQL** (bukan hanya Julai seperti prototaip), dan **dropdown Period berfungsi penuh** — Mingguan (×1) / Bulanan (×4.33) / Suku Tahunan (×13) benar-benar menukar unit pengiraan carta, kad ringkasan dan label perbandingan. |

### Peraturan Am (terpakai sepanjang projek)

- **Ketepatan visual:** rujuk fail `.dc.html` untuk setiap nilai. Contoh — emas jenama ialah **`oklch(0.78 0.12 85)`**, bukan hex anggaran. Tailwind v4 menyokong `oklch()` secara asli; salin terus ke `@theme`.
- **Kebenaran di backend:** setiap kaedah Livewire yang menulis data mesti menyemak Policy/Gate sendiri. Menyorok butang di Blade **tidak memadai**. Contoh kritikal: lajur "Sasaran" tidak boleh diubah oleh role `user` walaupun request dipalsukan melalui devtools.
- **Logik bisnes dalam Service class**, bukan dalam Livewire component. Setiap formula mesti boleh diuji unit secara berasingan.
- **PHP 8.4 idiomatik:** `readonly` properties, backed enums (`enum MetricStatus: string`) untuk status/role/jenis, typed properties penuh, constructor property promotion.
- **Jangan salin kelemahan prototaip.** Semua 26 item dalam `PRD.md` §10 adalah **pembetulan wajib**, bukan spesifikasi.
- Jika ragu-ragu pada satu nombor/formula, tandakan `// TODO: sahkan dengan DBENA` (rujuk `PRD.md` §15.1 Soalan Terbuka Q1–Q6) dan **teruskan** — jangan berhenti.
- Selepas setiap fasa: jalankan checklist, buat commit berasingan (`feat:`, `fix:`, `chore:`), jangan langkau ke fasa seterusnya sebelum checklist lulus.

---

## FASA 0 — Persediaan Projek

1. Cipta projek Laravel terkini + Livewire 3 + Tailwind CSS v4 (Vite). Sahkan `composer.json` memerlukan `"php": "^8.4"`.
2. `.env.example` lengkap: DB MySQL 8.4, `MAIL_*` (SMTP untuk OTP sebenar), `APP_LOCALE=ms`, `APP_FALLBACK_LOCALE=en`, `QUEUE_CONNECTION=database`, `FILESYSTEM_DISK=public`.
3. `.gitignore` standard Laravel (pastikan `.env`, `vendor/`, `node_modules/`, `storage/*.key` diabaikan). `README.md` ringkas: setup lokal + rujukan deploy Forge.
4. Salin `assets/logo-dbena.png` → `public/images/logo-dbena.png`.
5. **Self-host aset** (jangan guna CDN — betulkan isu #26):
   - `npm i @phosphor-icons/web` (import hanya set **Duotone**)
   - Fon Plus Jakarta Sans (500,600,700,800) + Inter (400,500,600,700) via `@fontsource` atau fail `.woff2`
6. `resources/css/app.css` — Tailwind v4 `@theme` block dengan **semua** token dari `ANALISIS_KOMPONEN.md`:
   - §5.3: 26 CSS variable × dark/light (`--bg`, `--sidebar-bg`, `--card-bg`, `--hover-bg`, `--hover-bg2`, `--hover-bg3`, `--track-bg`, `--input-bg`, `--switch-off`, `--t96`…`--t40`, `--border`, `--border2`, `--border3`)
   - §5.4: 16 warna tetap tidak-bertema (emas, pink, status, tier, dsb.)
   - Guna nilai `oklch()` **tepat**, jangan tukar ke hex.
7. Port keyframes: `shake` (0.4s, translateX −1/2/−4/4px) & `toastIn` (0.25s ease, opacity 0→1 + translateY 12px→0). Scrollbar tersuai 8px thumb `oklch(0.35 0.02 260)`.
8. Setup mekanisme tema: `data-theme="dark|light"` pada `<html>` + CSS custom properties (port fungsi `buildThemeVars()`). **Dark mode adalah lalai.**

**✅ Checklist Fasa 0:** `php artisan serve` bersih · `npm run dev` compile tanpa ralat · toggle `data-theme` menukar semua warna · Git commit `chore: project scaffold`.

---

## FASA 1 — Skema Pangkalan Data & Model

Bina migrasi mengikut **`PRD.md` §7** (16 jadual). Untuk SETIAP jadual: migration + Eloquent Model + Factory.

```
users                      otps                       services
index_tiers                owners                     critical_metrics
critical_metric_targets    critical_weekly_entries    critical_metric_months
year_growth_factors        projects                   priorities
notifications (bawaan)     admin_settings             audit_logs
sheet_integrations
```

**Perincian penting:**

- `users`: `username` unik, `role` enum('admin','user'), `phone`, `position`, `avatar_path`, `locale` enum('ms','en') default 'ms', `theme` enum('dark','light') default 'dark', `notif_email` default true, `notif_weekly` default true, `notif_sound` default false, `last_login_at`, `is_active` default true
- `otps`: `code_hash` (bukan plaintext!), `type` enum('login','reset'), `expires_at`, `consumed_at`, `attempts` default 0, `ip_address`
- `owners`: `is_core` bool (4 PIC teras tidak boleh dibuang), **`is_system` bool** (untuk `INFO` — muncul dalam dropdown tetapi dikecualikan dari penilaian prestasi PIC, betulkan isu #21), `status` enum('active','pending_approval','rejected'), `color_token` (satu sumber kebenaran warna — betulkan isu #12)
- `critical_metric_targets`: perlu `monthly_target` dec(14,2) **nullable** DAN `target_text` — kerana prototaip ada target bukan-angka `'Progress'` (soalan terbuka Q6)
- `critical_weekly_entries`: `unique(critical_metric_id, year, month, week_number)`
- `critical_metric_months`: simpan `owner_id` + `action_plan` per metrik per bulan (bukan global) — supaya pelan tindakan Julai berbeza dari Ogos
- `audit_logs`: `old_values`/`new_values` json, `created_at` sahaja

**Relationships + accessor dwibahasa** pada setiap model berkenaan:

```php
public function getNameAttribute(): string {
    return app()->getLocale() === 'en' ? $this->name_en : $this->name_ms;
}
```

**Enums PHP 8.4** yang perlu dicipta: `UserRole`, `MetricStatus` (green/yellow/red/belum_update), `ServiceStatus` (memuaskan/perlu_dipertingkat), `ProjectStatus`, `OwnerStatus`, `OtpType`, `PeriodMode`, `ViewMode`.

**Seeder** — guna nilai TEPAT dari `ANALISIS_KOMPONEN.md` §4.1 & §5.2:

- **5 servis** dengan `key`, `icon_class`, `monthly_target`, `chart_color` (renovation `oklch(0.6 0.2 350)`, kabinet `oklch(0.75 0.15 85)`, bina-rumah `oklch(0.6 0.16 250)`, divider `oklch(0.65 0.15 145)`, mihrab `oklch(0.7 0.16 40)`)
- **5 index tier** dengan threshold sebenar (0 / 457,142.86 / 685,714.29 / 914,285.71 / 1,371,428.57)
- **4 PIC teras** (ZIKRI `oklch(0.6 0.15 250)`, HAFIZAN `oklch(0.7 0.12 85)`, NIZAM `oklch(0.6 0.16 350)`, AZHARI `oklch(0.6 0.15 145)`) + **1 system owner** `INFO`
- **49 baris metrik kritikal** — perhatikan bilangan berbeza ikut servis (renovation/kabinet/bina-rumah/mihrab = 10 baris, divider = 9). Divider **tiada** "No of Site Visit" dan guna "Cost Per Quotation (CPQ)" ganti CPA. Bina Rumah guna "No of Appointment (Offline / Online)" ganti "No of Site Visit". Rujuk jadual penuh dalam `ANALISIS_KOMPONEN.md` §5.2.
- **16 projek demo** merentasi 5 servis
- **3 keutamaan** (kabinet/Ahmad Hafiz, bina-rumah/Mohd Amirul, mihrab/Nurul Farah)
- **10 faktor pertumbuhan** 2023–2032 (0.58 … 1.9, dengan 2026 = 1.0 asas)
- **1 akaun admin + 1 akaun user demo** — **JANGAN** guna kata laluan prototaip (`••••••••`/`••••••••`). Jana kata laluan rawak kuat, papar sekali dalam output `db:seed` dan catat dalam `README.md` sebagai nota setup — **bukan dalam kod**.
- **Data mingguan demo untuk beberapa bulan** (bukan Julai sahaja) supaya D3 boleh diuji.

**Betulkan nama servis (isu #20):** prototaip ada `nameEn` terbalik — `renovation.nameEn = 'Ubah Suai'` (BM!), `divider.nameEn = 'Pembahagi'` (BM!), `mihrab.nameEn = ''` (kosong). Seed dengan betul: `name_ms`/`name_en` = Renovation/Renovation, Kabinet/Cabinetry, Bina Rumah/House Construction, Divider/Divider, Mihrab/Mihrab. Tandakan `// TODO: sahkan dengan DBENA (Q1)`.

**✅ Checklist Fasa 1:** `php artisan migrate:fresh --seed` berjaya · `Service::count() === 5` · `CriticalMetric::count() === 49` · `Owner::where('is_core',true)->count() === 4` · accessor dwibahasa pulangkan nilai betul apabila `App::setLocale('en')`.

---

## FASA 2 — Auth, Layout Shell & I18n

### 2.1 Alur log masuk

Dua Livewire component berasingan (`Auth\UserLoginFlow`, `Auth\AdminLoginFlow`) yang **berkongsi satu trait `HandlesOtpFlow`** untuk elak duplikasi (logiknya 100% identik dalam prototaip — rujuk `ANALISIS_KOMPONEN.md` §3).

State machine `step`: `login` → `otp` → `success`, dengan cabang `forgot` → `resetOtp` → `resetPassword` → `resetSuccess`.

**OTP sebenar (betulkan isu #1):**
- Jana 6-digit rawak → `Hash::make()` → simpan ke `otps`
- Hantar melalui `Notification`/`Mailable` (queued) ke emel pengguna
- **JANGAN PAPAR KOD DI UI SAMA SEKALI** — buang kotak "Demo: OTP anda ialah..."
- Sah **5 minit** · had **3 percubaan** gagal · **cooldown 60 saat** untuk "Hantar Semula"
- Sahkan dengan `Hash::check()`

**Rate limiting (isu #6):** `RateLimiter` 5 percubaan gagal / 15 minit per IP+username.

**Validasi kata laluan baharu:** **≥8 aksara** (dinaikkan dari 6 dalam prototaip), gabungan huruf+nombor disyorkan.

**Animasi shake:** cetuskan melalui Alpine `x-data` + `$wire.entangle` atau `wire:key` yang berubah pada setiap ralat, supaya CSS animation dimulakan semula.

**Visual:**
- `/login` — kad 440px tengah, tema emas, kotak logo (bg putih `oklch(0.97 0.005 260)`, border emas). Port setiap nilai dari `Login.dc.html`.
- `/admin/login` — split-panel: kiri 46% tema merah amaran (badge "RESTRICTED ACCESS", `ph-shield-warning` 32px dalam kotak 64×64, tajuk 34px/800, perenggan dwibahasa, 2 chip ciri), kanan borang 400px. Port dari `Admin Login.dc.html`.
- **Mobile:** panel kiri Admin Login jadi **header ringkas di atas borang** (bukan 46% tetap, dan **JANGAN sorok terus** — badge "RESTRICTED ACCESS" mesti kekal kelihatan untuk konteks keselamatan).

**Logout sebenar (isu #7):** `Auth::logout()` + `session()->invalidate()` + `session()->regenerateToken()`. Bukan toast.

### 2.2 Middleware & Routes

```php
// Guest
Route::middleware('guest')->group(function () {
    Route::get('/login', UserLoginFlow::class)->name('login');
    Route::get('/admin/login', AdminLoginFlow::class)->name('admin.login');
});

// Auth (kedua role)
Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('/', Overview::class)->name('dashboard');
    Route::get('/servis/{key}', ServiceDetail::class)->name('service.detail');
    Route::get('/laporan', Laporan::class)->name('laporan');
    Route::get('/laporan/eksport', [ReportExportController::class, 'export'])->name('laporan.export');
    Route::get('/tetapan', Tetapan::class)->name('tetapan');
});

// Admin sahaja
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', ConfigPanel::class)->name('admin.panel');
});
```

Middleware `role` custom (`EnsureUserHasRole`) — semak `users.role`, kembalikan 403.

### 2.3 Layout Shell responsif

`resources/views/components/layouts/app.blade.php`:

**Sidebar (264px, sticky, `overflow-y:auto`):** kotak logo · 8 item menu dengan ikon Phosphor (`ph-house`, `ph-wrench`, `ph-squares-four`, `ph-house-line`, `ph-columns`, `ph-bank`, `ph-chart-bar`, `ph-gear`) · item aktif: bg `var(--hover-bg2)` + `border-left:3px solid oklch(0.78 0.12 85)` + ikon/teks emas · footer toggle Dark Mode (`ph-moon` + switch 38×22).

**Topbar (76px, sticky, z-20):** tajuk 22px/800 emas `letter-spacing:1px` + subtajuk · dropdown Tahun (2023-2032, `max-height:320px` skrol) · **dropdown Bulan** (baharu — prototaip kira `dateMonthOptions` tetapi tak papar) · dropdown Period · butang notifikasi 38×38 dengan badge merah 8×8 · dropdown profil (avatar 36×36 border emas, nama sebenar pengguna) · **language switcher BM ⇄ EN**.

**Mobile (`<lg`):** sidebar → off-canvas drawer (Alpine `x-show` + `x-transition`, hamburger di topbar). Topbar padat: tajuk + hamburger + notifikasi; kawalan lain ke bottom-sheet. Semua sasaran sentuh **≥44×44px**.

### 2.4 I18n (D1)

1. Fail bahasa `lang/ms/` & `lang/en/`: `app.php`, `auth.php`, `dashboard.php`, `service.php`, `laporan.php`, `tetapan.php`, `admin.php`, `validation.php`.
2. **Ekstrak SETIAP string** dari 5 fail `.dc.html`. Prototaip tulis "Log Masuk / Sign In" — pecahkan jadi `ms: 'Log Masuk'` dan `en: 'Sign In'`. Ini termasuk semua label kad, header jadual, placeholder input, mesej ralat, mesej toast, nota kaki, dan legend.
3. Middleware `SetLocale` — `App::setLocale(auth()->user()?->locale ?? session('locale', 'ms'))`.
4. Livewire component `LanguageSwitcher` — kemas kini `users.locale` (jika log masuk) atau `session('locale')` (guest), tanpa reload penuh.
5. Nama bulan dari fail bahasa: BM `Jan/Feb/Mac/Apr/Mei/Jun/Jul/Ogo/Sep/Okt/Nov/Dis` · EN `Jan/Feb/Mar/Apr/May/Jun/Jul/Aug/Sep/Oct/Nov/Dec`. Nama penuh BM `Januari…Disember` · EN `January…December`.
6. Format RM: `'RM' . number_format($n)` (tiada desimal, seperti `fmtRM()` asal yang guna `Math.round`).

### 2.5 Tema

Toggle dark/light → persist ke `users.theme`. Port nilai `buildThemeVars()` dari `Executive Dashboard.dc.html` (baris ~1014-1028) terus ke CSS. **Betulkan isu #23:** `--t55` bernilai sama pada dark & light — laraskan nilai tema terang dan sahkan kontras WCAG AA.

**✅ Checklist Fasa 2:** Log masuk sebagai admin & user dengan akaun seed · terima emel OTP sebenar (semak `storage/logs/laravel.log` jika driver `log` semasa dev) · OTP TIDAK dipapar di UI · user cuba buka `/admin` → 403 · sidebar+topbar berfungsi pada 375px/768px/1440px · tukar bahasa → semua string skrin log masuk & shell bertukar · toggle tema persist selepas reload.

---

## FASA 3 — Dashboard Utama & Detail Servis

### 3.1 Service class (WAJIB dahulu — sebelum Livewire component)

`app/Services/DashboardMetricsService.php` — mengandungi **SEMUA** formula dari `PRD.md` §7.1, setiap satu kaedah tersendiri yang boleh diuji unit. **JANGAN tulis formula terus dalam Livewire component.**

```php
calculateEffectiveSales(Service $s, int $year, int $month): float
calculateYearFactor(int $year): float
calculateTierIndex(float $monthActual, ViewMode $mode): IndexTier
calculateTierWidthPct(int $index): float            // 100 − i×16
calculateServiceStatus(float $pct): ServiceStatus   // ambang 35%
calculateMetricStatus(?float $actual, ?float $target, ?string $plan, bool $hasData): MetricStatus
calculateWeeklyTarget(float $monthlyTarget, string $type): float  // ceil() utk count, round() utk amount
getMonthWeeks(int $month, int $year): array         // penanda HARI KHAMIS
getCriticalWeekLabels(int $month, int $year): array // 'Minggu {n}\n{DD}/{MM}-{DD}/{MM}'
calculateEstimatedProfit(float $monthActual): float // margin 18%
calculateRequiredRunRate(float $gap): float         // 12 − now()->month, BUKAN hardcode 5
calculateConversionRate(int $year, int $month, ?int $serviceId): float  // BUKAN hardcode 8.2%
calculateAvgDealValue(float $revenue, int $projectCount): float
applyPeriodMultiplier(float $value, PeriodMode $mode): float  // D3: ×1 / ×4.33 / ×13
calculateOwnerScore(Collection $metrics): array     // greenCount/total, kecualikan is_system
buildMonthlyChart(array $actuals, float $target): array
buildYearlyChart(float $baseSales, float $baseTarget): array
buildStackedBars(Collection $services, int $year): array
```

**Butiran kritikal `getMonthWeeks()`** — prototaip guna **hari Khamis** sebagai penanda akhir minggu, BUKAN minggu kalendar Isnin–Ahad:

```
thursdays = semua hari Khamis dalam bulan
w1end = thursdays[1] ?? akhirBulan     // Khamis KEDUA
w2end = thursdays[2] ?? akhirBulan
w3end = thursdays[3] ?? akhirBulan
→ [[1,w1end],[w1end+1,w2end],[w2end+1,w3end],[w3end+1,akhirBulan]]
```

**Pengiraan koordinat carta** (untuk `<x-trend-chart>`, viewBox 1180×380):
```
bar.pctHeight = min(100, value/max × 100) . '%'
dot.x         = i / (n−1) × 1160 + 20
dot.y         = (1 − value/max) × 340 + 20
linePoints    = dots.map(d => "{x},{y}").join(' ')
```

### 3.2 `Dashboard\Overview` (`/dashboard`)

Port seksyen `isDashboard` dari `Executive Dashboard.dc.html` (baris 151-402). Semua 7 blok dalam `PRD.md` §6.3:

1. Kad Prestasi Keseluruhan (badge tier, toggle Bulanan/Tahunan, butang bulan, donut 210px `conic-gradient`)
2. 3 kad ringkasan (Kutipan, Sebut Harga, Lead Baharu)
3. Jadual Prestasi Mengikut Servis
4. Index Sasaran Jualan (piramid 5 tier + jadual 7 lajur)
5. Trend Jualan & Sasaran (`<x-trend-chart>` + carta bar bertindan)
6. Keutamaan Minggu Ini
7. Trend Jualan Tahunan

**Notifikasi dinamik** — kira baris berstatus Red per servis; jika >0 → notifikasi "{servis}: {n} metrik belum capai sasaran (tiada action plan)" dengan `ph-warning-circle`, klik → halaman servis. Tambah notifikasi status Google Sheet jika disambung. Badge merah muncul jika ada notis belum dibaca. **Buang array `this.notifications` statik** — ia tidak pernah digunakan dalam prototaip.

### 3.3 `Dashboard\ServiceDetail` (`/dashboard/servis/{key}`)

Port seksyen `isService` (baris 405-694). Semua 11 blok dalam `PRD.md` §6.4.

**Jadual Data Kritikal (11 lajur)** — grid `250px 95px 95px 95px 95px 80px 115px 140px 90px 110px 1fr`, `min-width:1360px`:
- `wire:model.blur` pada input minggu 1-4 (elak round-trip setiap ketukan)
- Auto-kira lajur "Actual" = Σ minggu 1-4; format RM jika metrik jenis currency
- **Lajur "Sasaran": guard backend WAJIB** —
  ```php
  public function updateTarget(int $metricId, string $value): void {
      $this->authorize('updateTarget', CriticalMetric::class);  // Policy: admin sahaja
      // ...
  }
  ```
  Menyorok input di Blade **TIDAK MEMADAI**. Uji dengan feature test yang memanggil kaedah terus sebagai user.
- Dropdown PIC dengan warna dari `owners.color_token` (satu sumber — isu #12)
- Input pelan tindakan (`wire:model.blur`)
- Chip penapis PIC · legend status 3 item

**Ciri D2 yang perlu dibina:**

| Modal / Blok | Butiran |
|---|---|
| **Modal Google Sheet** | Input URL · butang Sambung / Segerak Sekarang / Putus · papar `last_synced_at`. Admin sahaja untuk sambung/putus; user boleh buka pautan sahaja. Fasa 1 = penyimpanan URL + status (tiada API sebenar). |
| **Modal Raw Data** | Property Livewire **`$showRawDataModal` berasingan** (JANGAN sarangkan dalam modal lain seperti prototaip — isu #19). Papar JSON struktur Data Kritikal: `{metric, type, week1-4, monthlyActual, monthlyTarget, status, owner}`, `<pre>` font mono, `max-height:80vh`. |
| **Modal Tambah PIC** | Input nama (auto-uppercase) · validasi kosong & duplikasi · **User** → cipta `owners` status `pending_approval`; **Admin** → terus `active`. Toast pengesahan. |
| **Jadual Projek** | Nama · Klien · Nilai RM · Status berwarna (Selesai hijau `oklch(0.72 0.15 145)` / Dalam Proses oren `oklch(0.75 0.14 70)` / Menunggu Kelulusan biru `oklch(0.65 0.15 250)` / Perancangan kelabu) · Tarikh. Admin: tambah/edit/padam. |

### 3.4 Blade components

`<x-trend-chart>` — port `TrendChart.dc.html` **tepat**: legend 2 item · paksi-Y 5 label (max, ×0.75, ×0.5, ×0.25, 0) · SVG `viewBox="0 0 1180 380"` `preserveAspectRatio="none"` `width:100%` · `<polyline stroke="oklch(0.78 0.12 85)" stroke-width="2.5">` · `<circle r="4.5" fill="var(--input-bg)" stroke-width="2">` · bar `width:60%` `border-radius:4px 4px 0 0` `background:oklch(0.6 0.22 350)` dengan `title` tooltip.

Bina juga komponen dikongsi yang disenaraikan dalam `PRD.md` §5.

### 3.5 Responsive dual-mode

**Setiap jadual** mesti ada 2 mod:
```blade
<div class="hidden md:block">  {{-- grid/table desktop --}}  </div>
<div class="md:hidden">        {{-- senarai kad mobile   --}}  </div>
```
Kecuali Data Kritikal (11 lajur) dan Index Tier (7 lajur) yang guna `overflow-x-auto` dengan **sticky first column** — rujuk `PRD.md` §8.2.

**✅ Checklist Fasa 3:** Dashboard papar data sebenar dari DB · buka kesemua 5 halaman Detail Servis tanpa ralat · edit satu nilai minggu sebagai user → reload → nilai kekal · **cuba edit Sasaran sebagai user melalui network tab → mesti ditolak 403** · tukar bulan → data bulan lain muncul (D3) · tukar Period → nilai carta & kad benar-benar berubah (D3) · kesemua 3 modal + Jadual Projek berfungsi (D2) · unit test semua kaedah `DashboardMetricsService` lulus.

---

## FASA 4 — Laporan & Tetapan

### 4.1 `Dashboard\Laporan`

- Penapis tarikh (12 bulan) + penapis servis (Semua + 5)
- 4 kad KPI — gunakan `calculateConversionRate()` **sebenar** (isu #15) dan jumlah quotation **sebenar** (isu #16), bukan `revenue × 3.83`
- Carta Trend Keseluruhan (bertukar mengikut penapis)
- Jadual Pecahan Mengikut Servis (6 lajur, dual-mode responsive)
- Butang **Eksport** → `Response::streamDownload()` jana CSV sebenar (isu #9). Sertakan header lajur mengikut locale semasa.

### 4.2 `Dashboard\Tetapan`

- Kad Keutamaan Sistem — 3 toggle (Notifikasi Emel, Laporan Mingguan, Bunyi Amaran)
- **Kad Profil (D2)** — nama, jawatan, emel, telefon · mod Edit/Simpan (ikon `ph-pencil-simple` ⇄ `ph-check`) · **upload avatar sebenar** `WithFileUploads` → `storage/app/public/avatars`, validasi `image|mimes:jpg,png,webp|max:2048` (isu #22)
- Tukar kata laluan — `current_password` + baharu (≥8) + `confirmed`
- Pilihan bahasa (BM/EN) + tema (Dark/Light)
- Butang **Simpan Tetapan** → **persist sebenar ke `users`** (isu #8), bukan toast palsu
- Pautan **Admin Panel** hanya jika `auth()->user()->role === UserRole::Admin`

### 4.3 Laporan Mingguan automatik

Job + Laravel Scheduler — hantar emel ringkasan kepada pengguna yang `notif_weekly = true`. Daftarkan cron di Forge (Fasa 6).

**✅ Checklist Fasa 4:** Eksport CSV buka betul di Excel dengan aksara BM tidak rosak (UTF-8 BOM) · profil + avatar kekal selepas logout-login · tukar kata laluan berfungsi · toggle bahasa dari halaman Tetapan menukar seluruh UI.

---

## FASA 5 — Admin Panel

`Admin\ConfigPanel` (`/admin`, middleware `role:admin`) — port `Admin Panel.dc.html` penuh + tambahan:

1. **Servis & Sasaran** — 5 baris editable (nama BM, nama EN, sasaran bulanan RM)
2. **Index Tier Threshold** — 5 baris editable (Revenue Bulanan, Untung Bersih Bulanan)
3. **Pemilik Data (PIC)** — chip berwarna + tambah/buang.
   **Guard backend (isu #10):** `removeOwner()` mesti **TOLAK** jika PIC masih ada rekod `critical_weekly_entries` / `critical_metric_months` aktif → mesej ralat jelas, bukan buang senyap. PIC `is_core` dan `is_system` tidak boleh dibuang langsung.
4. **PIC Menunggu Kelulusan** (baharu, D2) — senarai `status = pending_approval` dengan butang Lulus / Tolak
5. **Faktor Pertumbuhan Tahunan** — grid 2023-2032 (boleh tambah tahun baharu)
6. **Google Sheet URL lalai**
7. **Urus Pengguna** (baharu) — senarai akaun, cipta, nyahaktif, tetapkan role
8. **Log Audit** (baharu) — jadual: masa · pengguna · tindakan · subjek · nilai lama → baharu
9. Butang **Simpan Semua** — semua update dalam **satu `DB::transaction()`**; bagi SETIAP nilai yang **benar-benar berubah**, rekod satu baris `audit_logs` (jangan rekod jika sama) (isu #25)

**Warna PIC (isu #12):** ambil dari `owners.color_token` sahaja. Jangan jana semula ikut index array seperti prototaip — pastikan PIC yang sama berwarna sama di Dashboard dan Admin Panel.

**✅ Checklist Fasa 5:** Admin ubah sasaran servis → Simpan → buka Dashboard tab lain → nilai baharu terpapar · `audit_logs` menunjukkan nilai lama & baharu betul · cuba buang PIC yang ada data aktif → ditolak dengan mesej jelas · PIC dicadang oleh user muncul dalam senarai kelulusan · lulus PIC → muncul dalam dropdown Data Kritikal · Admin Panel 1 lajur pada mobile.

---

## FASA 6 — QA, Testing & Deployment

### 6.1 Responsive QA

Uji **kesemua 8 halaman** pada 375px · 390px · 768px · 1024px · 1440px. Betulkan sebarang overflow/pertindihan. Semak khusus:
- Jadual Data Kritikal (11 lajur) — sticky first column berfungsi
- Butang bulan Jan-Dis — snap scroll, sasaran ≥44px
- Donut & carta — tidak pecah pada 375px
- Modal — full-screen/bottom-sheet pada mobile
- Admin Login — panel kiri jadi header, "RESTRICTED ACCESS" kekal kelihatan

### 6.2 Testing

**Unit test (Pest)** — SETIAP kaedah `DashboardMetricsService`:
- `getMonthWeeks()` untuk bulan dengan 4 vs 5 hari Khamis
- `calculateTierIndex()` pada setiap sempadan threshold (0, 457142.86, 685714.29, 914285.71, 1371428.57) dan mod bulanan vs tahunan (×12)
- `calculateMetricStatus()` untuk 4 kombinasi (tiada data / ≥100% / ada plan / tiada plan)
- `calculateServiceStatus()` pada 34.9% dan 35.0%
- `calculateEstimatedProfit()` margin 18%
- `calculateWeeklyTarget()` — `ceil()` untuk count, `round()` untuk amount
- `applyPeriodMultiplier()` — 1 / 4.33 / 13
- `calculateRequiredRunRate()` — dinamik ikut bulan semasa

**Feature test:**
- Log masuk user & admin + OTP (kod salah, kod luput, 3 percubaan, cooldown resend)
- Rate limiting 5 percubaan
- `role:admin` menyekat user dari `/admin`
- **`updateTarget()` ditolak untuk role user** (panggil kaedah terus, bukan melalui UI)
- `removeOwner()` ditolak jika PIC ada data aktif
- Language switcher — assert semua string bertukar
- Eksport CSV — assert header & baris betul

### 6.3 Git

History bersih dengan convention (`feat:`, `fix:`, `chore:`, `test:`). Push ke repo GitHub baharu:
```bash
git remote add origin git@github.com:<org>/dbena-dashboard.git
git push -u origin main
```
`README.md` lengkap: `composer install` · `npm install && npm run build` · setup `.env` · `php artisan key:generate` · `php artisan migrate --seed` · `php artisan storage:link` · `php artisan serve` · nota kata laluan akaun demo.

### 6.4 Deploy ke Laravel Forge

1. Provisi server: **PHP 8.4**, **MySQL 8.4**
2. Sambung repo GitHub ke site (auto-deploy on push ke `main`, atau manual — ikut keperluan DBENA)
3. Deploy Script:
   ```bash
   cd /home/forge/{site}
   git pull origin $FORGE_SITE_BRANCH
   $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
   ( flock -w 10 9 || exit 1
     echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
   npm ci && npm run build
   $FORGE_PHP artisan migrate --force
   $FORGE_PHP artisan storage:link
   $FORGE_PHP artisan config:cache
   $FORGE_PHP artisan route:cache
   $FORGE_PHP artisan view:cache
   $FORGE_PHP artisan queue:restart
   ```
4. `.env` produksi di Forge (**jangan commit `.env` sebenar** — sahkan `.gitignore`), termasuk kredensial SMTP sebenar untuk OTP
5. **Queue Worker** di Forge (`QUEUE_CONNECTION=database` atau `redis`) — untuk emel OTP & notifikasi
6. **Scheduler** — cron `* * * * * php artisan schedule:run` (Laporan Mingguan automatik)
7. **SSL** Let's Encrypt + domain
8. **Backup DB automatik** — Forge scheduled backup atau `spatie/laravel-backup` + S3/Backblaze

**✅ Checklist Fasa 6 (Definition of Done):** Semua item `PRD.md` §14 Kriteria Penerimaan disahkan ✅ · site live dengan SSL · push ke `main` trigger deploy · log masuk + OTP emel berfungsi di produksi sebenar (bukan driver `log`) · Lighthouse mobile ≥85 pada Dashboard.

---

## Rujukan Pantas — Nilai Kritikal

| Perkara | Nilai |
|---|---|
| Emas jenama | `oklch(0.78 0.12 85)` |
| Pink carta | `oklch(0.6 0.22 350)` |
| Latar gelap / terang | `oklch(0.15 0.025 260)` / `oklch(0.97 0.008 260)` |
| Kad gelap / terang | `oklch(0.19 0.025 260)` / `oklch(1 0 0)` |
| Status Green / Yellow / Red / Belum Update | `oklch(0.55 0.15 145)` / `oklch(0.78 0.15 85)` / `oklch(0.55 0.2 25)` / `oklch(0.6 0.02 260)` |
| Merah Admin Login | `oklch(0.6 0.2 25)` · `oklch(0.55 0.19 25)` · `oklch(0.68 0.19 25)` |
| Ambang status servis | **35%** |
| Margin untung anggaran | **18%** |
| Lebar tier piramid | `100 − i × 16` % |
| Toast auto-hilang | **2600ms** |
| Animasi shake | **0.4s** |
| Sidebar / Topbar | **264px** / **76px** |
| TrendChart viewBox | **1180 × 380** |
| Data Kritikal min-width | **1360px** (11 lajur) |
| Index Tier min-width | **760px** (7 lajur) |
| Penanda minggu | **Hari Khamis** (bukan Isnin–Ahad) |
| OTP | 6 digit · sah 5 minit · 3 cubaan · cooldown 60s |
| Rate limit log masuk | 5 cubaan / 15 minit |
| Kata laluan minimum | **8 aksara** |

---

## Pantang Larang (jangan buat ini)

❌ Papar OTP di UI
❌ Kata laluan hardcoded dalam kod atau seeder
❌ Simpan `code` OTP tanpa hash
❌ Bergantung pada UI yang disorok sebagai kawalan kebenaran
❌ Tulis formula bisnes terus dalam Livewire component
❌ Tukar nilai `oklch()` ke hex "supaya lebih mudah"
❌ Guna CDN untuk fon/ikon/avatar
❌ Hardcode `monthsLeft = 5`, `conversionRate = 8.2%`, atau `quotation = revenue × 3.83`
❌ Salin `min-width:1440px` — sistem mesti mobile-first
❌ Sarangkan Modal Raw Data dalam modal lain
❌ Jana warna PIC secara berasingan di setiap skrin
❌ Anggap semua servis ada bilangan metrik yang sama (divider ada 9, bukan 10)
❌ Commit `.env` sebenar
