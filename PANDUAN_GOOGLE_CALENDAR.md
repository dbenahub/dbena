# Panduan Sambung Google Calendar ke Roadmap Tahunan

**Untuk:** Ahmad Nizam (DBENA SDN BHD)
**Masa diperlukan:** ~10 minit, sekali sahaja
**Kalendar kekal PERIBADI.** Tiada "Make available to public". Tiada pautan rahsia.

---

## Kenapa perlu dikongsi, bukan log masuk

Service account ialah "robot" Google yang sudah kita guna untuk baca Google Sheet.
Robot itu **tidak boleh menyamar** sebagai `dbenagroup@gmail.com` — penyamaran
memerlukan *domain-wide delegation*, yang hanya wujud untuk akaun Google Workspace
berbayar, bukan akaun `@gmail.com`.

Jadi caranya terbalik: **kita jemput robot itu masuk.** Kalendar dikongsi dengan
emel robot, sama seperti anda kongsi kalendar dengan rakan sekerja. Selepas itu
kalendar muncul dalam senarai robot dan ia boleh membacanya.

Robot hanya dapat **baca** (`calendar.readonly`). Ia tidak boleh tambah, ubah atau
padam apa-apa acara.

---

## LANGKAH 1 — Dapatkan emel robot

1. Buka dashboard: **https://dbena.on-forge.com/admin/roadmap**
2. Lihat kotak **Google Calendar** di sebelah kanan bawah
3. Ada ayat seperti ini:

   > Kongsi kalendar dengan
   > **dbena-sheets@dbena-dashboard-xxxxx.iam.gserviceaccount.com**
   > (See all event details) sebelum menguji.

4. **Salin emel itu.** Itulah emel robot anda.

> Emel ini berakhir dengan `.iam.gserviceaccount.com`. Ia panjang dan pelik —
> itu normal.

**Kalau ayat itu tidak muncul:** bermakna kunci service account belum dipasang di
Forge. Berhenti di sini dan beritahu saya — sheet pun tidak akan boleh sync.

---

## LANGKAH 2 — Kongsi kalendar dengan robot

Buat langkah ini dalam akaun **dbenagroup@gmail.com** (akaun yang memiliki kalendar).

1. Buka **https://calendar.google.com**
2. Di panel kiri, cari senarai **My calendars**
3. **Hover** pada nama kalendar yang anda mahu guna
4. Klik **tiga titik ⋮** yang muncul di sebelah kanan nama itu
5. Pilih **Settings and sharing**

Sekarang anda dalam halaman tetapan kalendar itu.

6. Tatal ke bahagian **Share with specific people or groups**
7. Klik butang **+ Add people and groups**
8. **Tampal emel robot** dari Langkah 1
9. Pada dropdown **Permissions**, pilih:

   ```
   See all event details
   ```

   > **Jangan** pilih "See only free/busy" — robot akan nampak ada acara tetapi
   > tidak nampak tajuknya, dan roadmap akan memaparkan blok kosong tanpa nama.

10. Klik **Send**

> Google mungkin papar amaran "This email address doesn't look right" atau
> serupa. Itu normal untuk emel service account. **Teruskan.**
> Robot tidak menerima emel jemputan — akses diberikan serta-merta.

---

## LANGKAH 3 — Salin Calendar ID

Masih dalam halaman tetapan kalendar yang sama:

1. Tatal ke bahagian **Integrate calendar**
2. Medan pertama ialah **Calendar ID**
3. **Salin nilainya**

Rupanya salah satu daripada ini:

| Jenis kalendar | Rupa Calendar ID |
|---|---|
| Kalendar utama akaun | `dbenagroup@gmail.com` |
| Kalendar yang anda cipta sendiri | `c_a1b2c3d4e5f6@group.calendar.google.com` |

Kedua-duanya sah.

> **Jangan** salin "Secret address in iCal format". Kita tidak menggunakannya,
> dan pautan rahsia itu memberi akses kekal kepada sesiapa yang mendapatnya.

