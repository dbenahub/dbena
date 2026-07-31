# DBENA Executive Dashboard

Dashboard prestasi jualan **DBENA SDN BHD** — Laravel 12 · PHP 8.4 · MySQL 8.4 · Livewire 3 · Tailwind CSS v4.

Dibina semula daripada prototaip statik `.dc.html` kepada aplikasi produksi penuh, dengan **100% mobile responsive**, **RBAC sebenar (admin/user)**, dan **sistem dwibahasa BM ⇄ EN**.

---

## Keperluan

| Perisian | Versi |
|---|---|
| PHP | ^8.4 |
| Composer | ^2.7 |
| MySQL | ^8.4 |
| Node.js | ^20 |

Sambungan PHP: `pdo_mysql`, `mbstring`, `openssl`, `gd` atau `imagick` (upload avatar), `bcmath`, `intl`.

---

## Setup Tempatan

```bash
git clone <repo-url> dbena-dashboard
cd dbena-dashboard

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Kemas kini kelayakan pangkalan data dalam `.env`, kemudian:

```bash
php artisan migrate --seed
php artisan storage:link      # wajib — untuk paparan avatar
npm run dev                   # atau: npm run build
php artisan serve
```

> **Akaun demo:** kata laluan dijana **rawak** semasa `migrate --seed` dan dipaparkan **sekali sahaja** dalam output konsol. Salin ketika itu — ia tidak pernah ditulis ke dalam kod. Jika terlepas, jalankan `php artisan db:seed --class=UserSeeder` semula.
>
> - **Admin** → `/admin/login` (username `DBENASB`)
> - **User** → `/login` (username `dbena`)

### Membaca OTP semasa pembangunan

`MAIL_MAILER=log` dalam `.env` bermakna emel ditulis ke fail, bukan dihantar:

```bash
tail -f storage/logs/laravel.log
```

Kod OTP muncul di dalam badan emel. **Ia tidak pernah dipaparkan di antara muka** — ini adalah pembetulan keselamatan berbanding prototaip asal.

Untuk emel sebenar semasa pembangunan, tetapkan `MAIL_MAILER=smtp` dengan kelayakan Mailtrap.

### Jalankan semuanya sekali gus

```bash
composer dev    # server + queue worker + vite
```

---

## Ujian

```bash
php artisan test                          # semua
php artisan test --testsuite=Unit         # formula bisnes sahaja
php artisan test --filter=Authorization   # RBAC & guard
```

Liputan ujian merangkumi:

- **Unit** — setiap formula dalam `DashboardMetricsService` (sempadan tier, penanda hari Khamis, ambang 35%, margin 18%, pengganda period, koordinat SVG carta)
- **Feature** — alur OTP, rate limiting, middleware role, guard sasaran, guard buang PIC, alur kelulusan PIC, eksport CSV, integriti seeder, kesepadanan kunci bahasa

---

## Seni Bina

```
app/
├── Enums/            10 enum backed (role, status, period, mod paparan)
├── Livewire/
│   ├── Auth/         UserLoginFlow · AdminLoginFlow
│   ├── Concerns/     HandlesOtpFlow (alur dikongsi kedua-dua skrin log masuk)
│   ├── Dashboard/    Overview · ServiceDetail · Laporan · Tetapan
│   └── Admin/        ConfigPanel
├── Models/           15 model + trait HasBilingualAttributes
├── Policies/         CriticalMetric · Owner · Project
└── Services/
    ├── DashboardMetricsService   ← SEMUA formula bisnes (boleh diuji unit)
    ├── CriticalDataService       ← pembinaan baris Data Kritikal
    ├── AuditLogger               ← rekod hanya perubahan sebenar
    └── OtpService                ← jana · hash · hantar · sahkan
