# Analisis Inventori Penuh — Prototaip DBENA Dashboard (`.dc.html`)

**Tarikh analisis:** 27 Julai 2026
**Fail dianalisis:** `Login.dc.html`, `Admin Login.dc.html`, `Executive Dashboard.dc.html`, `Admin Panel.dc.html`, `TrendChart.dc.html`, `support.js`
**Tujuan:** Senarai lengkap setiap komponen UI, state, fungsi, data, token warna dan interaksi — sebagai rujukan tepat untuk rebuild Laravel. **Tiada apa-apa ditinggalkan**, termasuk kod mati (dead code) yang logiknya wujud tetapi markupnya telah dibuang.

---

## 0. Ringkasan Fail & Seni Bina Prototaip

| Fail | Baris | Peranan | Persist? |
|---|---|---|---|
| `support.js` | 1,911 | Runtime proprietari "DC" (dijana dari `dc-runtime/src/*.ts`, berasaskan React). Menyediakan `DCLogic`, `<x-dc>`, `<sc-if>`, `<sc-for>`, `<dc-import>`, `<helmet>`. **Bukan sebahagian aplikasi — tidak perlu diport.** | — |
| `Login.dc.html` | 311 | Log masuk pengguna (kad tunggal, tema emas) | ❌ state runtime sahaja |
| `Admin Login.dc.html` | 327 | Log masuk admin (split-panel, tema merah) | ❌ state runtime sahaja |
| `Executive Dashboard.dc.html` | 1,563 | **Aplikasi utama** — 4 halaman dalam satu komponen (dashboard / service / laporan / tetapan) | ❌ state runtime; baca `localStorage` |
| `Admin Panel.dc.html` | 235 | Konfigurasi induk | ✅ `localStorage['dbena_admin_settings']` |
| `TrendChart.dc.html` | 57 | Komponen carta boleh guna semula (presentational sahaja) | — |

**Sintaks DC → padanan Laravel:**

| DC | Maksud | Padanan Blade/Livewire |
|---|---|---|
| `<x-dc>` | Bekas komponen | Root Blade view |
| `<helmet>` | Suntik ke `<head>` | `@push('head')` / layout |
| `<sc-if value="{{ x }}">` | Syarat paparan | `@if($x) ... @endif` |
| `<sc-for list="{{ arr }}" as="i">` | Gelung | `@foreach($arr as $i)` |
| `<dc-import name="TrendChart" chart="{{ c }}">` | Import komponen | `<x-trend-chart :chart="$c" />` |
| `class Component extends DCLogic` | Logik + state | Livewire component |
| `renderVals()` | Kira semua nilai paparan | `render()` / computed properties |
| `this.setState({...})` | Kemas kini reaktif | Livewire public property |
| `style-hover="..."` | Gaya hover inline | Tailwind `hover:` |
| `hint-placeholder-count` / `hint-placeholder-val` | Petunjuk editor sahaja | **Abaikan** — bukan logik |

**Aset luaran yang digunakan (semua CDN — perlu self-host dalam Laravel):**

- Google Fonts: `Plus Jakarta Sans` (500,600,700,800) + `Inter` (400,500,600,700)
- Phosphor Icons Duotone: `https://unpkg.com/@phosphor-icons/web@2.1.1/src/duotone/style.css`
- Avatar keutamaan: `https://i.pravatar.cc/64?img={12|33|44}`
- Avatar profil lalai: `https://api.dicebear.com/7.x/avataaars/svg?seed=Ahmad-Nizam&backgroundColor=b6e3f4`
- Logo: `./assets/logo-dbena.png`

---

## 1. `TrendChart.dc.html` — Komponen Carta

### 1.1 Struktur UI (4 blok)

1. **Legend** (atas) — 2 item:
   - Petak `10×10px`, `border-radius:3px`, warna `oklch(0.6 0.22 350)` → "RM Jualan Sebenar / *Actual Sales*"
   - Bulatan `10×10px`, `border-radius:50%`, warna `oklch(0.78 0.12 85)` → "Sasaran / *Target*"
2. **Paksi-Y** (kiri) — 5 label bertaburan menegak: `maxLabel`, `threeQLabel`, `halfLabel`, `quarterLabel`, `0` (font 11px, `var(--t50)`)
3. **Kawasan plot** — `border-left` + `border-bottom` `1px solid var(--border2)`; mengandungi:
   - `<svg viewBox="0 0 1180 380" preserveAspectRatio="none">` overlay absolut, `pointer-events:none`
     - `<polyline points="{{ linePoints }}" stroke="oklch(0.78 0.12 85)" stroke-width="2.5" fill="none">` — garis sasaran
     - `<circle r="4.5" fill="var(--input-bg)" stroke="oklch(0.78 0.12 85)" stroke-width="2">` untuk setiap titik `dots`
   - Bar: setiap `bars[i]` → div `width:60%`, `border-radius:4px 4px 0 0`, `background:oklch(0.6 0.22 350)`, `height:{{ pctHeight }}`, `title="{{ valueLabel }}"` (tooltip). Hanya dilukis jika `b.hasValue` benar.
4. **Label paksi-X** (bawah) — `b.label` bagi setiap bar, `padding-left:34px` untuk selari dengan kawasan plot

### 1.2 Fungsi

| Fungsi | Logik |
|---|---|
| `renderVals()` | Terima `props.chart = {bars, dots, linePoints, maxLabel}`. Fallback `{bars:[], dots:[], linePoints:'', maxLabel:'0'}` |
| `maxNum` | `parseInt(String(maxLabel).replace(/,/g,'')) \|\| 0` |
| `fmt(n)` | `Math.round(n).toLocaleString('en-US')` |
| `threeQLabel` / `halfLabel` / `quarterLabel` | `fmt(maxNum × 0.75 / 0.5 / 0.25)` |

**Props yang diterima:** `bars[] {label, hasValue, pctHeight, valueLabel}`, `dots[] {x, y}`, `linePoints` (string "x,y x,y ..."), `maxLabel` (string)

**Nota Laravel:** komponen ini **murni presentational** — tiada pengiraan bisnes. Jadikan `<x-trend-chart>` Blade component; semua pengiraan kekal di Livewire/Service class induk.

---

## 2. `Login.dc.html` — Log Masuk Pengguna

### 2.1 Kelayakan hardcoded (WAJIB dibuang)

```
USERNAME       = 'dbena'
currentPassword= '••••••••'
ALLOWED_EMAIL  = 'dbenareport@gmail.com'
```

### 2.2 State (19 property)

`step`, `username`, `password`, `loginError`, `passwordVisible`, `otpInput`, `otpError`, `demoOtp`, `forgotEmail`, `forgotError`, `resetOtpInput`, `resetOtpError`, `demoResetOtp`, `newPassword`, `confirmPassword`, `resetPwError`, `currentPassword`, `toast`, `shakeKey`

### 2.3 Mesin keadaan `step` — 7 langkah

| `step` | Flag render | Ikon | Tajuk |
|---|---|---|---|
| `login` | `isLogin` | — | Log Masuk / *Sign In* |
| `otp` | `isOtp` | `ph-shield-check` 34px | Pengesahan OTP / *OTP Verification* |
| `forgot` | `isForgot` | `ph-key` 34px | Lupa Kata Laluan / *Forgot Password* |
| `resetOtp` | `isResetOtp` | `ph-shield-check` 34px | Kod Set Semula / *Reset Code* |
| `resetPassword` | `isResetPassword` | `ph-lock-key` 34px | Set Semula Kata Laluan / *Reset Password* |
| `success` | `isSuccess` | `ph-check-circle` 46px hijau | Log Masuk Berjaya / *Login Successful* |
| `resetSuccess` | `isResetSuccess` | `ph-check-circle` 46px hijau | Kata Laluan Dikemaskini / *Password Updated* |

### 2.4 Senarai penuh fungsi (21, tidak termasuk `renderVals()`)

| Fungsi | Tingkah laku |
|---|---|
| `genOtp()` | `String(Math.floor(100000 + Math.random()*900000))` — 6 digit |
| `showToast(msg)` | Set `toast`, auto-clear selepas **2600ms** |
| `shake()` | Naikkan `shakeKey` (cetus animasi) |
| `onUsernameChange(e)` | Set username, kosongkan `loginError` |
| `onPasswordChange(e)` | Set password, kosongkan `loginError` |
| `togglePasswordVisible()` | Tukar `passwordVisible` → tukar `type` input & ikon `ph-eye` ⇄ `ph-eye-slash` |
| `onLoginKeyDown(e)` | `Enter` → `submitLogin()` |
| `submitLogin()` | Kosong → "Sila isi username dan kata laluan."; salah → "Username atau kata laluan tidak sah."; betul → jana OTP, `step='otp'`, toast "OTP dihantar ke dbenareport@gmail.com" |
| `onOtpChange(e)` | Tapis bukan-nombor, potong 6 aksara |
| `onOtpKeyDown(e)` | `Enter` → `submitOtp()` |
| `submitOtp()` | Panjang ≠6 → "Masukkan kod 6-digit."; salah → "Kod OTP salah. Cuba lagi."; betul → `step='success'` |
| `resendOtp()` | Jana OTP baharu, kosongkan input, toast "OTP baharu dihantar..." |
| `goForgot()` | `step='forgot'`, reset medan |
| `backToLogin()` | `step='login'`, reset username/password/OTP |
| `onForgotEmailChange(e)` | Set email, kosongkan ralat |
| `submitForgot()` | Kosong → "Sila masukkan alamat emel."; bukan emel dibenarkan → "Emel tidak dikenali..."; betul → `step='resetOtp'` |
| `onResetOtpChange(e)` / `submitResetOtp()` | Sama corak dengan OTP login → `step='resetPassword'` |
| `onNewPasswordChange` / `onConfirmPasswordChange` | Set medan, kosongkan ralat |
| `submitResetPassword()` | `<6` aksara → "Kata laluan mesti sekurang-kurangnya 6 aksara."; tak padan → "Kata laluan tidak sepadan."; betul → `step='resetSuccess'`, tukar `currentPassword` (runtime sahaja) |

