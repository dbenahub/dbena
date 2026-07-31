# PRD — DBENA Executive Dashboard (Laravel Rebuild)

**Produk:** Dashboard Prestasi Jualan DBENA SDN BHD
**Versi Dokumen:** 2.0
**Tarikh:** 27 Julai 2026
**Disediakan untuk:** Ahmad Nizam
**Sumber rujukan UI:** `Login.dc.html`, `Admin Login.dc.html`, `Executive Dashboard.dc.html`, `Admin Panel.dc.html`, `TrendChart.dc.html`
**Dokumen sokongan:** `ANALISIS_KOMPONEN.md` (inventori teknikal lengkap — rujukan wajib semasa pembangunan)

---

## 1. Ringkasan Eksekutif

DBENA SDN BHD mempunyai prototaip UI (`.dc.html`, dijana oleh engine editor proprietari "DC" melalui `support.js`) bagi dashboard prestasi jualan merangkumi 5 servis (Renovation, Kabinet, Bina Rumah, Divider, Mihrab). Prototaip ini **cantik dari segi reka bentuk tetapi tiada backend sebenar** — semua data adalah array JavaScript hardcoded, dan satu-satunya "penyimpanan" ialah `localStorage` pada Admin Panel sahaja.

Projek ini membina semula **100% UI/UX prototaip** sebagai aplikasi produksi menggunakan **Laravel (versi terkini) + PHP 8.4 + MySQL 8.4 + Blade + Livewire 3**, dengan:

1. Reka bentuk visual (warna, fon, ikon, susun atur, animasi) ditiru **serupa 100%** dengan prototaip asal pada breakpoint desktop.
2. **100% mobile responsive** — prototaip asal *fixed* pada `min-width:1440px` (langsung tiada sokongan mobile); dibina semula mobile-first.
3. **2 role**: Admin dan User, dengan RBAC sebenar disahkan di peringkat server.
4. **Sistem dwibahasa penuh dengan language switcher** BM ⇄ EN (bukan label bersebelahan statik seperti asal).
5. Semua data hardcoded/localStorage dipindah ke **MySQL 8.4**.
6. Sedia untuk **commit ke GitHub** dan **deploy ke Laravel Forge**.

### 1.1 Keputusan Reka Bentuk Utama (disahkan 27 Julai 2026)

| # | Keputusan | Pilihan yang disahkan |
|---|---|---|
| D1 | Mod dwibahasa | **Language switcher penuh BM ⇄ EN** — satu bahasa dipapar mengikut pilihan pengguna. Fail `lang/ms` & `lang/en` untuk UI, lajur `_ms`/`_en` untuk data. |
| D2 | Ciri "dead code" dalam prototaip | **Bina kesemuanya** — Kad Profil + upload avatar, Jadual Projek per servis, Modal Sambung Google Sheet, Modal Raw Data, Modal Tambah PIC. Logiknya sudah wujud dalam prototaip; markupnya sahaja yang dibuang. |
| D3 | Skop data & dropdown Period | **Data penuh setiap bulan/tahun dari MySQL**, dan dropdown Period (Mingguan/Bulanan/Suku Tahunan) **berfungsi penuh** — benar-benar menukar unit pengiraan carta & kad. |

---

## 2. Objektif Projek

- Menggantikan prototaip demo dengan aplikasi produksi yang selamat, boleh diselenggara dan berskala.
- Mengekalkan identiti jenama & pengalaman pengguna sedia ada (emas `oklch(0.78 0.12 85)`, kad gelap/terang, Plus Jakarta Sans + Inter, Phosphor Duotone).
- Membetulkan **26 isu** yang dikesan dalam prototaip (Seksyen 10).
- Memisahkan kebenaran Admin (konfigurasi induk) vs User (data operasi mingguan) — disahkan di backend, bukan sekadar UI.
- Menyediakan asas kod bersih untuk CI/CD GitHub → Laravel Forge.

---

## 3. Skop Projek

### 3.1 Dalam Skop

- **Auth:** Log Masuk User, Log Masuk Admin (route berasingan), OTP emel sebenar, Lupa Kata Laluan + OTP reset, Set Semula Kata Laluan.
- **Dashboard Utama** — ringkasan prestasi keseluruhan syarikat.
- **Detail Servis** ×5 — termasuk **Jadual Projek** (D2).
- **Data Kritikal Mingguan** — input actual mingguan, status Green/Yellow/Red/Belum Update, pelan tindakan, PIC.
- **Laporan** — penapis tarikh/servis, 4 KPI, carta trend, pecahan servis, eksport CSV.
- **Tetapan** — keutamaan sistem, **Kad Profil + upload avatar** (D2), tukar kata laluan, pilihan bahasa & tema.
- **Admin Panel** — konfigurasi induk penuh + audit log + kelulusan PIC.
- **Integrasi Google Sheet** — Modal Sambung/Segerak/Putus + Modal Raw Data JSON (D2; Fasa 1 = penyimpanan URL + paparan struktur, API sebenar = Fasa 2).
- **Modal Tambah PIC** oleh user (dengan alur kelulusan Admin) (D2).
- Komponen `TrendChart` sebagai Blade component SVG responsif.
- **Language switcher** BM ⇄ EN penuh (D1).
- Reka bentuk 100% responsive (mobile, tablet, desktop).
- Repo Git + panduan deploy Forge.

### 3.2 Luar Skop (Fasa 2 / perlu kelulusan berasingan)

- Integrasi Google Sheets API **dua-hala sebenar** (OAuth service account, auto-sync).
- Eksport PDF/Excel penuh (`barryvdh/laravel-dompdf`, `maatwebsite/excel`) — Fasa 1 CSV sahaja.
- Aplikasi mobile native.
- Modul kewangan/perakaunan penuh (invois, baucer).

---

## 4. Peranan Pengguna (Roles) & Kebenaran

Sistem menggunakan **2 role**: `admin` dan `user`. Log masuk melalui **route berasingan** — `/login` (user, tema emas, kad tengah) dan `/admin/login` (admin, tema merah amaran, split-panel).

### 4.1 Matriks Kebenaran