```

**Prinsip:** tiada formula bisnes ditulis di dalam komponen Livewire. Semuanya berada dalam Service class supaya boleh diuji tanpa HTTP atau sesi.

### Keselamatan

Kebenaran disahkan di **backend**, bukan dengan menyorok butang:

```php
// ServiceDetail::updateTarget()
$this->authorize('updateTarget', CriticalMetric::class);   // Policy: admin sahaja
```

Memanggil kaedah ini sebagai role `user` melalui devtools/network menghasilkan **403** — disahkan oleh `tests/Feature/AuthorizationTest.php`.

| Kawalan | Pelaksanaan |
|---|---|
| Kata laluan | bcrypt (cast `hashed`) |
| OTP | 6 digit · di-hash · sah 5 minit · 3 percubaan · cooldown 60s · emel sahaja |
| Rate limit log masuk | 5 percubaan / 15 minit per IP+username |
| RBAC | middleware `role:admin` + Gate/Policy setiap penulisan |
| Audit | setiap perubahan konfigurasi ke `audit_logs` (lama → baharu) |
| CSRF | bawaan Laravel/Livewire |

### Dwibahasa

- **String UI** → `lang/ms/*.php` & `lang/en/*.php` (8 modul, 394 kunci)
- **Data dinamik** → lajur `_ms`/`_en` + accessor:
  ```php
  $service->name;   // ikut app()->getLocale(), fallback ke BM
  ```
- **Persist** → `users.locale` (merentasi peranti) + sesi untuk guest
- Ujian `LocalizationTest` menghalang kunci tertinggal antara dua fail bahasa

### Tema

26 CSS custom property × mod gelap/terang, nilai `oklch()` disalin **tepat** dari prototaip. Ditukar melalui `data-theme` pada `<html>`, persist ke `users.theme`.

Nilai `--t55` dan `--t50` pada tema **terang** telah digelapkan berbanding prototaip untuk memenuhi kontras WCAG AA.

---

## Responsive

Prototaip asal terkunci pada `min-width: 1440px` — langsung tiada sokongan mobile. Sistem ini dibina *mobile-first*:

| Elemen | Desktop | Mobile |
|---|---|---|
| Sidebar 264px | Sticky | Off-canvas drawer (hamburger) |
| Jadual servis / laporan | Grid | Senarai kad |
| Data Kritikal (11 lajur) | Grid skrol, lajur 1 melekat | Kad boleh-kembang per metrik |
| Index Tier (7 lajur) | Grid skrol | Skrol mendatar terkawal |
| Butang bulan | Baris | `overflow-x-auto` + snap, sasaran ≥44px |
| Modal | Kad berpusat | Bottom-sheet skrin penuh |
| Admin Login panel kiri | 46% split | Header ringkas (badge amaran kekal) |

Diuji pada 375px · 390px · 768px · 1024px · 1440px.

---

## Integrasi Google Sheet

Data Kritikal Mingguan ditarik terus dari Google Sheet DBENA. Sheet kekal tempat kerja harian PIC; dashboard memaparkannya.

### Susun atur yang disokong

Sistem dikonfigurasi untuk susun atur **sheet DBENA sedia ada** — satu tab memegang kesemua 5 servis:

```
baris 1  │ MASUKKAN DATA & REPORT DALAM KOTAK BERWARNA MERAH   ← banner (dilangkau)
baris 2  │ DATA CRITICAL │ Week 1 │ … │ Data Owner │ Action Plan
baris 3  │ Renovation                                          ← jalur servis
baris 4  │ Sales Collection (New) │ … │ ZIKRI │ …
   ⋮     │ (10 metrik Renovation)
baris 14 │ Bina Rumah                                          ← jalur servis
   ⋮
```

| Lajur | Kandungan | Digunakan |
|---|---|---|
| A | DATA CRITICAL | Nama metrik **dan** jalur servis |
| B–E | Week 1–4 | ✅ Ditarik ke DB |
| F | Data Type | Rujukan sahaja |
| G | Monthly Actual | Diabaikan — dikira semula dari B–E |
| H | Monthly Target | Opt-in (lihat bawah) |
| I | Data Status | Diabaikan — dikira semula |
| J | Data Owner | ✅ Ditarik |
| K | Action Plan | ✅ Ditarik |

**Baris jalur servis** dikenal pasti apabila lajur A memadani nama servis (`Renovation`, `Kabinet`, `Bina Rumah`, `Divider`, `Mihrab`) **dan** lajur minggu kosong. Semua metrik selepasnya dimiliki servis itu sehingga jalur berikutnya. Ini bermakna `Revenue/Sales` di bawah `Renovation` dan `Revenue/Sales` di bawah `Bina Rumah` masuk ke rekod berbeza dengan betul.

Jika sesetengah servis menggunakan tab berasingan, tukar susun atur kepada *satu sheet/tab untuk satu servis* dan konfigurasi setiap servis sendiri.

### Setup

1. Google Sheet → **Share** → General access → **"Anyone with the link"** → **Viewer**
2. Dashboard → **Admin → Google Sheet** → pilih **Semua Servis (satu sheet)**
3. Tampal pautan, tekan **Baca Sheet & Pratonton**
4. Pemetaan lajur sudah **dipra-isi** mengikut susun atur di atas. Pratonton mengesahkan baris jalur dikesan dan metrik dipadankan — sebelum apa-apa ditulis
5. Hidupkan **Aktifkan sync automatik**, tekan **Segerak Sekarang**

Baris header dikesan automatik (`header_row = 0`), jadi banner arahan di baris 1 tidak mengelirukan sistem.

### Tiga cara data masuk

| Kaedah | Kependaman | Setup |
|---|---|---|
| **Berjadual** | ≤15 minit | Automatik (perlu cron + queue worker di Forge) |
| **Butang Segerak Sekarang** | Serta-merta | Tiada |
| **Apps Script** | ~10 saat selepas suntingan | Salin skrip dari UI ke sheet, Run `installTrigger` sekali |

Ubah kekerapan melalui `DBENA_SHEETS_SYNC_MINUTES` dalam `.env`.

### Padanan & format

Nama metrik dipadankan secara longgar — huruf besar/kecil, tanda baca dan ruang berlebihan tidak penting:

```
"No of New Quotation"      ✓
"NO. OF NEW QUOTATION"     ✓   → metrik yang sama
"  no  of new quotation "  ✓
```

Nilai menerima format sheet sebenar: `RM12,500.00` · `1,234,567` · `(500)` sebagai negatif · `-`, `N/A`, `tiada`, `Progress` sebagai kosong.

Baris yang tidak dapat dipadankan **dilangkau dan disenaraikan** dengan konteks servisnya (`Bina Rumah › Metrik Rekaan`) dalam log sync — tidak pernah gagal senyap.

### Monthly Target (lajur H)

**Lalai: MATI.** Sasaran diurus dalam Admin Panel supaya hanya ada satu sumber kebenaran.

Hidupkan **Import Monthly Target dari sheet** jika DBENA lebih suka mengekalkan sasaran dalam sheet. Nilai teks seperti `Progress` disimpan sebagai teks, bukan sifar — jadi baris itu tidak dinilai sebagai kegagalan peratus.

### Sheet peribadi

Jika sheet tidak boleh dikongsi secara awam:

1. Google Cloud Console → projek → aktifkan **Google Sheets API**
2. Cipta **Service Account** → Keys → Add Key → JSON
3. Letak di `storage/app/google/service-account.json`
4. Kongsi sheet dengan emel `client_email` dari fail JSON (Viewer)
5. `.env` → `DBENA_SHEETS_DRIVER=service`

JWT ditandatangani secara asli — tiada pakej Google tambahan diperlukan.

### Menjejak masalah

**Admin → Google Sheet → Log Sync** merekodkan setiap larian: pencetus, status, servis dikesan, baris dibaca/dipadankan, nilai dikemas kini, dan label tidak dipadankan.

```bash
php artisan dbena:sync-sheets --sync          # jalankan sekarang, papar hasil
php artisan dbena:sync-sheets --month=7
```

---

## Laporan Prestasi Pemilik

**Laporan Pemilik** dalam sidebar menilai setiap PIC merentasi tempoh **mingguan, bulanan atau tahunan**.

Setiap PIC menerima:

- **Skor & gred** (A–E) berdasarkan metrik yang mencapai sasaran
- **Arah aliran** berbanding tempoh sebelumnya, dengan delta mata
- **Ulasan naratif terperinci** — menamakan metrik terlemah dan terbaik secara spesifik, mengasingkan jurang *disiplin* (tiada pelan tindakan) daripada jurang *prestasi*, dan menjumlahkan jurang kewangan dalam RM
- **Senarai tindakan berkeutamaan** — setiap satu merujuk metrik sebenar dengan nombor actual vs sasaran

Dalam mod mingguan, sasaran bulanan dibahagi empat secara automatik supaya perbandingan adil.

Ulasan **deterministik** — dijana daripada data, bukan AI. Laporan yang sama sentiasa menghasilkan teks yang sama, jadi ia boleh dipercayai untuk mesyuarat dan rekod.

**Eksport PDF** menghasilkan dokumen berjenama DBENA (A4, dua PIC setiap halaman) sesuai untuk diedar atau dilampirkan dalam emel.

---

## Deploy ke Laravel Forge

1. **Provisi server** — PHP 8.4, MySQL 8.4.
2. **Sambung repo GitHub** ke site; aktifkan auto-deploy pada `main` jika dikehendaki.
3. **Deploy script:**

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

4. **`.env` produksi** — set di panel Forge, **jangan** commit. Wajib:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - Kelayakan SMTP sebenar (OTP tidak akan berfungsi dengan `MAIL_MAILER=log`)
   - `QUEUE_CONNECTION=database` atau `redis`

5. **Queue Worker** — daemon `php artisan queue:work --tries=3`. Emel OTP, notifikasi **dan sync Google Sheet** melalui baris gilir. Tanpa worker: OTP tidak sampai dan sheet tidak disegerak.

6. **Scheduler** — cron `* * * * * php artisan schedule:run`. Menjalankan sync Google Sheet (setiap 15 minit), Laporan Mingguan automatik, dan pembersihan log.

7. **SSL** — Let's Encrypt melalui Forge.

8. **Backup** — Forge scheduled backup atau `spatie/laravel-backup` ke S3/Backblaze.

---

## Perbezaan Berbanding Prototaip

26 isu prototaip telah diperbetulkan. Yang paling penting:

| Prototaip | Sistem ini |
|---|---|
| OTP dipapar di skrin ("Demo: OTP anda ialah…") | Emel sahaja, di-hash dalam DB |
| Kelayakan hardcoded dalam kod sumber | Jadual `users`, kata laluan rawak di-hash |
| Semua data array JS hardcoded | MySQL 8.4, 16 jadual |
| Admin Panel hanyalah `<a href>` | Middleware `role:admin` + Policy |
| `removeOwner()` tiada guard | Tolak jika PIC teras/sistem/ada data aktif |
| Tambah PIC hilang bila refresh | Alur `pending_approval` → kelulusan Admin |
| Warna PIC berbeza antara skrin | Satu sumber: `owners.color_token` |
| Hanya bulan Julai ada data | Setiap bulan/tahun dari DB |
| `monthsLeft = 5` hardcoded | `12 − bulan_semasa` (tahun fiskal boleh dikonfigur) |
| `conversionRate = '8.2%'` hardcoded | Projek disahkan ÷ quotation dikeluarkan |
| `quotation = revenue × 3.83` | Jumlah quotation sebenar |
| Dropdown Period tiada kesan | ×1 / ×4.33 / ×13 benar-benar dipakai |
| `saveSettings()` toast palsu | Persist sebenar ke `users` |
| `exportReport()` toast palsu | CSV sebenar dengan BOM UTF-8 |
| Avatar base64 runtime | Fail sebenar di `storage/app/public/avatars` |
| Modal Raw Data mustahil dicapai | Property Livewire berasingan |
| Tiada jejak audit | Jadual `audit_logs` (siapa/apa/bila/lama→baharu) |
| `min-width: 1440px` | Mobile-first penuh |
| Dwibahasa serentak statik | Language switcher BM ⇄ EN sebenar |
| Fon/ikon/avatar dari CDN | Self-host melalui Vite |

Analisis penuh: lihat `../ANALISIS_KOMPONEN.md` · Spesifikasi: `../PRD.md`

---

## Soalan Terbuka untuk DBENA

Perkara berikut ditanda `// TODO: sahkan dengan DBENA` dalam kod:

1. Nama English rasmi untuk Renovation, Divider, Mihrab (prototaip menyimpan BM dalam lajur `nameEn`).
2. Definisi rasmi "Kadar Penukaran" — projek/quotation atau lead/quotation?
3. Tahun fiskal DBENA (kini diandaikan Jan–Dis melalui `config('dbena.fiscal_year_end_month')`).
4. Sasaran `'Progress'` pada Sales Collection (Progress Claim) — bagaimana ia patut dinilai? Kini disimpan sebagai teks dan dikecualikan daripada pengiraan peratus.
5. Adakah nisbah quotation `× 3.83` masih relevan? Kini digantikan dengan data sebenar.

---

*Kod proprietari DBENA SDN BHD.*