### 2.5 Elemen UI terperinci

- **Bekas luar:** `min-width:1440px`, `min-height:100vh`, bg `oklch(0.15 0.025 260)`, tengah menegak+melintang
- **Kad:** `width:440px`, bg `oklch(0.19 0.025 260)`, border `1px solid oklch(1 0 0/0.08)`, `border-radius:20px`, `padding:40px`, `box-shadow:0 24px 60px rgba(0,0,0,0.5)`, `animation:{{ shakeAnim }}`
- **Kotak logo:** border `1px solid oklch(0.78 0.12 85/0.4)`, `border-radius:14px`, `padding:16px 12px`, bg **putih** `oklch(0.97 0.005 260)`, `margin-bottom:28px`
- **Input standard:** bg `oklch(0.15 0.02 260)`, border `1px solid oklch(1 0 0/0.1)`, `border-radius:10px`, `padding:12px 14px`, font 14px
- **Input OTP:** `text-align:center`, `letter-spacing:10px`, font 22px/700 Plus Jakarta Sans, `padding:14px`, `maxlength=6`
- **Butang utama:** bg `oklch(0.78 0.12 85)`, teks `oklch(0.15 0.02 260)`, `padding:13px`, `border-radius:10px`, 14px/700, hover `filter:brightness(1.08)`
- **Kotak ralat:** bg `oklch(0.6 0.2 25/0.12)`, border `1px solid oklch(0.6 0.2 25/0.35)`, `border-radius:9px`, ikon `ph-warning-circle`
- **Kotak demo OTP:** bg `oklch(0.78 0.12 85/0.1)`, border **dashed** `oklch(0.78 0.12 85/0.4)` — ⚠️ **WAJIB DIBUANG dalam produksi**
- **Toast:** `position:fixed; bottom:28px; right:32px`, bg `oklch(0.22 0.03 260)`, border emas, `animation:toastIn 0.25s ease`, ikon `ph-check-circle` hijau
- **Keyframes:** `shake` (translateX −1/2/−4/4px, 0.4s) & `toastIn` (opacity 0→1, translateY 12px→0)
- **Pautan keluar:** butang kejayaan → `./Executive Dashboard.dc.html`

---

## 3. `Admin Login.dc.html` — Log Masuk Admin

**Struktur logik 100% identik dengan `Login.dc.html`** (semua 21 fungsi & 7 langkah sama). Perbezaan:

| Aspek | Login User | Login Admin |
|---|---|---|
| Username | `dbena` | `DBENASB` |
| Kata laluan | `••••••••` | `••••••••` |
| Susun atur | Kad 440px di tengah | **Split-panel**: kiri 46%, kanan `flex:1` |
| Latar utama | `oklch(0.15 0.025 260)` | `oklch(0.12 0.01 25)` |
| Panel borang | dalam kad | bg `oklch(0.14 0.015 25)`, lebar borang 400px |
| Warna aksen | Emas `oklch(0.78 0.12 85)` | Merah `oklch(0.68 0.19 25)` |
| Warna butang | `oklch(0.78 0.12 85)` | `oklch(0.55 0.19 25)`, teks `oklch(0.98 0.005 25)` |
| Input bg / border | `oklch(0.15 0.02 260)` / `oklch(1 0 0/0.1)` | `oklch(0.19 0.02 25)` / `oklch(0.6 0.2 25/0.3)` |
| Hover butang | `brightness(1.08)` | `brightness(1.1)` |
| Logo | Ada (kotak putih) | **Tiada** |
| Tajuk rata | `text-align:center` | Rata kiri |
| Destinasi kejayaan | `Executive Dashboard.dc.html` | `Admin Panel.dc.html` |
| Toast | bg `oklch(0.22 0.03 260)` border emas | bg `oklch(0.22 0.03 25)` border merah |

### 3.1 Panel jenama kiri (unik pada Admin Login)

- `width:46%`, bg `oklch(0.16 0.03 25)`, `border-right:1px solid oklch(0.6 0.2 25/0.3)`, `padding:60px 70px`
- **Badge "RESTRICTED ACCESS"** — absolut `top:32px; left:70px`, pill `border-radius:20px`, bg `oklch(0.6 0.2 25/0.15)`, border `oklch(0.6 0.2 25/0.4)`, ikon `ph-lock-key`, font 11px/700 `letter-spacing:1px`
- **Kotak ikon amaran** — `64×64px`, `border-radius:16px`, bg `oklch(0.6 0.2 25/0.16)`, ikon `ph-shield-warning` 32px
- **Tajuk** — "Admin Panel" (34px/800 Plus Jakarta Sans) + baris kedua "DBENA SDN BHD" warna merah
- **Perenggan dwibahasa** — "Kawasan ini dikhaskan untuk kakitangan diberi kuasa sahaja..." + baris EN italik, `max-width:380px`
- **2 chip ciri** — `ph-key` "Two-Factor OTP" · `ph-envelope-simple` "dbenareport@gmail.com"

---

## 4. `Admin Panel.dc.html` — Konfigurasi Induk

### 4.1 Data lalai (`defaults`) — nilai tepat untuk seeder

**Services (5):**

| key | name | nameEn | iconClass | target (RM) |
|---|---|---|---|---|
| `renovation` | Renovation | Ubah Suai | `ph-wrench` | 500,000 |
| `kabinet` | Kabinet | Cabinetry | `ph-squares-four` | 200,000 |
| `bina-rumah` | Bina Rumah | House Construction | `ph-house-line` | 500,000 |
| `divider` | Divider | Pembahagi | `ph-columns` | 40,000 |
| `mihrab` | Mihrab | *(kosong)* | `ph-bank` | 80,000 |

> ⚠️ **Isu:** lajur `nameEn` untuk `renovation` ("Ubah Suai") dan `divider` ("Pembahagi") sebenarnya **Bahasa Melayu**, bukan English — terbalik. `mihrab` pula kosong. Perlu dibetulkan.

**Index Tiers (5):**

| key | nameMy | name | color | monthlyRevenue | monthlyProfit |
|---|---|---|---|---|---|
| `critical` | Kritikal | Critical | `oklch(0.6 0.2 25)` | 0 | 0 |
| `survival` | Bertahan | Survival | `oklch(0.75 0.15 70)` | 457,142.86 | 0 |
| `growing` | Berkembang | Growing | `oklch(0.62 0.16 300)` | 685,714.29 | 80,000 |
| `stable` | Stabil | Stable | `oklch(0.6 0.15 235)` | 914,285.71 | 160,000 |
| `sustainability` | Mampan | Sustainability | `oklch(0.65 0.16 150)` | 1,371,428.57 | 320,000 |

**Owners lalai:** `['ZIKRI', 'HAFIZAN', 'NIZAM', 'AZHARI']` — 4 PIC teras (tidak boleh dibuang di UI)

**Year Growth Factors:**

| 2023 | 2024 | 2025 | 2026 | 2027 | 2028 | 2029 | 2030 | 2031 | 2032 |
|---|---|---|---|---|---|---|---|---|---|
| 0.58 | 0.72 | 0.87 | **1.0** | 1.15 | 1.3 | 1.45 | 1.6 | 1.75 | 1.9 |

**sheetUrl:** `''` (kosong)

### 4.2 Struktur UI (6 blok)

1. **Topbar sticky** `height:76px` — kotak ikon `ph-shield-check` 38×38 bg `oklch(0.78 0.12 85/0.16)`; tajuk "ADMIN PANEL" 19px/800 emas `letter-spacing:0.5px`; subtajuk "Konfigurasi Dashboard DBENA / *Dashboard Configuration*"; kanan: pautan **Lihat Dashboard** (`ph-gauge`) & **Log Keluar** (`ph-sign-out`, border merah)
2. **Bar Simpan** — kad border emas `oklch(0.78 0.12 85/0.3)` + teks nota + butang **Simpan Semua / Save All** (`ph-floppy-disk`)
3. **Kad Servis & Sasaran** — grid `1.4fr 1.4fr 1fr`; lajur: Nama Servis (ikon + teks, read-only) · Nama (English) (input) · Sasaran Bulanan RM (input, teks hijau `oklch(0.72 0.15 145)`)
4. **Kad Index Tier** — grid `1.3fr 1fr 1fr`; lajur: Tahap (petak warna 9×9 + nameMy + `/ name` italik) · Revenue Bulanan (input) · Untung Bersih Bulanan (input)
5. **Kad Pemilik Data (PIC)** — chip pill `border-radius:20px`, bg `color-mix(in oklch, {color} 16%, transparent)`, border 40%; ikon `ph-x-circle` untuk buang (hanya jika `removable`); di bawah: input nama baharu (`text-transform:uppercase`, placeholder "cth. FARID") + butang **Tambah** (`ph-plus`)
6. **Kad Faktor Pertumbuhan** — grid `repeat(5,1fr)`, label tahun di atas, input `text-align:center`
7. **Kad Google Sheet Lalai** — satu input URL penuh, placeholder `https://docs.google.com/spreadsheets/d/...`
8. **Toast** — sama gaya dengan Login

