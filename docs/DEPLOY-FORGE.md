# Deploy ke Laravel Forge

Panduan lengkap untuk server **dbenaserver**.

> **Prasyarat:** kod sudah ditolak ke repositori GitHub *private*.
> Forge menarik dari GitHub — ia tidak boleh membaca komputer anda.

---

## 1. Sediakan server

Buka Forge → server **dbenaserver** → tab **PHP**.

Pastikan **PHP 8.4** dipasang dan ditetapkan sebagai lalai. Jika tiada, klik *Install PHP 8.4*, tunggu selesai, kemudian *Make Default*.

Tab **Database**: sahkan MySQL 8.x berjalan.

---

## 2. Sambungkan GitHub ke Forge

Forge → menu profil (atas kanan) → **Source Control** → sambung akaun GitHub anda.

Ini dibuat sekali sahaja untuk semua projek.

---

## 3. Cipta site

Server **dbenaserver** → tab **Sites** → **Add Site**.

| Medan | Nilai |
|---|---|
| Root Domain | `dashboard.dbena.com.my` (atau subdomain pilihan anda) |
| Project Type | General PHP / Laravel |
| Web Directory | `/public` |
| PHP Version | **PHP 8.4** |

Klik **Add Site**.

> Belum ada domain? Guna alamat IP server buat sementara, atau subdomain percuma
> yang Forge sediakan. Domain boleh ditukar kemudian.

---

## 4. Sambungkan repositori

Site baharu → tab **Apps** → **Git Repository**.

| Medan | Nilai |
|---|---|
| Provider | GitHub |
| Repository | `USERNAME/dbena-dashboard` |
| Branch | `main` |
| Install Composer Dependencies | ✅ tandakan |

Klik **Install Repository**. Forge akan clone dan jalankan `composer install`.

---

## 5. Cipta pangkalan data

Server **dbenaserver** → tab **Database** → **Add Database**.

| Medan | Nilai |
|---|---|
| Database Name | `dbena_dashboard` |
| Database User | `dbena` |
| Password | jana yang kuat — **simpan**, anda perlukannya di langkah 6 |

---

## 6. Konfigurasi `.env`

Site → tab **Environment** → **Edit Environment**.

Tetapkan sekurang-kurangnya ini:

```ini
APP_NAME="DBENA Dashboard"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dashboard.dbena.com.my

APP_LOCALE=ms
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Asia/Kuala_Lumpur

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbena_dashboard
DB_USERNAME=dbena
DB_PASSWORD=<kata-laluan-dari-langkah-5>

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database
FILESYSTEM_DISK=public

# SMTP SEBENAR — tanpa ini, OTP tidak akan sampai dan tiada siapa boleh log masuk
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=dbenareport@gmail.com
MAIL_PASSWORD=<app-password-16-aksara>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="dbenareport@gmail.com"
MAIL_FROM_NAME="DBENA Dashboard"

# Google Sheet
DBENA_SHEETS_DRIVER=link
DBENA_SHEETS_SYNC_MINUTES=15
```

> **Gmail:** kata laluan biasa TIDAK berfungsi. Anda perlu *App Password*:
> Akaun Google → Security → 2-Step Verification → App passwords.
> `APP_DEBUG=false` adalah wajib — `true` mendedahkan kod dan kelayakan
> kepada sesiapa yang melihat halaman ralat.

Simpan.

---

## 7. Deploy script

Site → tab **Apps** → **Deploy Script**. Ganti kandungan dengan:

```bash
cd /home/forge/dashboard.dbena.com.my

git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

( flock -w 10 9 || exit 1
  echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

npm ci
npm run build

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan queue:restart
```

Tukar baris `cd` kepada laluan site sebenar anda (Forge memaparkannya di atas kotak skrip).

---

## 8. Deploy pertama

Klik **Deploy Now**. Perhatikan output.

Jika `npm ci` gagal kerana Node terlalu lama:
Server → tab **Node** → naik taraf ke Node 20 atau lebih baru.

---

## 9. Isi data awal

Ini dijalankan **sekali sahaja** — ia menjana kata laluan akaun demo.

Site → tab **Commands** → **Run Command**:

```
php artisan db:seed --force
```

⚠️ **Output akan memaparkan kata laluan admin dan user. Salin sekarang** —
ia tidak akan dipaparkan semula.

---

## 10. Queue worker — WAJIB

Emel OTP dan sync Google Sheet melalui baris gilir. Tanpa worker, **tiada siapa
boleh log masuk** kerana OTP tidak akan dihantar.

Site → tab **Queue** → **New Worker**:

| Medan | Nilai |
|---|---|
| Connection | `database` |
| Queue | `default` |
| Maximum Seconds Per Job | `60` |
| Maximum Tries | `3` |
| Processes | `1` |

Klik **Start Worker**.

---

## 11. Scheduler — WAJIB untuk sync automatik

Server **dbenaserver** → tab **Scheduler** → **New Scheduled Job**:

| Medan | Nilai |
|---|---|
| Command | `php /home/forge/dashboard.dbena.com.my/artisan schedule:run` |
| User | `forge` |
| Frequency | **Every Minute** |

Ini yang memacu sync Google Sheet setiap 15 minit, Laporan Mingguan automatik,
dan pembersihan log.

---

## 12. SSL

Site → tab **SSL** → **LetsEncrypt** → **Obtain Certificate**.

Domain mesti sudah menunjuk ke IP server sebelum ini berjaya.

---

## 13. Deploy automatik (pilihan)

Site → tab **Apps** → hidupkan **Quick Deploy**.

Selepas ini, setiap `git push origin main` akan trigger deploy sendiri.

---

## 14. Sahkan ia hidup

1. Buka domain → sepatutnya alih ke `/login`
2. Log masuk dengan akaun dari langkah 9 → OTP patut sampai ke emel
3. Site → tab **Queue** → sahkan worker berstatus *running*
4. Admin → Google Sheet → tampal pautan → **Baca Sheet & Pratonton**

---

## Backup

Server **dbenaserver** → tab **Backups** → **Add Backup**.

Pilih pangkalan data `dbena_dashboard`, frekuensi harian, dan destinasi
(S3 / DigitalOcean Spaces / Backblaze).

---

## Bila sesuatu tidak kena

| Gejala | Semak |
|---|---|
| Halaman putih / ralat 500 | Site → **Logs**, atau `storage/logs/laravel.log` |
| OTP tidak sampai | Queue worker berjalan? Kelayakan SMTP betul? Semak tab **Queue** untuk job gagal |
| Sheet tidak disegerak | Scheduler aktif? Admin → Google Sheet → **Log Sync** menunjukkan sebab |
| CSS tidak dimuatkan | `npm run build` berjaya semasa deploy? Semak output deploy |
| 419 Page Expired | `APP_URL` dalam `.env` sepadan dengan domain sebenar? |
| Perubahan tidak muncul | `php artisan config:clear` melalui tab Commands |

---

## Selepas UAT

Sebelum pengguna sebenar masuk:

- [ ] Tukar kata laluan akaun demo
- [ ] Cipta akaun sebenar melalui Admin → Urus Pengguna
- [ ] Nyahaktifkan akaun demo
- [ ] Sahkan `APP_DEBUG=false`
- [ ] Uji sync Google Sheet dengan data sebenar
- [ ] Sahkan backup berjalan
