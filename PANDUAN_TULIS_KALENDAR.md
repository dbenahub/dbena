# Google Calendar — kenapa "shared for reading only" dan cara betulkan

**Untuk:** Ahmad Nizam
**Masalah:** Task Calendar boleh BACA kalendar, tetapi tidak boleh TULIS ke dalamnya.

---

## Baca dan tulis ialah dua kebenaran berbeza

| Kebenaran dalam Google | Robot boleh baca? | Robot boleh tulis? |
|---|---|---|
| See only free/busy | ❌ | ❌ |
| **See all event details** | ✅ | ❌ ← **anda di sini** |
| **Make changes to events** | ✅ | ✅ ← perlu ini |
| Make changes and manage sharing | ✅ | ✅ |

Menaik taraf daripada baris ketiga ke baris keempat ialah keseluruhan pembetulan.

---

## LANGKAH 0 — Tekan "Semak kebenaran" dahulu

Sebelum mengubah apa-apa, buka **Task Calendar** dan tekan **Semak kebenaran**
di panel kiri.

Ia bertanya kepada Google secara terus dan memaparkan setiap kalendar yang
robot nampak, berserta perananya. Anda akan dapat satu daripada empat jawapan:

| Yang dipaparkan | Maksudnya | Pergi ke |
|---|---|---|
| Calendar ID belum ditetapkan | Tiada ID disimpan | Langkah 4 |
| ID **TIADA** dalam senarai | ID salah, atau belum dikongsi | Langkah 1 & 4 |
| Kalendar ada, **baca sahaja** | Kebenaran terlalu rendah | Langkah 2 |
| Kebenaran sudah betul | Sepatutnya berjaya | Langkah 5 |

**Jangan langkau ini.** Tiga masalah berbeza semuanya memberi ralat 403 yang sama,
dan membetulkan yang salah membuang masa berjam-jam.

---

## LANGKAH 1 — Salin emel robot

Emel itu dipaparkan dalam panel Semak kebenaran, dan juga di
**Panel Admin → Roadmap**.

Rupanya:

```
dbena-sync@dbena-dashboard.iam.gserviceaccount.com
```

Salin **keseluruhannya**. Ia panjang dan hujungnya mudah terpotong.

---

## LANGKAH 2 — Naik taraf kebenaran

Buat dalam akaun **dbenagroup@gmail.com**.

1. Buka **https://calendar.google.com**
2. Panel kiri → **My calendars** → hover kalendar yang betul
3. Klik **⋮** → **Settings and sharing**
4. Tatal ke **Share with specific people or groups**
5. Cari emel robot dalam senarai

### Kalau emel robot ADA dalam senarai

6. Klik dropdown kebenaran di sebelahnya
7. Pilih **Make changes to events**
8. Klik **Send** / **Save**

### Kalau menukar dropdown tidak berkesan

Ini berlaku. Google kadangkala mengekalkan kebenaran lama.

6. Klik **✕** di sebelah emel robot untuk **membuangnya sepenuhnya**
7. Muat semula halaman
8. **+ Add people and groups** → tampal semula emel robot
9. Pilih **Make changes to events** **sebelum** menekan Send
10. **Send**

> Google akan beri amaran alamat itu kelihatan pelik. Teruskan.
> Robot tidak menerima emel — akses diberikan serta-merta.

---

## LANGKAH 3 — Pastikan anda mengubah kalendar yang BETUL

Punca paling kerap terlepas pandang: kebenaran dinaik taraf pada satu kalendar,
tetapi ID yang disimpan dalam dashboard menunjuk kalendar lain.

Masih dalam halaman **Settings and sharing** kalendar yang sama:

1. Tatal ke **Integrate calendar**
2. Salin **Calendar ID**
3. Bandingkan dengan ID yang dipaparkan dalam panel Semak kebenaran

Kalau berbeza — itulah masalahnya. Betulkan dalam Langkah 4.

---

## LANGKAH 4 — Sahkan Calendar ID dalam dashboard

**Panel Admin → Roadmap Tahunan** → medan **Calendar ID**.

Tampal ID daripada Langkah 3. Anda boleh tampal pautan penuh juga —
sistem akan keluarkan ID daripadanya.

Tekan **Uji sambungan**, kemudian **Simpan teks**.

---

## LANGKAH 5 — Hantar

**Task Calendar** → **Hantar ke Google Calendar**.

Berjaya kelihatan begini:

```
7 acara baharu, 0 dikemas kini, 0 dipadam.
```

Buka Google Calendar. Tugasan muncul sebagai:

```
[P] Joint booth 3 hari di Putrajaya
[C] Buka car booth event Renovation
[K] Gantung banner Renovation
```

Huruf itu ialah status. Warna diset juga, tetapi huruf berfungsi pada setiap
peranti manakala warna tidak.

---

## Kalau masih gagal selepas semua ini

Tekan **Semak kebenaran** sekali lagi dan lihat apa yang berubah:

**"Robot tidak nampak SATU pun kalendar"**
Perkongsian tidak pernah berjaya disimpan. Ulang Langkah 2 dengan kaedah
buang-dan-tambah-semula.

**"Google Calendar API belum diaktifkan"**
Buka pautan yang dipaparkan, tekan **ENABLE**, tunggu seminit.

**"Kebenaran sudah betul" tetapi hantar masih gagal**
Beritahu saya mesej penuh yang dipaparkan. Pada tahap itu masalahnya bukan
lagi kebenaran, dan mesej Google akan menamakan apa yang sebenarnya berlaku.

---

## Nota keselamatan

Robot kini boleh **menambah, mengubah dan memadam acara** pada kalendar itu.
Ia tidak boleh melihat kalendar lain, tidak boleh mengubah tetapi perkongsian,
dan tidak boleh mengakses e-mel atau fail.

Untuk menarik balik pada bila-bila masa: Settings and sharing → buang emel robot.
Dashboard terus berfungsi seperti biasa; hanya butang Hantar yang berhenti.