**Bekas kandungan:** `padding:28px 32px 80px`, `max-width:1200px`, `gap:24px`
**Kad standard:** bg `oklch(0.19 0.025 260)`, border `1px solid oklch(1 0 0/0.08)`, `border-radius:16px`, `padding:24px`

### 4.3 Fungsi (9, tidak termasuk `renderVals()`)

| Fungsi | Logik |
|---|---|
| `showToast(msg)` | 2600ms auto-clear |
| `updateServiceField(key, field, value)` | Map immutable pada array `services` |
| `updateTierField(key, field, value)` | Map immutable pada `indexTiers` |
| `updateNewOwnerName(v)` | Set input |
| `addOwner()` | Trim + `toUpperCase()`; kosong → toast "Sila masukkan nama pemilik / Enter an owner name"; duplikasi → "Pemilik sudah wujud / Owner already exists" |
| `removeOwner(name)` | `filter(o => o !== name)` — ⚠️ **tiada guard backend**, hanya butang disorok di UI |
| `updateYearFactor(year, value)` | Merge objek |
| `updateSheetUrl(v)` | Set |
| `saveAll()` | `parseFloat` semua nilai numerik → `localStorage.setItem('dbena_admin_settings', JSON.stringify(payload))` → toast "Semua tetapan disimpan / All settings saved" |

### 4.4 Palet warna PIC (Admin Panel) — ⚠️ TIDAK KONSISTEN dengan Dashboard

```js
ownerColors = ['oklch(0.6 0.15 250)','oklch(0.7 0.12 85)','oklch(0.6 0.16 350)',
               'oklch(0.6 0.15 145)','oklch(0.62 0.15 30)','oklch(0.62 0.15 190)']
// diberikan mengikut INDEX dalam senarai (ownerColors[i % length])
```

Berbanding Dashboard yang guna **peta nama tetap + hash** (lihat §5.6). Akibatnya PIC yang sama boleh berlainan warna antara dua skrin apabila susunan berubah.

### 4.5 Guard `removable`

```js
removable: !['ZIKRI','HAFIZAN','NIZAM','AZHARI'].includes(name)
```

⚠️ `INFO` **tidak** ada dalam senarai Admin Panel walaupun digunakan sebagai pemilik dalam Data Kritikal Dashboard — ketidakselarasan data.

---

## 5. `Executive Dashboard.dc.html` — Aplikasi Utama

Satu komponen mengandungi **4 halaman** yang ditukar melalui `state.page`.

### 5.1 State penuh (31 property)

| Property | Lalai | Guna |
|---|---|---|
| `page` | `'dashboard'` | Halaman aktif: `dashboard` / `service` / `laporan` / `tetapan` |
| `activeServiceKey` | `null` | Servis dipapar |
| `period` | `'Mingguan / Weekly'` | Dropdown period |
| `periodOpen` | `false` | Dropdown period buka |
| `notifOpen` | `false` | Dropdown notifikasi buka |
| `profileOpen` | `false` | Dropdown profil buka |
| `dateOpen` | `false` | Dropdown tahun buka |
| `dashboardYear` | `2026` | Tahun dipilih |
| `notifRead` | `false` | Badge merah notifikasi |
| `toast` | `null` | Mesej toast |
| `darkOn` | `true` | **Dark mode lalai AKTIF** |
| `editingProfile` | `false` | ⚠️ dead — UI dibuang |
| `criticalMonth` | `'Jul'` | Bulan Data Kritikal |
| `criticalActionPlans` | `{}` | Peta `svKey::metric` → teks pelan |
| `dashboardMonthIndex` | `6` (Julai) | Bulan dashboard |
| `criticalOwners` | `{}` | Peta `svKey::metric` → nama PIC |
| `criticalWeekValues` | `{}` | Peta `svKey::metric::w{0-3}` → nilai |
| `serviceViewMode` | `'monthly'` | `monthly` / `yearly` |
| `ownerFilter` | `null` | Penapis PIC |
| `laporanServiceFilter` | `null` | Penapis servis Laporan |
| `laporanFilterOpen` | `false` | Dropdown penapis |
| `laporanDateOpen` | `false` | Dropdown tarikh Laporan |
| `sheetModalOpen` | `false` | ⚠️ dead — sentiasa false |
| `rawDataOpen` | `false` | ⚠️ dead — bersarang dalam `sheetModalOpen` |
| `sheetUrl` | `''` | ⚠️ dead — UI dibuang |
| `sheetConnected` | `false` | ⚠️ separa — hanya jana notifikasi |
| `lastSynced` | `null` | ⚠️ separa |
| `addOwnerModalOpen` | `false` | ⚠️ dead — UI dibuang |
| `newOwnerName` | `''` | ⚠️ dead |
| `customOwners` | `[]` | PIC tambahan (runtime, hilang bila refresh) |
| `settings` | objek | `{name:'Ahmad Nizam', role:'Managing Director', email:'ahmad.nizam@dbena.com.my', phone:'012-345 6789', notifEmail:true, notifWeekly:true, notifSound:false, avatarUrl:'dicebear...'}` |

### 5.2 Data konstruktor (data statik)

**`months`** (12): `Jan, Feb, Mac, Apr, Mei, Jun, Jul, Ogo, Sep, Okt, Nov, Dis`
**`monthsFull`**: `Januari … Disember`
**`monthsEn`**: `January … December` ⚠️ *diisytihar tetapi tidak pernah digunakan*
**`yearOptions`**: `[2023 … 2032]`
**`yearGrowthFactor`**: sama seperti Admin Panel §4.1
**`indexTiers`**: sama seperti Admin Panel §4.1

**`periodConfig`** — ⚠️ **`mult` dikira tetapi TIDAK PERNAH digunakan dalam mana-mana formula**

| Period | mult | prevLabel |
|---|---|---|
| Mingguan / Weekly | 1 | vs minggu lalu / vs last week |
| Bulanan / Monthly | 4.33 | vs bulan lalu / vs last month |
| Suku Tahunan / Quarterly | 13 | vs suku lalu / vs last quarter |

**`services`** — sama dengan Admin Panel, **tambah** medan `sales`:

| key | sales | target |
|---|---|---|
| renovation | 512,480 | 500,000 |
| kabinet | 286,940 | 200,000 |
| bina-rumah | 198,500 | 500,000 |
| divider | 102,350 | 40,000 |
| mihrab | 71,006 | 80,000 |

**`projectsByService`** — 16 projek demo (⚠️ **tiada jadual projek dalam markup** — data hanya dipakai untuk kiraan `avgProjectValue` & `projectCount`):

| Servis | Bil. | Contoh |
|---|---|---|
| renovation | 4 | Rumah Taman Melawati (RM68k, Selesai), Kondominium Mont Kiara (RM92k), Rumah Banglo Bangi (RM145k), Apartmen Cheras (RM54k) |
| kabinet | 4 | Kabinet Dapur Puchong (RM18.5k), Kabinet Bilik Subang (RM24.2k), Kabinet Pejabat Shah Alam (RM31.5k), Kabinet Dapur Klang (RM15.8k) |
| bina-rumah | 3 | Banglo Setia Alam (RM850k), Rumah Teres Rawang (RM320k), Rumah Kluster Semenyih (RM410k) |
| divider | 3 | Pembahagi Ruang Tetamu PJ (RM8.5k), Pembahagi Pejabat KL (RM14.2k), Pembahagi Rumah Ampang (RM6.9k) |
| mihrab | 2 | Mihrab Masjid Ampang (RM38k), Mihrab Surau Bangi (RM22k) |

Medan projek: `name`, `client`, `value`, `status`, `date`
Status yang wujud: `Selesai` (hijau `oklch(0.72 0.15 145)`), `Dalam Proses` (oren `oklch(0.75 0.14 70)`), `Menunggu Kelulusan` (biru `oklch(0.65 0.15 250)`), `Perancangan` (kelabu `var(--t60)`)
**Jumlah projek keseluruhan: 16**

**`criticalData`** — baris metrik per servis (⚠️ bilangan baris **berbeza** ikut servis):