| Keupayaan | Admin | User |
|---|---|---|
| Log masuk (username/emel + kata laluan + OTP emel) | ✅ `/admin/login` | ✅ `/login` |
| Lihat Dashboard Utama, Detail Servis, Laporan | ✅ | ✅ |
| Edit Data Kritikal Mingguan (nilai minggu 1-4) | ✅ | ✅ |
| Edit Pemilik/PIC & Pelan Tindakan | ✅ | ✅ |
| Edit **Sasaran/Target** pada Data Kritikal | ✅ | ❌ read-only, label "Hanya boleh ditukar di Admin Panel" |
| Cadang PIC baharu (Modal Tambah PIC) | ✅ (terus aktif) | ⚠️ status `pending_approval`, perlu kelulusan Admin |
| Lulus/Tolak PIC menunggu kelulusan | ✅ | ❌ |
| Sambung/Segerak/Putus Google Sheet | ✅ | ❌ (boleh buka pautan sahaja) |
| Lihat Modal Raw Data | ✅ | ✅ |
| Lihat Jadual Projek | ✅ | ✅ |
| Cipta/edit/padam Projek | ✅ | ❌ |
| Konfigurasi servis (nama BM/EN, sasaran bulanan) | ✅ | ❌ |
| Konfigurasi threshold Index Tier (5 tahap) | ✅ | ❌ |
| Konfigurasi Faktor Pertumbuhan Tahunan | ✅ | ❌ |
| Konfigurasi Google Sheet URL lalai | ✅ | ❌ |
| Urus akaun pengguna lain (create/deactivate) | ✅ | ❌ |
| Edit profil sendiri (nama, jawatan, emel, telefon, avatar) | ✅ | ✅ |
| Tukar kata laluan sendiri | ✅ | ✅ |
| Tukar tema Dark/Light & bahasa | ✅ | ✅ |
| Lihat log audit | ✅ | ❌ |
| Eksport laporan | ✅ | ✅ |

> **Nota keselamatan:** Prototaip asal langsung tiada semakan role — pautan "Admin Panel" hanyalah `<a href>`. Dalam Laravel, **setiap** route mesti dilindungi middleware (`auth`, `role:admin`) di peringkat server, dan setiap kaedah Livewire yang mengubah data terlindung mesti menyemak Policy/Gate sendiri (bukan bergantung pada UI yang disorok).

### 4.2 Mekanisme Role

Lajur `role` enum (`admin`,`user`) pada `users`. Semakan kebenaran mesti melalui **Gate/Policy**, bukan `if ($user->role === 'admin')` bertaburan — supaya boleh naik taraf ke `spatie/laravel-permission` tanpa migrasi besar jika role tambahan diperlukan.

---

## 5. Peta Modul, Route & Komponen

| # | Skrin prototaip | Route Laravel | Livewire component | Role |
|---|---|---|---|---|
| 1 | `Login.dc.html` | `GET/POST /login` | `Auth\UserLoginFlow` | guest |
| 2 | `Admin Login.dc.html` | `GET/POST /admin/login` | `Auth\AdminLoginFlow` | guest |
| 3 | Dashboard | `GET /dashboard` | `Dashboard\Overview` | admin, user |
| 4 | Service Detail ×5 | `GET /dashboard/servis/{key}` | `Dashboard\ServiceDetail` | admin, user |
| 5 | Laporan | `GET /dashboard/laporan` | `Dashboard\Laporan` | admin, user |
| 6 | Tetapan | `GET /dashboard/tetapan` | `Dashboard\Tetapan` | admin, user |
| 7 | `Admin Panel.dc.html` | `GET /admin` | `Admin\ConfigPanel` | admin |
| 8 | `TrendChart.dc.html` | — | Blade `<x-trend-chart>` | semua |
| 9 | — (baharu) | `GET /dashboard/laporan/eksport` | Controller `ReportExportController` | admin, user |
| 10 | — (baharu) | `POST /locale/{locale}` | `SetLocale` / Livewire `LanguageSwitcher` | semua |

**Komponen Blade dikongsi yang perlu dibina:**

`<x-trend-chart>` · `<x-stat-card>` · `<x-donut-chart>` · `<x-progress-bar>` · `<x-status-dot>` · `<x-owner-chip>` · `<x-toggle-switch>` · `<x-toast>` · `<x-modal>` · `<x-month-selector>` · `<x-view-mode-toggle>` · `<x-dropdown>` · `<x-tier-pyramid>` · `<x-stacked-bar-chart>` · `<x-weekly-mini-chart>` · `<x-responsive-table>` (mod jadual desktop ⇄ senarai kad mobile)

---

## 6. Spesifikasi Fungsian Terperinci

> Semua nilai visual tepat (oklch, padding, font-size, ikon) terdapat dalam `ANALISIS_KOMPONEN.md`. Seksyen ini menerangkan **tingkah laku**, bukan mengulang gaya.

### 6.1 Log Masuk User (`/login`)

Alur 7 langkah dalam satu skrin (state machine `step`):

| Langkah | Kandungan | Validasi |
|---|---|---|
| `login` | Username, Kata Laluan (toggle mata `ph-eye`/`ph-eye-slash`), butang Log Masuk, pautan Lupa Kata Laluan | Kedua medan wajib; kelayakan salah → mesej ralat + animasi **shake 0.4s** pada kad |
| `otp` | Input OTP 6-digit (auto-tapis numerik, `letter-spacing:10px`), butang Sahkan, pautan Kembali & Hantar Semula | Panjang 6; had **3 percubaan** gagal → mesti minta kod baharu; **cooldown 60 saat** untuk hantar semula |
| `forgot` | Emel berdaftar | Emel wajib & wujud dalam DB |
| `resetOtp` | Kod reset 6-digit | Sama seperti OTP login |
| `resetPassword` | Kata laluan baharu + sahkan | **≥8 aksara** (dinaikkan dari 6 dalam prototaip), gabungan huruf+nombor disyorkan, mesti sepadan |
| `success` | Mesej kejayaan + auto-alih ke `/dashboard` | — |
| `resetSuccess` | Mesej + butang kembali ke log masuk | — |

