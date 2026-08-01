# Alat Pengesahan

Skrip Python yang menyemak kod tanpa memerlukan PHP dipasang.
Berguna semasa pembangunan di persekitaran tanpa PHP.

| Skrip | Menyemak |
|---|---|
| `check-syntax.py` | Kurungan seimbang dalam PHP, pasangan arahan Blade, komponen `<x-...>` wujud |
| `check-php-strings.py` | **String PHP yang lexically sah tetapi syntactically rosak** |
| `check-closures.py` | **Pembolehubah digunakan dalam closure tetapi tiada dalam `use()`** |

## Kenapa check-php-strings.py wujud

Baris ini melepasi semakan kurungan tetapi memecahkan PHP:

```php
'missing_key_field' => 'Kelayakan dalam :source tiada medan ':field'.',
```

Bilangan petikan seimbang (4), jadi penyemak kurungan meluluskannya. Tetapi PHP
menghuraikannya sebagai *string, bareword, string* — ralat parse yang hanya
muncul ketika runtime, sebagai halaman 500.

Skrip ini memeriksa apa yang **mengikuti** setiap string tertutup. Selepas
string, PHP hanya membenarkan pengendali (`.` `,` `)` `=>` `||` dsb.) — bukan
perkataan bogel.

Ralat ini benar-benar berlaku dalam projek ini dan menyebabkan 500 di produksi.

## Kenapa check-closures.py wujud

```php
->map(function (Collection $ownerRows) use ($allRows) {
    return $this->analyse($ownerRows, $rows);   // $rows tidak wujud
})
```

Kurungan seimbang, string sah — tetapi `$rows` tidak diimport, jadi PHP
melemparkan ralat ketika closure dijalankan. Muncul sebagai 500 pada satu
halaman sahaja. Ini juga benar-benar berlaku dalam projek ini.

## Jalankan

```bash
python3 tools/check-syntax.py
python3 tools/check-php-strings.py
python3 tools/check-closures.py
```

Kalau PHP tersedia, `php -l fail.php` lebih tepat. Alat ini untuk bila ia tiada.

## check-blade-echo.py

Mengesan `{{ $baris['kunci'] }}` yang sebenarnya memaparkan tatasusunan.

PHP melemparkan `htmlspecialchars(): Argument #1 must be of type string,
array given` — halaman 500 penuh, bukan amaran. Ia hanya muncul apabila
templat itu benar-benar dirender, jadi laluan yang jarang digunakan seperti
eksport PDF boleh rosak berminggu-minggu tanpa disedari.

Ditulis selepas `commentary` — senarai ayat — dipaparkan terus dalam
templat PDF laporan pengurusan. Ia menghempaskan setiap eksport, dan
mencari puncanya mengambil lima pusingan kerana skrin 500 kosong tidak
memberitahu apa-apa.

Kunci hanya dilaporkan apabila SETIAP tempat yang menghasilkannya
menghasilkan bukan skalar. Nama seperti `owner` dan `target` digunakan
semula merentas perkhidmatan yang tidak berkaitan; menandakan setiap
penggunaan menghasilkan bunyi bising, dan pemeriksa yang bising akan
diabaikan.

    python3 tools/check-blade-echo.py

## check-icons.py

Mengesan ikon Phosphor yang menggunakan berat yang tidak diimport.

Phosphor mengedarkan setiap berat sebagai fail CSS berasingan. Menggunakan
`ph-fill` sedangkan hanya `duotone` diimport tidak menghasilkan ralat, tiada
amaran, dan tiada apa dalam konsol — ikon itu sekadar tidak dirender. Ruang
kosong di tempatnya kelihatan seperti pelapik yang salah, bukan ikon yang
hilang.

Ditulis selepas butang buka/tutup sidebar dihantar dengan `ph-bold`. Anak
panahnya tidak pernah muncul, dan butang itu kelihatan seperti ruang kosong.
Enam ikon lain dalam Peta Perjalanan sudah tidak kelihatan atas sebab yang
sama tanpa disedari.

    python3 tools/check-icons.py