| Metrik | renovation | kabinet | bina-rumah | mihrab | divider |
|---|---|---|---|---|---|
| Sales Collection (New) | RM150,000 · ZIKRI | RM60,000 · ZIKRI | RM150,000 · ZIKRI | RM40,000 · ZIKRI | RM20,000 · ZIKRI |
| Revenue/Sales | RM500,000 · ZIKRI | RM200,000 · ZIKRI | RM500,000 · ZIKRI | RM80,000 · ZIKRI | RM40,000 · ZIKRI |
| Sales Collection (Progress Claim) | `Progress` · HAFIZAN | `Progress` · ZIKRI | `Progress` · HAFIZAN | `Progress` · ZIKRI | `Progress` · ZIKRI *(actual RM4,890)* |
| Amount Quotation Release (New) | RM2,400,000 · HAFIZAN | RM1,000,000 · ZIKRI | RM2,500,000 · HAFIZAN | RM400,000 · ZIKRI | RM200,000 · ZIKRI |
| No of New Quotation | 16 · HAFIZAN | 30 · ZIKRI | 20 · HAFIZAN | 11 · ZIKRI | 16 · ZIKRI |
| No of Site Visit | 24 · ZIKRI | 40 · ZIKRI | — | 16 · ZIKRI | **tiada** |
| No of Appointment (Offline / Online) | — | — | 30 · HAFIZAN | — | — |
| Ads Spend | RM6,000 · ZIKRI | RM6,000 · ZIKRI | RM4,500 · ZIKRI | RM1,200 · ZIKRI | RM1,200 · ZIKRI |
| No of Lead | 600 · ZIKRI | 400 · ZIKRI | 300 · ZIKRI | 80 · ZIKRI | 80 · ZIKRI |
| Cost Per Lead (CPL) | RM10 · INFO | RM15 · INFO | RM15 · INFO | RM15 · INFO | RM15 · INFO |
| Cost Per Appointment (CPA) | RM250 · INFO | RM150 · INFO | RM150 · INFO | RM75 · INFO | — |
| Cost Per Quotation (CPQ) | — | — | — | — | RM75 · INFO |
| **Jumlah baris** | **10** | **10** | **10** | **10** | **9** |

Medan setiap baris: `metric`, `type` (`Total` / `Avg`), `actual`, `target`, `status`, `owner`
Semua `actual` = `RM0.00` atau `0` **kecuali** divider · Sales Collection (Progress Claim) = `RM4,890.00`
Semua `status` awal = `Red`
Target `'Progress'` adalah **bukan angka** → `parseNum` pulangkan `null` → status kekal Red

**`priorities`** (3):

| serviceKey | owner | avatar | desc | descEn |
|---|---|---|---|---|
| kabinet | Ahmad Hafiz | 12 | Tingkatkan pencapaian kepada 35% ke atas. | Increase achievement to above 35%. |
| bina-rumah | Mohd Amirul | 33 | Tutup sekurang-kurangnya 3 projek baharu. | Close at least 3 new projects. |
| mihrab | Nurul Farah | 44 | Bina momentum kutipan untuk minggu ini. | Build collection momentum for this week. |

**`notifications`** (4) — ⚠️ **DIISYTIHAR TETAPI TIDAK PERNAH DIGUNAKAN** (digantikan `liveNotifications` dinamik):
`ph-user-plus` "5 lead baharu diterima pagi ini" (10 minit lalu) · `ph-file-text` "Sebut harga #Q-2291 menunggu kelulusan" (1 jam lalu) · `ph-wallet` "Kutipan RM45,000 diterima — Taman Melawati" (3 jam lalu) · `ph-warning-circle` "Kabinet: pencapaian di bawah 30%" (Semalam)

**`menuBase`** (8 item sidebar):

| key | label | labelEn | icon | page |
|---|---|---|---|---|
| dashboard | Dashboard Utama | Main Dashboard | `ph-house` | dashboard |
| renovation | Renovation | Ubah Suai | `ph-wrench` | service |
| kabinet | Kabinet | Cabinetry | `ph-squares-four` | service |
| bina-rumah | Bina Rumah | House Construction | `ph-house-line` | service |
| divider | Divider | Pembahagi | `ph-columns` | service |
| mihrab | Mihrab | *(kosong)* | `ph-bank` | service |
| laporan | Laporan | Reports | `ph-chart-bar` | laporan |
| tetapan | Tetapan | Settings | `ph-gear` | tetapan |

### 5.3 Sistem tema — `buildThemeVars(dark)`

Menjana string CSS custom properties inline. **26 pemboleh ubah.**

**Latar/permukaan (9):** `--bg`, `--sidebar-bg`, `--card-bg`, `--hover-bg`, `--hover-bg2`, `--hover-bg3`, `--track-bg`, `--input-bg`, `--switch-off`

| Var | Dark | Light |
|---|---|---|
| `--bg` | `oklch(0.15 0.025 260)` | `oklch(0.97 0.008 260)` |
| `--sidebar-bg` | `oklch(0.13 0.025 260)` | `oklch(0.995 0.003 260)` |
| `--card-bg` | `oklch(0.19 0.025 260)` | `oklch(1 0 0)` |
| `--hover-bg` | `oklch(0.22 0.025 260)` | `oklch(0.93 0.012 260)` |
| `--hover-bg2` | `oklch(0.24 0.03 260)` | `oklch(0.9 0.02 260)` |
| `--hover-bg3` | `oklch(0.2 0.025 260)` | `oklch(0.95 0.01 260)` |
| `--track-bg` | `oklch(0.28 0.02 260)` | `oklch(0.88 0.012 260)` |
| `--input-bg` | `oklch(0.15 0.02 260)` | `oklch(0.96 0.01 260)` |
| `--switch-off` | `oklch(0.32 0.02 260)` | `oklch(0.82 0.012 260)` |

**Teks (14):** `--t96`, `--t94`, `--t92`, `--t90`, `--t85`, `--t80`, `--t75`, `--t70`, `--t68`, `--t65`, `--t60`, `--t55`, `--t50`, `--t40`

| Var | Dark | Light |
|---|---|---|
| `--t96` | `oklch(0.96 0.01 260)` | `oklch(0.2 0.02 260)` |
| `--t94` | `oklch(0.94 0.01 260)` | `oklch(0.22 0.02 260)` |
| `--t92` | `oklch(0.92 0.01 260)` | `oklch(0.24 0.02 260)` |
| `--t90` | `oklch(0.9 0.01 260)` | `oklch(0.27 0.02 260)` |
| `--t85` | `oklch(0.85 0.01 260)` | `oklch(0.32 0.02 260)` |
| `--t80` | `oklch(0.8 0.01 260)` | `oklch(0.36 0.02 260)` |
| `--t75` | `oklch(0.75 0.02 260)` | `oklch(0.4 0.02 260)` |
| `--t70` | `oklch(0.7 0.02 260)` | `oklch(0.44 0.02 260)` |
| `--t68` | `oklch(0.68 0.02 260)` | `oklch(0.46 0.02 260)` |
| `--t65` | `oklch(0.65 0.02 260)` | `oklch(0.48 0.02 260)` |
| `--t60` | `oklch(0.6 0.02 260)` | `oklch(0.52 0.02 260)` |
| `--t55` | `oklch(0.55 0.02 260)` | `oklch(0.55 0.02 260)` |
| `--t50` | `oklch(0.5 0.02 260)` | `oklch(0.58 0.02 260)` |
| `--t40` | `oklch(0.4 0.02 260)` | `oklch(0.62 0.02 260)` |

**Sempadan (3):**

| Var | Dark | Light |
|---|---|---|
| `--border` | `oklch(1 0 0/0.08)` | `oklch(0 0 0/0.1)` |
| `--border2` | `oklch(1 0 0/0.1)` | `oklch(0 0 0/0.13)` |
| `--border3` | `oklch(1 0 0/0.06)` | `oklch(0 0 0/0.07)` |

> ⚠️ **a11y:** `--t55` bernilai sama (`0.55`) pada dark & light — kontras rendah pada tema terang. Perlu semak WCAG AA.

### 5.4 Warna tetap (tidak ikut tema)

| Guna | Nilai |
|---|---|
| Emas jenama | `oklch(0.78 0.12 85)` |
| Pink/magenta (bar carta, ikon) | `oklch(0.6 0.22 350)` / `oklch(0.55 0.22 350)` |
| Hijau kejayaan | `oklch(0.72 0.15 145)` |
| Status Green | `oklch(0.55 0.15 145)` |
| Status Yellow | `oklch(0.78 0.15 85)` |
| Status Red | `oklch(0.55 0.2 25)` |
| Belum Update | `oklch(0.6 0.02 260)` |
| Amaran/kritikal | `oklch(0.6 0.2 25)` |
| Status "Perlu Dipertingkat" | `oklch(0.75 0.14 70)` |
| Log keluar | `oklch(0.75 0.1 20)` |
| Scrollbar thumb | `oklch(0.35 0.02 260)` |

**Warna servis untuk carta bertindan (`serviceColors`):**

| Servis | Warna |
|---|---|
| renovation | `oklch(0.6 0.2 350)` |
| kabinet | `oklch(0.75 0.15 85)` |
| bina-rumah | `oklch(0.6 0.16 250)` |
| divider | `oklch(0.65 0.15 145)` |
| mihrab | `oklch(0.7 0.16 40)` |

### 5.5 Senarai PENUH fungsi (47, tidak termasuk `renderVals()`)

**Navigasi & dropdown (9)**