**OTP:** jana 6-digit rawak, **hash sebelum simpan**, hantar melalui `Notification`/`Mailable` ke emel pengguna, sah **5 minit**. ⚠️ **JANGAN paparkan kod di UI** (betulkan isu #1).

**Toast** bawah-kanan, auto-hilang **2600ms**, animasi `toastIn 0.25s ease` — untuk setiap tindakan hantar OTP/kejayaan.

**Rate limiting:** 5 percubaan gagal / 15 minit per IP + username.

### 6.2 Log Masuk Admin (`/admin/login`)

Struktur alur **identik** dengan 6.1, tetapi:

- Split-panel: kiri **46%** panel jenama merah amaran (badge "RESTRICTED ACCESS", `ph-shield-warning`, penerangan dwibahasa, 2 chip ciri), kanan borang 400px.
- Palet merah `oklch(0.6 0.2 25)` / `oklch(0.55 0.19 25)` / `oklch(0.68 0.19 25)` — **kekalkan pembezaan visual ini** supaya pengguna sedar mereka berada di zon terhad.
- Tiada logo; tajuk rata kiri.
- Destinasi kejayaan: `/admin`.
- **Mobile:** panel kiri jadi header ringkas di atas borang (bukan 46% lebar tetap, dan **bukan disorok terus** — mesej "RESTRICTED ACCESS" mesti kekal kelihatan).

### 6.3 Dashboard Utama (`/dashboard`)

**Sidebar (sticky, 264px):** logo · 8 item menu (Dashboard Utama, Renovation, Kabinet, Bina Rumah, Divider, Mihrab, Laporan, Tetapan) dengan ikon Phosphor + label · toggle Dark Mode.
*Mobile (`<lg`): off-canvas drawer melalui ikon hamburger di topbar.*

**Topbar (sticky, 76px):**
- Tajuk halaman (`EXECUTIVE PERFORMANCE`) + subtajuk emas
- Dropdown **Tahun** (2023–2032, skrol)
- Dropdown **Period** (Mingguan / Bulanan / Suku Tahunan) — **aktif penuh** (D3): menukar unit pengiraan carta, kad ringkasan dan label perbandingan (`vs minggu lalu` / `vs bulan lalu` / `vs suku lalu`)
- Ikon **Notifikasi** + badge merah jika ada notis belum baca + dropdown senarai dinamik
- Dropdown **Profil** (nama sebenar pengguna, bukan "Akses Dalaman") → Tetapan, Log Keluar
- **Language switcher BM ⇄ EN** (baharu, D1)
*Mobile: tajuk + hamburger + notifikasi sahaja; dropdown lain dipindah ke bottom-sheet/drawer.*

**Kandungan:**

1. **Kad Prestasi Keseluruhan** — badge tier index semasa · toggle Bulanan/Tahunan · butang bulan Jan–Dis (skrol mendatar pada mobile) · Jumlah Jualan (38px) · Sasaran · Perubahan vs Sasaran (ikon arah + % + warna) · donut 210px `conic-gradient` peratus pencapaian · kapsyen unjuran jika bulan akan datang.
2. **3 kad ringkasan** — Kutipan/*Collection* (`ph-wallet`), Sebut Harga/*Quotation* (`ph-receipt`), Lead Baharu/*New Leads* (`ph-user-plus`) — setiap satu nilai + % perubahan vs sasaran.
3. **Jadual Prestasi Mengikut Servis** — Servis · Jumlah Jualan · Sasaran · Pencapaian (progress bar + %) · Status (Memuaskan ≥35% / Perlu Dipertingkat) · butang Lihat Detail. Toggle Bulanan/Tahunan + butang bulan diulang di header kad.
4. **Index Sasaran Jualan** — piramid 5 tier (lebar `100% − i×16%`, tersusun Sustainability → Critical) dengan penanda "📍 Anda di sini / Current" · jadual 7 lajur perbandingan Revenue & Untung Bersih (Bulanan / Suku Tahun ×3 / Tahunan ×12), baris semasa disorot.
5. **Trend Jualan & Sasaran** — `<x-trend-chart>` + carta bar bertindan 7 bulan mengikut 5 servis dengan legend warna.
6. **Keutamaan Minggu Ini** — senarai dinamik item tindakan dengan avatar pemilik; klik → halaman servis; footer → Laporan.
7. **Trend Jualan Tahunan** — carta unjuran 10 tahun (2023–2032) menggunakan faktor pertumbuhan.

### 6.4 Detail Servis (`/dashboard/servis/{key}`)

1. **Breadcrumb** — butang balik + "Dashboard Utama / {Servis}".
2. **Bar pemilih bulan** untuk Data Kritikal (12 butang, skrol mendatar).
3. **3 kad ringkasan** — Jumlah Jualan · Sasaran · donut kecil Pencapaian Sasaran.
4. **2 kad Actual vs Sasaran** — Quotation & Site Visit/Appointment (progress bar berwarna: ≥100% hijau, ≥50% kuning, <50% merah). *Nota: Divider tiada baris Site Visit → kad ini tidak dipapar untuk Divider; Bina Rumah guna label "Appointment".*
5. **Carta Trend Bulanan** — `<x-trend-chart>`.
6. **Trend Mingguan** — 3 mini bar chart (Amount Quotation, Bilangan Quotation, Bilangan Site Visit/Appointment) dengan **garis putus sasaran mingguan** + legend.
7. **Jadual Data Kritikal Mingguan** — 11 lajur: Data Kritikal · Minggu 1–4 (label julat tarikh 2 baris) · Jenis · Actual (auto-kira) · Sasaran (read-only untuk user) · Status · Pemilik (dropdown PIC) · Pelan Tindakan.
   - Legend status Green/Yellow/Red
   - Chip penapis "Tapis: {owner} ✕"
   - Butang **Google Sheet Integration** → buka Modal Sambung Google Sheet (D2)
   - Butang **Raw Data** → Modal JSON struktur data (D2)
   - Butang **+ Tambah PIC** → Modal Tambah PIC (D2)
8. **Jadual Projek** (D2 — baharu) — Nama Projek · Klien · Nilai (RM) · Status (Selesai / Dalam Proses / Menunggu Kelulusan / Perancangan, berwarna) · Tarikh. Admin boleh tambah/edit/padam.
9. **Prestasi Pemilik Data** — kad per PIC (klik untuk tapis jadual): chip nama · skor % (Green/total) · kiraan On Track / Ada Plan / Kritikal / total · badge "⚠ Perlu Tindakan" + senarai metrik kritikal jika ada Red.
10. **Analisis Penting** — kotak perbandingan Actual vs Sasaran · 3 tile (Pencapaian Jualan `ph-target`, Purata Nilai Projek `ph-chart-line-up`, Kadar Larian Diperlukan `ph-gauge`) · 2 kad nasihat mingguan · senarai "Tindakan Disyorkan" (teks berbeza mengikut ≥35% atau tidak).
11. **Kad Keutamaan** (jika ada untuk servis ini) — desc BM/EN + avatar + nama pemilik.

### 6.5 Laporan (`/dashboard/laporan`)

- Bar kawalan: dropdown tarikh (12 bulan), dropdown penapis servis (Semua + 5 servis), butang **Eksport** → jana CSV sebenar (`Response::streamDownload`), bukan toast palsu.
- 4 kad KPI: Jumlah Hasil · Jumlah Sebut Harga · **Kadar Penukaran** (dikira sebenar, bukan hardcode 8.2%) · Purata Nilai Deal.
- Carta Trend Keseluruhan (bertukar mengikut penapis servis).
- Jadual Pecahan Mengikut Servis: Servis · Jumlah Jualan · Sasaran · Projek · Pencapaian · Status.

### 6.6 Tetapan (`/dashboard/tetapan`)

- **Kad Keutamaan Sistem** — toggle Notifikasi Emel, Laporan Mingguan, Bunyi Amaran.
- **Kad Profil** (D2 — dibina semula) — nama, jawatan, emel, telefon, **upload avatar sebenar** (`WithFileUploads` → `storage/app/public/avatars`, validasi imej + saiz maks), mod Edit/Simpan.
- **Tukar Kata Laluan** — `current_password` + baharu + sahkan.
- **Pilihan bahasa** (BM/EN) dan **tema** (Dark/Light) — persist ke profil pengguna.
- Butang **Simpan Tetapan** — **wajib persist ke DB** (betulkan isu #8).
- Pautan **Admin Panel** — hanya dipapar jika `role === 'admin'`.

### 6.7 Admin Panel (`/admin`)

1. **Servis & Sasaran Bulanan** — jadual editable 5 servis (nama BM, nama EN, sasaran bulanan RM).
2. **Index Sasaran Jualan (Threshold)** — jadual editable 5 tahap (Revenue Bulanan, Untung Bersih Bulanan).
3. **Pemilik Data (PIC)** — senarai chip berwarna, tambah/buang (dengan guard backend).
4. **PIC Menunggu Kelulusan** (baharu, D2) — senarai `pending_approval` dengan butang Lulus/Tolak.
5. **Faktor Pertumbuhan Tahunan** — grid input 2023–2032 (boleh extend).
6. **Integrasi Google Sheet Lalai** — input URL.
7. **Urus Pengguna** (baharu) — senarai akaun, cipta/nyahaktif, tetapkan role.
8. **Log Audit** (baharu) — senarai perubahan: siapa, apa, bila, nilai lama → nilai baharu.
9. Butang **Simpan Semua / Save All** — semua perubahan dalam **satu `DB::transaction()`**, setiap perubahan sebenar dicatat ke `audit_logs` (jangan catat jika nilai tidak berubah).
10. Pautan "Lihat Dashboard" & "Log Keluar".

### 6.8 Komponen TrendChart

Blade component `<x-trend-chart :bars="$bars" :dots="$dots" :line-points="$linePoints" :max-label="$maxLabel" />`:

- Legend atas: petak pink `oklch(0.6 0.22 350)` = Jualan Sebenar · bulatan emas `oklch(0.78 0.12 85)` = Sasaran
- Paksi-Y: 5 label (`maxLabel`, ×0.75, ×0.5, ×0.25, `0`)
- SVG `viewBox="0 0 1180 380"` `preserveAspectRatio="none"`, `width:100%` → skala automatik
- `<polyline>` garis sasaran + `<circle r=4.5>` titik
- Bar `width:60%`, `border-radius:4px 4px 0 0`, tooltip `title`
- **Presentational sahaja** — semua pengiraan di Livewire/Service class induk

---

## 7. Model Data (Skema MySQL 8.4)

> Prototaip asal **tiada backend langsung**. Semua jadual di bawah adalah baharu, direka daripada struktur data yang dikesan dalam kod prototaip (rujuk `ANALISIS_KOMPONEN.md` §4.1 & §5.2 untuk nilai seeder tepat).

| Jadual | Tujuan | Lajur utama |
|---|---|---|
| `users` | Akaun log masuk | id, name, username(unik), email(unik), password, role enum(admin,user), phone, position, avatar_path, locale enum(ms,en) default ms, theme enum(dark,light) default dark, notif_email, notif_weekly, notif_sound, email_verified_at, last_login_at, is_active, timestamps |
| `otps` | Kod OTP login & reset | id, user_id FK, code_hash, type enum(login,reset), expires_at, consumed_at, attempts default 0, ip_address |
| `services` | 5 servis | id, key(slug unik), name_ms, name_en, icon_class, monthly_target dec(14,2), chart_color, sort_order |
| `index_tiers` | 5 tahap index | id, key(unik), name_ms, name_en, color_token, sort_order, monthly_revenue_threshold dec(14,2), monthly_profit_threshold dec(14,2) |
| `owners` | Pemilik data (PIC) | id, name, color_token, is_core bool, is_system bool *(untuk `INFO`)*, status enum(active,pending_approval,rejected), created_by FK, approved_by FK, approved_at |
| `critical_metrics` | Definisi baris metrik per servis | id, service_id FK, metric_key, label_ms, label_en, type enum(total,avg), value_type enum(currency,number), default_owner_id FK, sort_order |
| `critical_metric_targets` | Sasaran per metrik per tahun | id, critical_metric_id FK, year, monthly_target dec(14,2) nullable, target_text *(untuk nilai bukan-angka seperti "Progress")*, unique(metric,year) |
| `critical_weekly_entries` | Input mingguan actual | id, critical_metric_id FK, year, month tinyint, week_number tinyint(1-4), value dec(14,2) nullable, updated_by FK, unique(metric,year,month,week) |
| `critical_metric_months` | Metadata bulanan per metrik | id, critical_metric_id FK, year, month, owner_id FK nullable, action_plan text nullable, unique(metric,year,month) |
| `year_growth_factors` | Faktor pertumbuhan tahunan | id, year(unik), factor dec(6,4) |
| `projects` | Projek per servis | id, service_id FK, name, client_name, value dec(14,2), status enum(selesai,dalam_proses,menunggu_kelulusan,perancangan), project_date, created_by FK |
| `priorities` | Keutamaan minggu ini | id, service_id FK nullable, title_ms, title_en, desc_ms, desc_en, owner_id FK nullable, avatar_seed, icon_class, sort_order, is_active |
| `notifications` | Notifikasi sistem | jadual bawaan Laravel + custom trigger |
| `admin_settings` | Tetapan tunggal (key-value) | id, key(unik), value text |
| `audit_logs` | Log audit perubahan | id, user_id FK, action, subject_type, subject_id, old_values json, new_values json, ip_address, created_at |
| `sheet_integrations` | Status Google Sheet | id, service_id FK nullable *(null = lalai global)*, url, connected bool, last_synced_at |

### 7.1 Formula Turunan (dikira on-the-fly, WAJIB dalam Service class boleh-uji)

```
// Agregat
yearFactor       = year_growth_factors[tahun] ?? 1
totalSales       = Σ (effectiveSales servis) × yearFactor
totalTarget      = Σ (target servis)         × yearFactor
overallPct       = monthActual / monthTarget × 100
changeVsTarget   = (monthActual − monthTarget) / monthTarget × 100

// Untung & tier
estProfit        = monthActual × 0.18                          // margin anggaran 18%
threshold(tier)  = mod tahunan ? monthlyRevenue × 12 : monthlyRevenue
currentTier      = tier TERTINGGI yang dipenuhi (monthActual >= threshold)
tierWidthPct(i)  = 100 − i × 16                                // piramid 100/84/68/52/36%

// Status servis
statusServis     = pct >= 35 ? 'Memuaskan' : 'Perlu Dipertingkat'

// Status metrik kritikal
actual(bulan)    = Σ nilai minggu 1-4
pct              = actual / target × 100
if  tiada input bulan itu   → 'Belum Update' (kelabu)
elif pct >= 100             → 'Green'  (hijau)
elif ada action plan        → 'Yellow' (kuning)
else                        → 'Red'    (merah)

// Sasaran mingguan
weeklyQuotationTarget = ceil(monthlyTarget / 4)
weeklySiteVisitTarget = ceil(monthlyTarget / 4)
weeklyAmountTarget    = round(monthlyTarget / 4)

// Minggu Data Kritikal — penanda HARI KHAMIS (bukan Isnin–Ahad standard)
getMonthWeeks(m, y):
  thursdays = semua hari Khamis dalam bulan
  w1end = thursdays[1] ?? akhirBulan
  w2end = thursdays[2] ?? akhirBulan
  w3end = thursdays[3] ?? akhirBulan
  → [[1,w1end],[w1end+1,w2end],[w2end+1,w3end],[w3end+1,akhirBulan]]

// Prestasi PIC (kecualikan owner is_system seperti INFO)
scorePct = round(greenCount / totalMetrik × 100)
barColor = >=70 hijau · >=40 kuning · else merah

// Analisis servis
gap             = max(0, target − actual)
monthsLeft      = 12 − bulan_semasa            // DINAMIK, bukan hardcode 5
requiredRunRate = gap / monthsLeft
avgProjectValue = actual / bilanganProjek

// Laporan
conversionRate  = (projek disahkan / quotation dikeluarkan) × 100     // DIKIRA, bukan 8.2%
avgDealValue    = revenue / bilanganProjek
totalQuotation  = Σ 'Amount Quotation Release (New)' sebenar          // bukan revenue × 3.83

// Period multiplier (D3 — kini AKTIF)
Mingguan/Weekly   → mult 1     · label "vs minggu lalu / vs last week"
Bulanan/Monthly   → mult 4.33  · label "vs bulan lalu / vs last month"
Suku Tahunan/Qtr  → mult 13    · label "vs suku lalu / vs last quarter"

// Carta bertindan
totalPct(i)     = monthTotals[i] / max(monthTotals) × 100
segment.flex    = max(0.001, monthlyDelta[i])     // elak flex:0
```

---

## 8. Reka Bentuk & Responsiveness

### 8.1 Reka Bentuk Visual (Tiru 100%)

Semua token warna, saiz dan spacing tepat terdapat dalam `ANALISIS_KOMPONEN.md` §5.3 (26 CSS variable × dark/light) dan §5.4 (16 warna tetap). Ringkasan:

- **Emas jenama:** `oklch(0.78 0.12 85)` · **Pink carta:** `oklch(0.6 0.22 350)`
- **Latar:** gelap `oklch(0.15 0.025 260)` / terang `oklch(0.97 0.008 260)`
- **Kad:** gelap `oklch(0.19 0.025 260)` / terang `oklch(1 0 0)`
- **Status:** Green `oklch(0.55 0.15 145)` · Yellow `oklch(0.78 0.15 85)` · Red `oklch(0.55 0.2 25)` · Belum Update `oklch(0.6 0.02 260)`
- **Tier:** Critical `oklch(0.6 0.2 25)` · Survival `oklch(0.75 0.15 70)` · Growing `oklch(0.62 0.16 300)` · Stable `oklch(0.6 0.15 235)` · Sustainability `oklch(0.65 0.16 150)`
- **Admin Login:** merah amaran `oklch(0.6 0.2 25)` / `oklch(0.55 0.19 25)` / `oklch(0.68 0.19 25)`
- **Fon:** Plus Jakarta Sans (700-800: tajuk & angka besar) + Inter (400-700: badan) — **self-host** via Vite
- **Ikon:** Phosphor Duotone, **45 ikon** disenaraikan dalam `ANALISIS_KOMPONEN.md` §7 — self-host `@phosphor-icons/web` via npm
- **Tailwind CSS v4** — sokongan `oklch()` asli, salin token terus tanpa penukaran ke hex
- **Animasi:** `shake 0.4s` (ralat borang) & `toastIn 0.25s ease` (toast) — port keyframes yang sama
- **Dark mode default aktif**, toggle ke light melalui `data-theme` attribute + CSS custom properties (port `buildThemeVars()`)
- **Scrollbar tersuai:** 8px, thumb `oklch(0.35 0.02 260)`, radius 4px

### 8.2 Mobile Responsive (100% — item baharu sepenuhnya)

Prototaip asal `min-width:1440px` — **langsung tidak boleh digunakan pada mobile**. Bina semula *mobile-first*:

| Elemen | Desktop (≥1024px) | Mobile (<1024px) |
|---|---|---|
| Sidebar 264px | Sticky penuh | Off-canvas drawer (hamburger) atau bottom nav 5 ikon + menu "Lagi" |
| Kad Prestasi + 3 kad ringkasan | Sisi-bersisi (flex 2:1) | Stack menegak |
| Donut 210px | Sebelah kanan kad | Di bawah teks, saiz dikecilkan |
| Jadual Prestasi Servis (6 lajur) | `<table>`/grid | Senarai kad — setiap baris jadi kad label:nilai menegak |
| Jadual Data Kritikal (11 lajur) | Grid `min-width:1360px`, skrol mendatar | Skrol mendatar dengan **sticky first column**, atau kad boleh-kembang per metrik |
| Jadual Index Tier (7 lajur) | Grid `min-width:760px` | Skrol mendatar terkawal |
| Jadual Projek | Grid 5 lajur | Senarai kad |
| Piramid tier | Lebar berperingkat | Kekal, tetapi font & padding dikecilkan |
| Carta (TrendChart, stacked, mini) | Saiz penuh | `width:100%` responsif; label paksi-Y dikecilkan/disorok pada <400px |
| Butang bulan Jan–Dis | `max-width:340-420px` skrol | `overflow-x-auto` + **snap scroll**, sentuhan min **44×44px** |
| Chip penapis | Baris | `overflow-x-auto` |
| Topbar | Tajuk + 4 kawalan | Tajuk + hamburger + notifikasi; kawalan lain ke bottom-sheet |
| Admin Panel (grid multi-lajur) | Grid | 1 lajur |
| Admin Login panel kiri 46% | Split | Header ringkas di atas borang (RESTRICTED ACCESS kekal kelihatan) |
| Modal (Raw Data, Sheet, Tambah PIC) | Tengah 640px | Full-screen atau bottom-sheet |

**Breakpoint:** Tailwind default — `sm` 640 · `md` 768 · `lg` 1024 · `xl` 1280 · `2xl` 1536. Reka bentuk desktop asal (1440px) dipetakan sebagai baseline `xl`/`2xl`, **bukan minimum wajib**.

**Ujian wajib:** iPhone SE (375px) · iPhone 14 (390px) · iPad potret (768px) · iPad landskap (1024px) · desktop (1440px+).

---

## 9. Keperluan Bukan Fungsian

| Kategori | Keperluan |
|---|---|
| **Prestasi** | Lighthouse mobile ≥ 85; lazy-load carta/imej berat; `wire:loading` skeleton semasa pertukaran data; `wire:model.blur` pada input jadual untuk elak round-trip berlebihan |
| **Keselamatan** | Hash kata laluan (bcrypt/argon2); rate limiting log masuk & OTP; CSRF (bawaan); audit log semua perubahan Admin Panel; **role middleware pada setiap route** + Policy pada setiap kaedah Livewire yang menulis; validasi server-side penuh (Form Requests) |
| **Kebolehcapaian** | Kontras WCAG AA — **semak khusus `--t55`** yang bernilai sama pada dark & light (isu #23); `aria-*` pada ikon interaktif; navigasi papan kekunci untuk borang & dropdown; sasaran sentuh ≥44px |
| **Kebolehselenggaraan** | Konvensyen Laravel: Form Requests, Policies, Service classes untuk semua logik pengiraan (§7.1), Enum PHP 8.4 untuk status/role |
| **Ujian** | Unit test untuk **setiap** formula §7.1; feature test alur log masuk/OTP/role middleware/guard sasaran/guard buang PIC |
| **Dwibahasa** | String UI melalui `lang/ms/*.php` & `lang/en/*.php`; kandungan dinamik lajur `_ms`/`_en` + accessor model ikut locale |
| **Log & Monitoring** | Laravel log channel + (cadangan) Sentry/Flare selepas deploy Forge |
| **Backup** | Backup MySQL harian automatik (Forge scheduled backup atau `spatie/laravel-backup`) |

---

## 10. Item Yang Perlu "Di-Adjust" Berbanding Prototaip (26)

Senarai penuh dengan konteks teknikal terdapat dalam `ANALISIS_KOMPONEN.md` §6. Ringkasan mengikut keutamaan:

### 🔴 Kritikal (mesti dibetulkan)

| # | Isu | Pembetulan |
|---|---|---|
| 1 | OTP demo dipapar di skrin ("Demo: OTP anda ialah **123456**") | Buang sepenuhnya; hantar via emel sahaja |
| 2 | Kelayakan hardcoded (`dbena`/`••••••••`, `DBENASB`/`••••••••`) | Jadual `users`, kata laluan di-hash, berbilang akaun |
| 3 | Tiada backend — semua data array JS | MySQL 8.4 penuh (Seksyen 7) |
| 4 | `localStorage` sebagai satu-satunya storan (Admin Panel) | Jadual DB + `DB::transaction()` |
| 5 | Tiada semakan role — "Admin Panel" hanyalah `<a href>` | Middleware `role:admin` + Policy server-side |
| 6 | Tiada rate limit / lockout log masuk | `RateLimiter` 5 cubaan / 15 minit |
| 7 | `doLogout()` hanya toast — tiada logout sebenar | `Auth::logout()` + `session()->invalidate()` |
| 24 | `min-width:1440px` — langsung tiada sokongan mobile | Bina semula mobile-first (Seksyen 8.2) |

### 🟠 Tinggi

| # | Isu | Pembetulan |
|---|---|---|
| 8 | `saveSettings()` palsu — hanya toast | Persist sebenar ke `users` |
| 9 | `exportReport()` palsu — hanya toast | `Response::streamDownload` CSV |
| 10 | `removeOwner()` tiada guard backend | Tolak jika PIC masih ada `critical_weekly_entries` aktif; mesej ralat jelas |
| 11 | `addCustomOwner()` tidak persist (hilang bila refresh); tidak konsisten dengan Admin Panel yang persist | User cadang → status `pending_approval` → hanya global selepas Admin lulus |
| 12 | Warna PIC tidak konsisten Dashboard (peta nama + hash) ⇄ Admin Panel (index array) | Satu sumber kebenaran: `owners.color_token`, dijana sekali semasa cipta |
| 13 | `hasMonthData = criticalMonth === 'Jul'` hardcoded — hanya Julai ada data | Data setiap bulan/tahun dari DB (D3) |
| 14 | `monthsLeft = 5` hardcoded dalam Kadar Larian Diperlukan | `12 − now()->month` (atau tahun fiskal DBENA) |
| 15 | `conversionRateLabel = '8.2%'` hardcoded | Kira sebenar: projek disahkan / quotation dikeluarkan |
| 16 | `reportQuotationLabel = revenue × 3.83` — multiplier tanpa asas bisnes | Guna jumlah quotation sebenar dari `critical_weekly_entries`; jika DBENA mahu kekal nisbah, sahkan dahulu |
| 17 | `baseActualRatios` 7 bulan hardcoded `[120000…1172276]` sebagai bentuk taburan kumulatif | Kira terus dari data mingguan sebenar |

### 🟡 Sederhana

| # | Isu | Pembetulan |
|---|---|---|
| 18 | `periodConfig.mult` (1 / 4.33 / 13) dikira tetapi **tidak masuk mana-mana formula** | **Aktifkan penuh** (D3) — setiap mod benar-benar menukar unit pengiraan |
| 19 | Modal Raw Data bersarang dalam `sheetModalOpen` yang tiada pemicu — mustahil dicapai | Livewire property `showRawDataModal` berasingan & bersih (D2) |
| 20 | `nameEn` terbalik: renovation="Ubah Suai", divider="Pembahagi" (BM!); mihrab kosong | Betulkan: Renovation/Renovation · Divider/Divider · Mihrab/Mihrab — **sahkan dengan DBENA** |
| 21 | `INFO` bukan PIC sebenar tetapi masuk dropdown; tidak wujud dalam senarai Admin Panel | Lajur `is_system` — muncul dalam dropdown tetapi dikecualikan dari penilaian prestasi PIC |
| 22 | Avatar base64 runtime (`FileReader`), avatar dari CDN luar (pravatar/dicebear) | Upload sebenar ke `storage/app/public/avatars` (atau S3 di Forge) |
| 23 | `--t55` bernilai sama (`0.55`) pada dark & light → kontras rendah pada tema terang | Laraskan nilai tema terang, sahkan WCAG AA |
| 25 | Tiada audit trail — Admin boleh ubah threshold tanpa jejak | Jadual `audit_logs` (siapa/apa/bila/lama→baharu) |

### 🟢 Rendah

| # | Isu | Pembetulan |
|---|---|---|
| 26 | Aset dari CDN (Google Fonts, unpkg Phosphor, pravatar, dicebear) | Self-host semua via npm/Vite — kawalan versi, prestasi, privasi |

### 10.1 Ciri "Dead Code" yang DIBINA SEMULA (keputusan D2)

Logik penuh wujud dalam prototaip tetapi markupnya telah dibuang. Kesemuanya **dibina** dalam versi Laravel:

| Ciri | Bukti dalam prototaip | Skop Laravel |
|---|---|---|
| **Kad Profil + avatar** | `settings.name/role/email/phone/avatarUrl`, `toggleEditProfile()`, `updateSettingField()`, `onAvatarFileChange()`, `editIconClass`, `editLabel` | Borang profil penuh + upload avatar sebenar (6.6) |
| **Jadual Projek** | `projectsByService` (16 projek), `serviceProjects`, `serviceProjectCount`, peta warna status | Jadual projek per servis + CRUD admin (6.4 #8) |
| **Modal Google Sheet** | `sheetModalOpen`, `updateSheetUrl()`, `connectSheet()`, `syncSheetNow()`, `disconnectSheet()`, `sheetButtonLabel` | Modal sambung/segerak/putus + status `connected`/`last_synced_at` (admin sahaja) |
| **Modal Raw Data** | `rawDataOpen`, `toggleRawData()`, `rawDataJson` | Modal JSON struktur Data Kritikal, property berasingan |
| **Modal Tambah PIC** | `addOwnerModalOpen`, `updateNewOwnerName()`, `addCustomOwner()`, `customOwners` | Modal cadang PIC + alur kelulusan Admin |
| **Notifikasi statik** | `this.notifications` (4 item) tidak pernah digunakan | Digantikan sepenuhnya oleh notifikasi dinamik dari data kritikal + status sheet |
| **`dateMonthOptions`** | Dikira, dropdown topbar hanya papar tahun | Tambah pemilih bulan dalam dropdown topbar |

---

## 11. Tech Stack & Seni Bina

| Lapisan | Teknologi |
|---|---|
| Bahasa | PHP 8.4 (readonly properties, enums, typed properties penuh) |
| Framework | Laravel (versi stabil terkini, cth. 12.x) |
| Reaktiviti UI | Livewire 3.x |
| Templat | Blade + Blade Components |
| CSS | Tailwind CSS v4 (`oklch()` asli, `@theme` block) |
| Interaktiviti ringan | Alpine.js (bawaan Livewire) — dropdown, modal, drawer tanpa round-trip |
| Pangkalan Data | MySQL 8.4 |
| Auth | Laravel session-based + custom OTP flow (Notification + Mailable) |
| RBAC | Enum role + Gate/Policy (boleh naik taraf ke `spatie/laravel-permission`) |
| Ikon | Phosphor Icons Duotone (`@phosphor-icons/web` via npm) |
| Fon | Plus Jakarta Sans + Inter (self-host) |
| Build | Vite |
| Eksport | CSV (Fasa 1) · `maatwebsite/excel` + `barryvdh/laravel-dompdf` (Fasa 2) |
| Queue | Laravel Queue (database/redis) — emel OTP & notifikasi background |
| Scheduler | Laravel Scheduler — Laporan Mingguan automatik |
| Testing | Pest / PHPUnit |
| Kawalan Versi | Git → GitHub |
| Deployment | Laravel Forge (provisioning, deploy script, queue worker, scheduler, SSL, backup) |

---

## 12. Dwibahasa (I18n) — Pendekatan Teknikal

**Keputusan D1: language switcher penuh** — satu bahasa dipapar pada satu masa, mengikut pilihan pengguna.

1. **Fail bahasa:** `lang/ms/` & `lang/en/` — modul `app.php`, `auth.php`, `dashboard.php`, `service.php`, `laporan.php`, `tetapan.php`, `admin.php`, `validation.php`.
2. **Kandungan dinamik:** lajur `_ms`/`_en` (`name_ms`/`name_en`, `label_ms`/`label_en`, `desc_ms`/`desc_en`, `title_ms`/`title_en`) + accessor model:
   ```php
   public function getNameAttribute(): string {
       return app()->getLocale() === 'en' ? $this->name_en : $this->name_ms;
   }
   ```
3. **Persist:** `users.locale` (merentasi peranti) + fallback `session('locale')`/cookie untuk guest (skrin log masuk).
4. **Middleware `SetLocale`** — `App::setLocale()` pada awal setiap request.
5. **Komponen `LanguageSwitcher`** — di topbar & skrin log masuk; tukar segera tanpa reload penuh (Livewire).
6. **Nombor & tarikh:** format RM konsisten (`'RM' . number_format($amount)`), tarikh ikut locale (`Carbon::setLocale()`), nama bulan dari fail bahasa (BM: Jan/Feb/Mac/Apr/Mei/Jun/Jul/Ogo/Sep/Okt/Nov/Dis · EN: Jan–Dec).
7. **Semua 8 skrin** mesti bertukar sepenuhnya — tiada string BM yang tertinggal dalam mod EN dan sebaliknya (kriteria penerimaan).

---

## 13. Fasa Pembangunan

| Fasa | Skop | Output |
|---|---|---|
| **0 — Persediaan** | Repo GitHub, skeleton Laravel + Livewire + Tailwind v4/Vite, token warna `@theme`, self-host fon & ikon | `php artisan serve` + `npm run dev` jalan bersih |
| **1 — DB & Model** | 16 migrasi, Eloquent models + relationships + accessor dwibahasa, factories, seeder data demo penuh | `migrate:fresh --seed` berjaya |
| **2 — Auth & Shell** | UserLoginFlow, AdminLoginFlow, OTP emel sebenar, middleware role, layout sidebar/topbar responsif, dark/light, language switcher | Kedua role boleh log masuk; user disekat dari `/admin` |
| **3 — Dashboard & Detail Servis** | `DashboardMetricsService` (semua formula §7.1), Overview, ServiceDetail, Data Kritikal CRUD, TrendChart, Jadual Projek, 3 modal (D2) | Dashboard penuh dengan data sebenar |
| **4 — Laporan & Tetapan** | Laporan + eksport CSV, Tetapan + Kad Profil + upload avatar + tukar kata laluan | Modul selesai, data persist |
| **5 — Admin Panel** | ConfigPanel penuh, kelulusan PIC, urus pengguna, audit log, transaksi Simpan Semua | Admin urus semua master data |
| **6 — Responsive & QA** | Ujian semua breakpoint, a11y, keselamatan, unit + feature test | Sedia UAT |
| **7 — Deploy** | Forge (server, DB, queue, scheduler, SSL, backup), CI/CD GitHub | Sistem live |

---

## 14. Kriteria Penerimaan

- [ ] Setiap skrin prototaip (Login, Admin Login, Dashboard, Service Detail ×5, Laporan, Tetapan, Admin Panel) mempunyai padanan Laravel yang **sepadan visual** pada breakpoint desktop.
- [ ] Semua skrin **berfungsi penuh & selesa digunakan** pada 375px tanpa horizontal scroll tidak disengajakan (kecuali jadual kompleks yang memang direka untuk skrol mendatar terkawal dengan sticky column).
- [ ] Log masuk User & Admin memerlukan kata laluan di-hash + **OTP emel sebenar** (tidak pernah dipaparkan di skrin).
- [ ] Role `user` **tidak boleh** akses `/admin` walaupun URL ditaip terus — disahkan oleh feature test.
- [ ] Role `user` **tidak boleh** mengubah lajur Sasaran walaupun melalui manipulasi network request — disahkan oleh feature test.
- [ ] Semua formula §7.1 diuji dengan unit test dan padan dengan output prototaip untuk input yang sama.
- [ ] **Language switcher menukar SEMUA string UI** (bukan sebahagian) antara BM dan EN pada kesemua 8 skrin.
- [ ] Dropdown Period benar-benar mengubah pengiraan (bukan hiasan) — disahkan oleh unit test.
- [ ] Data Kritikal boleh diisi untuk **mana-mana bulan/tahun**, bukan Julai sahaja.
- [ ] Kesemua 7 ciri "dead code" (§10.1) dibina dan berfungsi.
- [ ] Semua data yang sebelum ini hardcoded/localStorage kini boleh diedit melalui UI dan **kekal selepas logout/login semula**.
- [ ] Buang PIC yang masih ada data aktif → sistem tolak dengan mesej jelas.
- [ ] Setiap perubahan konfigurasi Admin Panel tercatat dalam `audit_logs` dengan nilai lama & baharu yang betul.
- [ ] Repo Git bersih (tiada kredensial hardcoded, `.env.example` lengkap), boleh di-deploy ke Forge mengikut `CLAUDE_CODE_PROMPT.md`.
- [ ] Lighthouse mobile ≥ 85 pada halaman Dashboard.

---

## 15. Aset & Rujukan

- **Logo:** `assets/logo-dbena.png` → `public/images/logo-dbena.png` (dipapar dalam kotak border emas berlatar putih `oklch(0.97 0.005 260)`)
- **Fail rujukan reka bentuk:** `Login.dc.html`, `Admin Login.dc.html`, `Executive Dashboard.dc.html`, `Admin Panel.dc.html`, `TrendChart.dc.html` — rujuk terus untuk ketepatan visual pixel-level
- **Inventori teknikal:** `ANALISIS_KOMPONEN.md` — senarai lengkap 98 fungsi, 76 state property, 26 token tema, 45 ikon, 15 kumpulan formula
- **Arahan pembangunan:** `CLAUDE_CODE_PROMPT.md`
- **Emel operasi rujukan:** `dbenareport@gmail.com` (seed/contoh sahaja — SMTP produksi ditentukan berasingan oleh DBENA)
- **Data seeder:** 5 servis · 5 index tier · 4 PIC teras (ZIKRI, HAFIZAN, NIZAM, AZHARI) + 1 system owner (INFO) · 49 baris metrik kritikal · 16 projek demo · 3 keutamaan · 10 faktor pertumbuhan (2023–2032)

### 15.1 Soalan Terbuka (perlu pengesahan DBENA)

| # | Soalan | Kesan jika tidak dijawab |
|---|---|---|
| Q1 | Nama English sebenar untuk Renovation, Divider dan Mihrab (lajur `nameEn` prototaip terbalik/kosong) | Terjemahan EN tidak tepat |
| Q2 | Adakah nisbah `quotation = revenue × 3.83` masih relevan, atau guna data quotation sebenar? | Kad "Jumlah Sebut Harga" di Laporan |
| Q3 | Definisi rasmi "Kadar Penukaran" — projek/quotation, atau lead/quotation? | Formula conversion rate |
| Q4 | Tahun fiskal DBENA (Jan–Dis, atau lain?) untuk `monthsLeft` | Kadar Larian Diperlukan |
| Q5 | Adakah `INFO` sepatutnya PIC sebenar atau label sistem? | Prestasi Pemilik Data |
| Q6 | Nilai `target: 'Progress'` pada Sales Collection (Progress Claim) — bagaimana status dinilai? | Status metrik kekal Red selamanya |

---

*Dokumen ini adalah rujukan induk (single source of truth) bagi keperluan produk. Sebarang perubahan skop perlu dikemaskini di sini dahulu sebelum pembangunan diteruskan.*
