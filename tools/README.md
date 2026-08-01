# Alat Pengesahan

Skrip Python yang menyemak kod tanpa memerlukan PHP dipasang.
Berguna semasa pembangunan di persekitaran tanpa PHP.

| Skrip | Menyemak |
|---|---|
| `check-syntax.py` | Kurungan seimbang dalam PHP, pasangan arahan Blade, komponen `<x-...>` wujud |
| `check-php-strings.py` | **String PHP yang lexically sah tetapi syntactically rosak** |

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

## Jalankan

```bash
python3 tools/check-syntax.py
python3 tools/check-php-strings.py
```

Kalau PHP tersedia, `php -l fail.php` lebih tepat. Alat ini untuk bila ia tiada.