| Fungsi | Logik |
|---|---|
| `navigate(page, serviceKey)` | Set page + tutup SEMUA dropdown |
| `togglePeriod()` | Buka period, tutup lain |
| `selectPeriod(p)` | Set period, tutup |
| `toggleNotif()` | Buka notif, tutup lain, set `notifRead = true` |
| `toggleProfile()` | Buka profil, tutup lain |
| `toggleDate()` | Buka dropdown tahun, tutup lain |
| `selectDashboardYear(year)` | Set tahun, tutup |
| `selectDashboardMonth(idx)` | Set indeks bulan, tutup dropdown tarikh |
| `doLogout()` | Tutup profil + toast "Log keluar (demo)" — ⚠️ **tiada logout sebenar** |

**Utiliti format (4)**

| Fungsi | Logik |
|---|---|
| `fmtRM(n)` | `'RM' + Math.round(n).toLocaleString('en-US')` |
| `fmtNum(n)` | `Math.round(n).toLocaleString('en-US')` |
| `parseNum(str)` | `parseFloat(String(str).replace(/[^0-9.]/g,''))`, `null` jika NaN |
| `showToast(msg)` | 2600ms auto-clear |

**Tema (2)**

| `buildThemeVars(dark)` | Jana 26 CSS var (§5.3) |
| `toggleDark()` | Tukar `darkOn` |

**Data Kritikal (6)**

| Fungsi | Logik |
|---|---|
| `selectCriticalMonth(m)` | Set bulan |
| `updateActionPlan(key, value)` | Merge `criticalActionPlans[svKey::metric]` |
| `updateCriticalOwner(key, value)` | Merge `criticalOwners[svKey::metric]` |
| `updateWeekValue(ck, weekIdx, value)` | Merge `criticalWeekValues[ck::w{0-3}]` |
| `computeCriticalActual(s, svKey, r)` | Jumlah minggu 1-4; jika ada input → format RM/nombor; jika tiada → guna `r.actual` asal |
| `toggleOwnerFilter(name)` | Toggle penapis PIC (klik semula = clear) |

**Kalendar minggu (2)**

| Fungsi | Logik |
|---|---|
| `getMonthWeeks(monthIndex, year)` | Cari **semua hari Khamis** dalam bulan. `w1end = thursdays[1]`, `w2end = thursdays[2]`, `w3end = thursdays[3]`, fallback `daysInMonth`. Pulangkan `[[1,w1end],[w1end+1,w2end],[w2end+1,w3end],[w3end+1,daysInMonth]]` |
| `getCriticalWeekLabels(monthIndex, year)` | `'Minggu {n}\n{DD}/{MM}-{DD}/{MM}'` (pad 2 digit) |

**Carta (2)**

| Fungsi | Logik |
|---|---|
| `buildChart(actualArr7, targetTotal)` | `max = targetTotal`. 12 bar (indeks 0-6 ada nilai, 7-11 null). `pctHeight = min(100, v/max×100)%`. Dots: `x = i/11×1160+20`, `y = (1 − ((max×(i+1)/12)/max))×340+20` |
| `buildYearlyChart(baseSales, baseTarget)` | 10 tahun. `actual[y] = baseSales × factor[y] × 12`, `target[y] = baseTarget × factor[y] × 12`. `max = Math.max(...actuals, ...targets)`. `x = i/9×1160+20`, `y = (1 − target/max)×340+20` |

**Google Sheet (7 — 6 daripadanya DEAD)**

| Fungsi | Status |
|---|---|
| `openGoogleSheet()` | ✅ **AKTIF** — jika `adminSheetUrl` kosong → toast "Belum ada pautan Google Sheet..."; jika ada → `window.open(url, '_blank')` |
| `toggleSheetModal()` | ⚠️ dead — tiada pemanggil dalam markup |
| `toggleRawData()` | ⚠️ dead — markup bersarang dalam `sheetModalOpen` yang sentiasa `false` |
| `updateSheetUrl(v)` | ⚠️ dead |
| `connectSheet()` | ⚠️ dead — set `sheetConnected=true`, `lastSynced=now`, toast |
| `syncSheetNow()` | ⚠️ dead — set `lastSynced=now`, toast |
| `disconnectSheet()` | ⚠️ dead — reset semua, toast |

**PIC tersuai (3 — semua DEAD)**

| `toggleAddOwnerModal()` · `updateNewOwnerName(v)` · `addCustomOwner()` | ⚠️ Logik lengkap (validasi kosong, semak duplikasi terhadap `['ZIKRI','HAFIZAN','NIZAM','AZHARI','INFO', ...customOwners]`, toast "Pemilik ... ditambah") tetapi **tiada modal dalam markup** |

**Profil & Tetapan (6 — 4 daripadanya DEAD)**

| Fungsi | Status |
|---|---|
| `toggleSettingSwitch(field)` | ✅ AKTIF — toggle `notifEmail`/`notifWeekly`/`notifSound` |
| `saveSettings()` | ✅ AKTIF tapi **palsu** — hanya toast "Tetapan berjaya disimpan", tidak simpan apa-apa |
| `toggleEditProfile()` | ⚠️ dead — jika sedang edit → toast "Profil berjaya dikemaskini" |
| `updateSettingField(field, value)` | ⚠️ dead (handler `onNameChange`/`onRoleChange`/`onEmailChange`/`onPhoneChange` wujud, input tiada) |
| `onAvatarFileChange(e)` | ⚠️ dead — `FileReader.readAsDataURL` → simpan base64 ke state (bukan storan sebenar) |
| `exportReport()` | ✅ AKTIF tapi **palsu** — hanya toast "Laporan sedang dieksport..." |

**Lain-lain (3)**

| `applyAdminSettings()` | ✅ Baca `localStorage['dbena_admin_settings']`; timpa `services.nameEn`+`target`, kemas kini baris `Revenue/Sales` dalam `criticalData`, timpa `indexTiers` threshold, merge `yearGrowthFactor`, set `adminSheetUrl`, tapis owner tambahan ke `customOwners` |
| `getOwnerColor(name)` | Peta tetap: ZIKRI `oklch(0.6 0.15 250)`, HAFIZAN `oklch(0.7 0.12 85)`, NIZAM `oklch(0.6 0.16 350)`, AZHARI `oklch(0.6 0.15 145)`. Nama lain → hash `(hash×31 + charCode) % 4` pada palet `['oklch(0.62 0.15 30)','oklch(0.62 0.15 190)','oklch(0.62 0.15 100)','oklch(0.62 0.15 320)']` |
| `setServiceViewMode(mode)` | `monthly` / `yearly` |
| `stopProp(e)` | `e.stopPropagation()` untuk modal |

### 5.6 Formula bisnes penuh (dalam `renderVals()`)