**Kalau anda tersalah salin pautan, tidak apa.** Tampal sahaja — sistem akan
keluarkan ID daripadanya dan memaparkan apa yang diambil. Ketiga-tiga bentuk ini
diterima:

```
https://calendar.google.com/calendar/embed?src=dbenagroup%40gmail.com&ctz=...
https://calendar.google.com/calendar/u/0?cid=dbenagroup%40gmail.com
https://calendar.google.com/calendar/ical/dbenagroup%40gmail.com/private/basic.ics
```

Kesemuanya menghasilkan `dbenagroup@gmail.com`.

---

## LANGKAH 4 — Masukkan dan uji

1. Kembali ke **https://dbena.on-forge.com/admin/roadmap**
2. Tampal Calendar ID ke medan **Calendar ID**
3. Klik **Uji sambungan**

**Berjaya** — mesej hijau:

```
12 acara dibaca untuk tahun 2026.
```

4. Klik **Simpan teks** untuk menyimpannya.

Selesai. Buka Dashboard Utama — bulan yang ada acara kini bertanda titik kuning
kecil di penjuru. Klik bulan itu untuk lihat senarai acara dengan tarikh penuh.

---

## Kalau gagal

Mesej ralat akan muncul dalam merah. Ini maksudnya:

### "Itu bukan Calendar ID"

Anda menampal sesuatu yang tiada ID di dalamnya. Kembali ke **Settings and
sharing → Integrate calendar → Calendar ID** dan salin nilai medan itu.

### "Google menolak permintaan. Buka calendar.google.com → ..." (403/404)

Tiga kemungkinan, ikut turutan semakan:

1. **Kalendar belum dikongsi.** Ini punca paling biasa. Ulang LANGKAH 2 dengan
   teliti — khususnya, pastikan anda menekan **Send** di hujungnya.
2. **Emel robot salah taip.** Salin semula dari halaman Roadmap. Emel itu panjang
   dan mudah terpotong hujungnya semasa salin.
3. **Kalendar yang salah dikongsi.** Kalau anda ada beberapa kalendar, mudah
   tersilap kongsi kalendar A tetapi salin ID kalendar B.

### "0 acara dibaca untuk tahun 2026"

Sambungan **berjaya** — cuma kalendar itu memang tiada acara dalam tahun tersebut.
Cuba tukar tahun ke tahun semasa dan uji semula, atau tambah satu acara ujian
dalam kalendar.

### Ralat menyebut "credentials" atau "private key"

Kunci service account di Forge bermasalah. Sheet pun tidak akan sync. Beritahu saya.

---

## Perkara yang berguna diketahui

**Acara berulang dikembangkan.** Mesyuarat mingguan akan muncul pada setiap bulan
ia berlaku, bukan sekali pada bulan ia dicipta.

**Acara dicache 15 minit.** Kalau anda tambah acara dalam Google Calendar, ia
muncul dalam roadmap dalam masa 15 minit. Untuk melihatnya serta-merta, tekan
**Uji sambungan** di Panel Admin — itu membuang cache.

**Kalendar yang rosak tidak merosakkan roadmap.** Kalau seseorang membatalkan
perkongsian atau memadam kalendar, roadmap tetap dipaparkan seperti biasa —
cuma dengan satu jalur amaran di atas. Grid dan sasaran hidup dalam pangkalan
data DBENA sendiri, bukan dalam Google.

**Satu kalendar untuk semua servis.** Reka bentuk sekarang membaca satu kalendar
setiap tahun. Kalau anda mahu kalendar berasingan untuk setiap servis, beritahu
saya — itu perubahan kecil.

---

## Bahagian keselamatan

| | |
|---|---|
| Kalendar kekal | **Private** — tiada perkongsian awam |
| Akses robot | **Baca sahaja** (`calendar.readonly`) |
| Robot boleh ubah acara | **Tidak** |
| Boleh tarik balik bila-bila | **Ya** — buang emel robot dari senarai Share |

Untuk menarik balik akses: Settings and sharing → cari emel robot → klik ✕.
Roadmap akan terus berfungsi; hanya titik acara hilang.