```
// 1. Jualan efektif — override dari Data Kritikal jika ada input minggu
getEffectiveSales(sv) = parseNum(computeCriticalActual(Revenue/Sales row)) ?? sv.sales

// 2. Agregat + faktor tahun
yearFactor    = yearGrowthFactor[dashboardYear] ?? 1
rawBaseSales  = Σ effectiveServices.sales
rawBaseTarget = Σ effectiveServices.target
totalSales    = rawBaseSales  × yearFactor
totalTarget   = rawBaseTarget × yearFactor

// 3. Taburan kumulatif 7 bulan (HARDCODED)
baseActualRatios   = [120000, 260000, 410000, 570000, 740000, 950000, 1172276]
scale              = totalSales / 1172276
cumActualByMonth   = baseActualRatios.map(v => v × scale)
overallMonthlyDelta[i] = i===0 ? cum[0] : cum[i] − cum[i−1]
cumTargetByMonth[i]    = totalTarget × (i+1)

// 4. Nilai bulan dipilih
isProjectedMonth = dashboardMonthIndex > 6        // Ogos ke atas = unjuran
isYearlyOverall  = serviceViewMode === 'yearly'
monthActual = isYearlyOverall
              ? (isProjectedMonth ? cumActual[6] : cumActual[dmIdx])
              : (isProjectedMonth ? 0            : monthlyDelta[dmIdx])
monthTarget = isYearlyOverall ? cumTargetByMonth[dmIdx] : totalTarget
overallPct     = monthTarget ? monthActual / monthTarget × 100 : 0
changeVsTarget = monthTarget ? (monthActual − monthTarget) / monthTarget × 100 : 0

// 5. Untung & tier
estProfitMargin = 0.18
estProfit       = monthActual × 0.18
threshold(tier) = isYearlyOverall ? tier.monthlyRevenue × 12 : tier.monthlyRevenue
currentTierIdx  = indeks TERTINGGI di mana monthActual >= threshold
tierWidthPct(i) = 100 − i×16        // 100%, 84%, 68%, 52%, 36% — piramid
(paparan senarai tier di-REVERSE — Sustainability di atas)

// 6. Baris servis
share            = totalSales ? (sv.sales × yearFactor) / totalSales : 0
svCumByMonth[i]  = cumActualByMonth[i] × share
svMonthlyDelta[i]= i===0 ? svCum[0] : svCum[i] − svCum[i−1]
svTargetScaled   = sv.target × yearFactor
// mod tahunan
displayActual = dmIdx<=6 ? svCum[dmIdx] : svCum[6];  displayTarget = svTargetScaled × 12
// mod bulanan
displayActual = dmIdx<=6 ? svDelta[dmIdx] : null;    displayTarget = svTargetScaled
pct    = displayActual!=null && displayTarget ? displayActual/displayTarget×100 : 0
good   = pct >= 35        // AMBANG STATUS SERVIS
status = good ? 'Memuaskan / Satisfactory' : 'Perlu Dipertingkat / Needs Improvement'
statusColor = good ? oklch(0.72 0.15 145) : oklch(0.75 0.14 70)
barColor    = good ? oklch(0.72 0.15 145) : oklch(0.6 0.22 350)

// 7. Status metrik kritikal (per baris)
hasMonthData = criticalMonth === 'Jul'    // ⚠️ HARDCODED — hanya Julai ada data
weekSum      = Σ parseNum(week1..week4)
rawActual    = ada input minggu ? (isCurrency ? fmtRM(weekSum) : String(weekSum)) : r.actual
pct          = actualNum/targetNum×100 (null jika mana-mana null / target<=0)
if      (!hasMonthData)        status='Belum Update'  color=oklch(0.6 0.02 260)
else if (pct !== null && >=100) status='Green'        color=oklch(0.55 0.15 145)
else if (actionPlan.trim())     status='Yellow'       color=oklch(0.78 0.15 85)
else                            status='Red'          color=oklch(0.55 0.2 25)

// 8. Sasaran mingguan
weeklyQuotationTarget = ceil(parseInt(qRow.target) / 4)
weeklySiteVisitTarget = ceil(parseInt(svRow.target) / 4)
weeklyAmountTarget    = round(parseNum(amtRow.target) / 4)
perWeek(bar)          = monthlyActual / 4
barColor: pct>=100 → oklch(0.55 0.15 145); pct>=50 → oklch(0.78 0.15 85); else oklch(0.6 0.22 350)
// Kad Actual-vs-Target guna: pct>=100 hijau; >=50 kuning; <50 oklch(0.55 0.2 25)

// 9. Prestasi PIC
ownerNames = unik(criticalRows.owner) TOLAK 'INFO'
scorePct   = total ? round(greenCount / total × 100) : 0
barColor   = scorePct>=70 hijau; >=40 kuning; else merah
hasCritical= redCount > 0

// 10. Analisis servis
gap             = max(0, currentService.target − currentService.sales)
monthsLeft      = 5        // ⚠️ HARDCODED
avgProjectValue = projectCount ? currentService.sales / projectCount : 0
requiredRunRate = gap / monthsLeft   // dipapar '{RM}/bln'

// 11. Override kad servis daripada baris Revenue/Sales
revActual = hasMonthData ? parseNum(revRow.actual) : 0
revTarget = parseNum(revRow.target) ?? currentService.target
revPct    = min(999, revActual/revTarget×100)     // had 999%
barColor  = revPct>=100 hijau : pink

// 12. Carta bertindan (7 bulan pertama sahaja)
monthTotals[i]  = Σ serviceRows.monthlyDelta[i]
maxMonthTotal   = max(monthTotals) || 1
totalPct        = monthTotals[i] / maxMonthTotal × 100 %
segments.flexVal= max(0.001, monthlyDelta[i])     // elak flex 0

// 13. Laporan
totalProjects       = 16
avgDeal             = totalSales / totalProjects
reportRevenueNum    = filteredRow ? parseNum(filteredRow.salesLabel) : monthActual
reportQuotationLabel= fmtRM(reportRevenueNum × 3.83)    // ⚠️ MULTIPLIER AJAIB
conversionRateLabel = '8.2%'                             // ⚠️ HARDCODED
reportAvgDealLabel  = fmtRM(reportProjectCount ? reportRevenueNum/reportProjectCount : 0)

// 14. Kad ringkasan dashboard (agregat SEMUA servis)
kutipanSum   = Σ metrik 'Sales Collection (New)' + 'Sales Collection (Progress Claim)'
quotationSum = Σ metrik 'Amount Quotation Release (New)'
leadSum      = Σ metrik 'No of Lead'
pctChange(s) = s.target ? (s.actual − s.target)/s.target × 100 : 0
label        = (pct>=0 ? '↑ +' : '↓ ') + pct.toFixed(1) + '%'
color        = pct>=0 ? oklch(0.72 0.15 145) : oklch(0.6 0.2 25)

// 15. Notifikasi hidup (liveNotifications)
Untuk setiap servis: kira baris berstatus Red → jika >0, tambah notifikasi
  text: '{servis}: {n} metrik belum capai sasaran (tiada action plan)'
  icon: ph-warning-circle, accent oklch(0.6 0.2 25), time 'Data Kritikal'
  onClick: navigate('service', key)
Jika sheetConnected: tambah 'Google Sheet disambung — segerak terakhir {lastSynced}'
showNotifBadge = liveNotifications.length > 0 && !notifRead
```

### 5.7 Halaman 1 — DASHBOARD (`isDashboard`)

**Topbar:** tajuk `EXECUTIVE PERFORMANCE` (22px/800 emas, `letter-spacing:1px`) · subtajuk "Dashboard Prestasi Syarikat"

**Blok 1 — Baris atas (flex, gap 24px)**

*1a. Kad Prestasi Keseluruhan* (`flex:2`, border emas `oklch(0.78 0.12 85/0.3)`, `border-radius:16px`, `padding:26px`)
- Tajuk "Prestasi Keseluruhan / *Overall Performance*" + **badge tier** (pill, bg `color-mix(in oklch, {tierColor} 18%, transparent)`, titik 7×7, teks "Index: {nameMy} / {name}")
- Kapsyen: mod bulanan → "Prestasi bulan {Bln} {Thn} / Monthly performance for..."; mod tahunan → "Kumulatif tahunan setakat..."; tambahan " — *unjuran, data sebenar belum tersedia*" jika `isProjectedMonth`
- **Toggle Bulanan/Tahunan** — segmented control, bg `var(--hover-bg3)`, `border-radius:9px`, aktif bg emas
- **Butang bulan Jan-Dis** — `overflow-x:auto`, `max-width:340px`, `padding:7px 14px`, `border-radius:8px`, aktif emas
- Kiri: label jualan (bertukar ikut mod) → nilai 38px/800 → label sasaran → nilai 20px/700 → "Perubahan vs Sasaran" + ikon `ph-trend-up`/`ph-trend-down` + peratus berwarna
- Kanan: **donut 210×210px** `conic-gradient(oklch(0.6 0.22 350) 0% {pct}%, var(--track-bg) {pct}% 100%)`, lubang tengah 158px bg `var(--card-bg)` dengan peratus 30px/800 emas + "Pencapaian Sasaran / *Target Achievement*"

*1b. Tiga kad ringkasan* (`flex:1`, susun menegak, gap 16px) — setiap satu: bulatan ikon 52×52 + label + nilai 22px/700 + peratus perubahan kanan

| Kad | Ikon | Warna bulatan | Sumber |
|---|---|---|---|
| Kutipan / *Collection* | `ph-wallet` | `oklch(0.55 0.22 350/0.18)` | `kutipanSum.actual` |
| Sebut Harga / *Quotation* | `ph-receipt` | `oklch(0.78 0.12 85/0.16)` | `quotationSum.actual` |
| Lead Baharu / *New Leads* | `ph-user-plus` | `oklch(0.55 0.22 350/0.18)` | `leadSum.actual` (fmtNum) |

**Blok 2 — Jadual Prestasi Mengikut Servis**
Grid `2fr 1.2fr 1.2fr 2fr 1.6fr 1.2fr`; header: Servis / *Service* · Jumlah Jualan / *Sales* · Sasaran / *Target* · Pencapaian / *Achievement* · Status · Tindakan / *Action*
Setiap baris: kotak ikon 34×34 (`border-radius:9px`, bg `oklch(0.55 0.22 350/0.16)`, ikon emas) + nama + nameEn italik · nilai · sasaran · **progress bar** (`height:7px`, `border-radius:4px`, track `var(--track-bg)`) + peratus · titik status 8×8 + label 2 baris (BM + EN italik) · butang "Lihat Detail" (border emas, `ph-caret-right`)
Toggle Bulanan/Tahunan + butang bulan **diulang** di header kad ini (`max-width:420px`)

**Blok 3 — Index Sasaran Jualan**
- Header + badge "Status: {nameMy} ({Bulanan|Tahunan})"
- Nota: "Ditentukan oleh hasil jualan {timeframe} berbanding sasaran indeks. Untung bersih dianggarkan {RM} (margin 18%, anggaran)"
- **Piramid tier** — 5 bar tersusun (Sustainability → Critical), lebar `100% − i×16%`, `border-radius:6px`, teks putih 13px/700, `padding:12px 16px`. Tier semasa dapat penanda absolut kanan: "📍 Anda di sini / Current" (bg `var(--card-bg)`, border warna tier)
- **Jadual 7 lajur** (`overflow-x:auto`, `min-width:760px`): Tahap / *Tier* · Revenue Bulanan · Untung Bersih Bulanan · Revenue Suku Tahun (×3) · Untung Bersih Suku Tahun (×3) · Revenue Tahunan (×12) · Untung Bersih Tahunan (×12). Baris semasa: bg `color-mix(in oklch, {color} 12%, transparent)` + ikon `ph-check-circle`

**Blok 4 — Trend + Keutamaan (flex, gap 24px)**

*4a. Trend Jualan & Sasaran* (`flex:2`)
- `<x-trend-chart :chart="dashboardChart" />` tinggi 320px
- Pembahagi "Mengikut Servis / *By Service*" + garis
- **Legend 5 servis** (petak 9×9 + nama)
- **Carta bar bertindan** — tinggi 200px, 7 bulan; setiap bar: label jumlah di atas (10.5px), bar `width:60%` `flex-direction:column-reverse` dengan segmen berwarna ikut `serviceColors`, label bulan di bawah

*4b. Keutamaan Minggu Ini* (`flex:1`)
- 3 item: kotak ikon 38×38 + nama servis (+ nameEn italik) + desc BM/EN + **avatar bulat 30×30** (`i.pravatar.cc/64?img={n}`, border emas)
- Klik item → navigasi ke halaman servis
- Footer: "Lihat semua tindakan / *View all actions*" → halaman Laporan

**Blok 5 — Trend Jualan Tahunan**
`<x-trend-chart :chart="yearlyChart" />` tinggi 320px, kapsyen "Unjuran jualan setahun penuh, 2023-2032"

### 5.8 Halaman 2 — DETAIL SERVIS (`isService`)

**Topbar:** tajuk `{NAMA} / {NAMEEN}` (uppercase) · subtajuk "Prestasi Servis / Service Performance"

1. **Breadcrumb** — butang balik 38×38 (`ph-arrow-left`) + "Dashboard Utama / **{Servis}**"
2. **Bar pemilih bulan** (hanya jika ada `criticalRows`) — "Papar bulan / *View month*: **{Bln}**" + 12 butang bulan `overflow-x:auto`
3. **3 kad ringkasan** (flex gap 20px) — Jumlah Jualan (26px/800) · Sasaran (26px/800) · Donut kecil 76×76 (lubang 54px) + "Pencapaian Sasaran"
4. **2 kad Actual vs Sasaran** (bersyarat) — Quotation & Site Visit/Appointment: nilai 22px/800 + "/ {target} sasaran" + progress bar 8px + peratus
5. **Carta Trend Bulanan** — `<x-trend-chart :chart="serviceChart" />` tinggi 300px
6. **Trend Quotation & Site Visit** (bersyarat) — grid 3 lajur, setiap satu:
   - Tajuk + "Sasaran: **{n}**/mgu" atau "/minggu"
   - Kawasan bar tinggi 120px dengan **garis putus sasaran** absolut `top:8px` `border-top:2px dashed oklch(0.78 0.12 85/0.6)`
   - 4 bar mingguan (M1-M4), label nilai atas + label minggu bawah, `width:56%`, `min-height:2px`
   - 3 carta: Amount Quotation / *Quotation Value* · Bilangan Quotation / *Quotation Count* · Bilangan {Site Visit|Appointment}
   - Legend bawah: garis putus + "Garis sasaran mingguan / *Weekly target line*"
7. **Jadual Data Kritikal Mingguan** — `overflow-x:auto`, `min-width:1360px`, grid **11 lajur**: `250px 95px 95px 95px 95px 80px 115px 140px 90px 110px 1fr`
   - Header: Data Kritikal · Minggu 1-4 (label 2 baris `white-space:pre-line` dengan julat tarikh) · Jenis · Actual · Sasaran / *Target* · Status · Pemilik / *Owner* · Pelan Tindakan / *Action Plan*
   - Baris: nama metrik (600) · 4 input minggu (`text-align:center`, `disabled` jika `notEditable`, placeholder "—") · jenis · **Actual** (700, auto-kira) · **Sasaran** (hijau `oklch(0.72 0.15 145)`, `title="Hanya boleh ditukar di Admin Panel"`) · titik status 7×7 + label · **`<select>` PIC** (bg `color-mix(in oklch, {ownerColor} 18%, transparent)`, teks warna PIC, opsyen dari `ownerOptionsList`) · input pelan tindakan (placeholder "Isi pelan tindakan...")
   - Legend status 3 item · chip penapis "Tapis: {owner} ✕" (jika aktif) · butang **Google Sheet Integration** (`ph-google-logo`, bg emas)
8. **Prestasi Pemilik Data** — grid `repeat(auto-fit, minmax(280px,1fr))`; setiap kad boleh klik untuk tapis:
   - Chip nama (+ ✓ jika terpilih) + badge "⚠ Perlu Tindakan" jika ada Red
   - Progress bar skor + peratus
   - Kiraan: {n} On Track · {n} Ada Plan · {n} Kritikal · / {total} metrik
   - Jika ada Red: kotak merah senarai "Metrik belum capai sasaran & tiada action plan:" dengan bullet
9. **Analisis Penting** (`flex:2`) + **Kad Keutamaan** (`flex:1`, bersyarat)
   - Kotak Perbandingan Actual vs Sasaran (nilai 22px/800 + 16px/700 + progress bar)
   - **3 tile** grid: Pencapaian Jualan (`ph-target`) · Purata Nilai Projek (`ph-chart-line-up`) · Kadar Larian Diperlukan (`ph-gauge`) — setiap satu ikon + label BM/EN + nilai 19px/800 + nota
   - 2 kad nasihat mingguan (`ph-file-text` quotation, `ph-map-pin` site visit) — bersyarat `weeklyQuotationTarget`
   - "Tindakan Disyorkan / *Recommended Actions*" — 3 bullet `ph-check-circle` hijau, **teks berbeza** ikut `good` (≥35%) atau tidak
   - Kad Keutamaan: desc BM/EN + avatar 32×32 + nama pemilik

### 5.9 Halaman 3 — LAPORAN (`isLaporan`)

**Topbar:** `LAPORAN / REPORTS` · "Ringkasan Prestasi Keseluruhan / Overall Performance Summary"

1. **Bar kawalan** — kad `padding:16px 22px`:
   - Dropdown tarikh (`ph-calendar-blank`) → senarai 12 bulan (`{Bulan Penuh} {Tahun}`)
   - Dropdown penapis servis (`ph-funnel`) → "Semua Servis / All Services" + 5 servis
   - Butang **Eksport / Export** (`ph-download-simple`, bg emas)
2. **4 kad KPI** grid `repeat(4,1fr)` gap 20px — Jumlah Hasil / *Total Revenue* · Jumlah Sebut Harga / *Total Quotations* · Kadar Penukaran / *Conversion Rate* · Purata Nilai Deal / *Avg Deal Value* (semua nilai 22px/800 Plus Jakarta Sans)
3. **Carta Trend Keseluruhan** — `<x-trend-chart :chart="reportChart" />` tinggi 320px (bertukar ikut penapis servis)
4. **Jadual Pecahan Mengikut Servis** — grid `2fr 1.2fr 1.2fr 1fr 1.6fr 1.4fr`: Servis · Jumlah Jualan · Sasaran · Projek · Pencapaian (bar `max-width:80px`) · Status (titik + label)

### 5.10 Halaman 4 — TETAPAN (`isTetapan`)

**Topbar:** `TETAPAN / SETTINGS` · "Keutamaan Sistem / System Preferences"

Satu kad `max-width:640px`:
- Header ikon `ph-shield-check` + "Keutamaan Sistem / *System Preferences*"
- Nota: "Akses dalaman syarikat sahaja — tiada akaun individu. / *Internal company access only — no individual user accounts.*"
- **3 toggle switch** (38×22px, knob 18×18 putih, `left:2px` ⇄ `18px`, bg on `oklch(0.72 0.15 145)` / off `var(--switch-off)`):
  - Notifikasi Emel / *Email Notifications*
  - Laporan Mingguan / *Weekly Report*
  - Bunyi Amaran / *Alert Sound*
- Butang **Simpan Tetapan / *Save Settings*** (bg emas)
- Pautan **Admin Panel** (`ph-shield-check`, border) → `./Admin Login.dc.html`

> ⚠️ **Kad Profil TIADA dalam markup** walaupun semua state (`settings.name/role/email/phone/avatarUrl`) dan 6 handler wujud dalam kod.

### 5.11 Komponen global

- **Sidebar** — `width:264px`, sticky `height:100vh`, `overflow-y:auto`, bg `var(--sidebar-bg)`; kotak logo (bg putih, border emas); 8 item menu (`padding:12px 14px`, `border-radius:10px`, aktif: bg `var(--hover-bg2)` + `border-left:3px solid oklch(0.78 0.12 85)`, ikon+teks emas/terang); footer toggle Dark Mode (`ph-moon` emas + switch)
- **Topbar** — `height:76px`, sticky, `padding:0 32px`, `border-bottom:1px solid var(--border)`, `z-index:20`
  - Dropdown Tahun — butang `ph-calendar-blank` + "Tahun {n}" + `ph-caret-down`; panel `width:160px`, `max-height:320px`, `overflow-y:auto`, 10 pilihan tahun (aktif bg emas)
  - Dropdown Period — `ph-clock-countdown`; panel `width:220px`, 3 pilihan
  - Butang Notifikasi — 38×38, `ph-bell`; badge merah 8×8 (`oklch(0.55 0.22 350)`) absolut `top:6px right:7px`; panel `width:320px` dengan header "Notifikasi / *Notifications*", senarai item (ikon + teks + masa) atau mesej kosong "Semua servis on track — tiada amaran kritikal. / *All clear.*"
  - Dropdown Profil — avatar bulat 36×36 border emas dengan ikon `ph-shield-check`, teks "Akses Dalaman / *Internal Access*"; panel `width:200px`: "Tetapan / Settings" (`ph-gear`) & "Log Keluar / Log Out" (`ph-sign-out`, warna `oklch(0.75 0.1 20)`)
- **Toast global** — sama gaya dengan Login
- **Scrollbar tersuai** — `width/height:8px`, thumb `oklch(0.35 0.02 260)`, `border-radius:4px`
- **Modal Raw Data** — `width:640px`, `max-height:80vh`, overlay `rgba(0,0,0,0.55)`, `z-index:110`; header `ph-code` + "Raw Data — Data Kritikal"; `<pre>` JSON dengan medan `{metric, type, week1-4, monthlyActual, monthlyTarget, status, owner}`. ⚠️ **Tidak boleh dicapai** — bersarang dalam `sheetModalOpen` yang tiada pemicu

### 5.12 Nilai `renderVals()` yang dikira tetapi TIDAK digunakan dalam markup

| Nilai | Nota |
|---|---|
| `dateMonthOptions` | Senarai 12 bulan penuh — dropdown topbar hanya papar tahun |
| `periodPrevLabel` | Label "vs minggu lalu" — tiada di markup |
| `sheetButtonLabel` | "Google Sheet Disambung" — markup hardcode "Google Sheet Integration" |
| `serviceProjects`, `serviceProjectCount` | Jadual projek tiada dalam markup |
| `editingProfile`, `notEditingProfile`, `editIconClass`, `editLabel` | Kad profil tiada |
| `settings.name/role/email/phone/avatarUrl` + 5 handler | Kad profil tiada |
| `addOwnerModalOpen`, `newOwnerName`, `toggleAddOwnerModal`, `addCustomOwner` | Modal tiada |
| `onSheetUrlChange`, `connectSheet`, `syncSheetNow`, `disconnectSheet`, `toggleSheetModal` | Modal tiada |
| `isYearlyView`, `serviceViewMode` (raw) | Diganti oleh `monthlyViewBg` dsb. |
| `revKey` | Pemboleh ubah tempatan tidak dirujuk |
| `this.notifications` (4 item statik) | Diganti `liveNotifications` |
| `this.monthsEn` | Tidak dirujuk |
| `periodConfig.mult` | Tidak masuk mana-mana formula |

---

## 6. Senarai Isu & Item "Adjust" (26)

| # | Isu | Keterukan | Cadangan pembetulan Laravel |
|---|---|---|---|
| 1 | OTP demo dipapar di skrin ("Demo: OTP anda ialah **123456**") | 🔴 Kritikal | Buang; hantar via `Notification`/`Mailable` sahaja |
| 2 | Kelayakan hardcoded (`dbena`/`••••••••`, `DBENASB`/`••••••••`) | 🔴 Kritikal | Jadual `users`, kata laluan di-hash |
| 3 | Tiada backend — semua data array JS | 🔴 Kritikal | MySQL 8.4 penuh |
| 4 | `localStorage` sebagai satu-satunya storan (Admin Panel) | 🔴 Kritikal | Jadual DB + transaksi |
| 5 | Tiada semakan role — "Admin Panel" hanyalah `<a href>` | 🔴 Kritikal | Middleware `role:admin` server-side |
| 6 | Tiada rate limit / lockout log masuk | 🔴 Kritikal | `RateLimiter` 5 cubaan / 15 min |
| 7 | `doLogout()` hanya toast, tiada logout sebenar | 🔴 Kritikal | `Auth::logout()` + invalidate session |
| 8 | `saveSettings()` palsu — tidak simpan apa-apa | 🟠 Tinggi | Persist ke `users` |
| 9 | `exportReport()` palsu — hanya toast | 🟠 Tinggi | `Response::streamDownload` CSV |
| 10 | `removeOwner()` tiada guard backend | 🟠 Tinggi | Tolak jika PIC ada `critical_weekly_entries` |
| 11 | `addCustomOwner()` tidak persist (hilang bila refresh) | 🟠 Tinggi | Simpan status `pending_approval` → lulus Admin |
| 12 | Warna PIC tidak konsisten Dashboard ⇄ Admin Panel | 🟠 Tinggi | Satu sumber: `owners.color_token` |
| 13 | `hasMonthData = criticalMonth === 'Jul'` hardcoded | 🟠 Tinggi | Data setiap bulan/tahun dari DB |
| 14 | `monthsLeft = 5` hardcoded | 🟠 Tinggi | `12 − now()->month` (atau tahun fiskal) |
| 15 | `conversionRateLabel = '8.2%'` hardcoded | 🟠 Tinggi | Kira sebenar dari data |
| 16 | `reportQuotationLabel = revenue × 3.83` multiplier ajaib | 🟠 Tinggi | Guna jumlah quotation sebenar |
| 17 | `baseActualRatios` 7 bulan hardcoded | 🟠 Tinggi | Kira dari `critical_weekly_entries` |
| 18 | `periodConfig.mult` tidak beri kesan | 🟡 Sederhana | Aktifkan penuh (unit pengiraan bertukar) |
| 19 | Modal Raw Data bersarang mustahil dicapai | 🟡 Sederhana | Property `showRawDataModal` berasingan |
| 20 | `nameEn` untuk renovation/divider sebenarnya BM (terbalik); mihrab kosong | 🟡 Sederhana | Betulkan: Renovation/Renovation, Divider/Divider, Mihrab/Mihrab |
| 21 | `INFO` bukan PIC sebenar tetapi masuk dropdown; tiada di Admin Panel | 🟡 Sederhana | Jadikan `is_system` owner, tak boleh dipilih untuk penilaian |
| 22 | Avatar base64 runtime (`FileReader`), aset avatar dari CDN luar | 🟡 Sederhana | Upload sebenar ke `storage/app/public/avatars` |
| 23 | `--t55` sama nilai dark & light — kontras rendah | 🟡 Sederhana | Semak WCAG AA, laraskan tema terang |
| 24 | `min-width:1440px` — **langsung tiada sokongan mobile** | 🔴 Kritikal | Bina semula mobile-first |
| 25 | Tiada audit trail perubahan konfigurasi | 🟡 Sederhana | Jadual `audit_logs` |
| 26 | Aset CDN (fon, ikon, avatar) — risiko prestasi & privasi | 🟢 Rendah | Self-host via Vite |

---

## 7. Ringkasan Kuantitatif

| Metrik | Jumlah |
|---|---|
| Fail HTML prototaip | 5 |
| Halaman logikal | 8 (Login, Admin Login, Dashboard, 5× Detail Servis, Laporan, Tetapan, Admin Panel) |
| Komponen boleh guna semula | 1 (TrendChart) |
| Fungsi JS keseluruhan | **98** (Login 21 · Admin Login 21 · Admin Panel 9 · Dashboard 47) — tidak termasuk 5× `renderVals()` |
| Fungsi mati (dead code) | **12** (semuanya dalam Dashboard) |
| State property | 31 (Dashboard) + 19 (Login) + 19 (Admin Login) + 7 (Admin Panel) = **76** |
| Jadual DB diperlukan | 15 |
| Token warna tema | 26 (× 2 mod = 52 nilai) |
| Warna tetap tidak-bertema | 16 |
| Formula bisnes | 15 kumpulan |
| Servis | 5 |
| Baris metrik kritikal | 49 (10+10+10+10+9) |
| Index tier | 5 |
| PIC teras | 4 (+1 pseudo `INFO`) |
| Projek demo | 16 |
| Faktor pertumbuhan tahunan | 10 (2023-2032) |
| Ikon Phosphor Duotone digunakan | 45 |

**Senarai penuh ikon Phosphor Duotone:** `ph-arrow-left`, `ph-bank`, `ph-bell`, `ph-calendar-blank`, `ph-caret-down`, `ph-caret-right`, `ph-chart-bar`, `ph-chart-line-up`, `ph-check`, `ph-check-circle`, `ph-clock-countdown`, `ph-code`, `ph-columns`, `ph-download-simple`, `ph-envelope-simple`, `ph-eye`, `ph-eye-slash`, `ph-file-text`, `ph-floppy-disk`, `ph-funnel`, `ph-gauge`, `ph-gear`, `ph-google-logo`, `ph-house`, `ph-house-line`, `ph-key`, `ph-lock-key`, `ph-map-pin`, `ph-moon`, `ph-pencil-simple`, `ph-plus`, `ph-receipt`, `ph-shield-check`, `ph-shield-warning`, `ph-sign-out`, `ph-squares-four`, `ph-target`, `ph-trend-down`, `ph-trend-up`, `ph-user-plus`, `ph-wallet`, `ph-warning-circle`, `ph-wrench`, `ph-x`, `ph-x-circle`

---

*Dokumen ini adalah rujukan teknikal lengkap prototaip. Untuk keperluan produk lihat `PRD.md`; untuk arahan pembinaan lihat `CLAUDE_CODE_PROMPT.md`.*
