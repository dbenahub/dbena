#!/usr/bin/env python3
"""
Pernyataan `use` di dalam blok @php BERSARANG.

PHP hanya membenarkan import pada skop terluar sesuatu fail. Blade
menyusun @foreach dan @if kepada blok { } sebenar, jadi `use App\Foo;` di
dalam salah satu daripadanya berada di dalam skop bersarang — dan itu
ralat sintaks MAUT, bukan amaran.

Halaman menjadi 500 kosong, dan ralatnya menunjuk ke fail paparan yang
disusun dalam storage/framework/views/ dengan nama cincangan, bukan ke
fail yang anda tulis. Ia juga lulus setiap semakan mata kasar, kerana ia
kelihatan betul.

`use` pada peringkat atas fail Blade adalah SAH dan tidak dilaporkan.
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
VIEWS = ROOT / "resources" / "views"

BUKA = re.compile(r"@(foreach|forelse|for|while|if|unless|switch|isset|empty|auth|guest|can|cannot|push|once|section|slot|error)\b")
TUTUP = re.compile(r"@(endforeach|endforelse|endfor|endwhile|endif|endunless|endswitch|endisset|endempty|endauth|endguest|endcan|endcannot|endpush|endonce|endsection|endslot|enderror)\b")
PHP_BLOK = re.compile(r"@php(.*?)@endphp", re.S)
IMPORT = re.compile(r"^[ \t]*use\s+\\?[A-Za-z_][\w\\]*(\s+as\s+\w+)?\s*;", re.M)

masalah = []
jumlah = 0

for f in sorted(VIEWS.rglob("*.blade.php")):
    jumlah += 1
    src = f.read_text(encoding="utf-8")

    # Tanda kedalaman setiap aksara: berapa banyak blok kawalan terbuka.
    kedalaman = 0
    peta = []
    i = 0

    while i < len(src):
        if src[i] == "@":
            tutup = TUTUP.match(src, i)
            if tutup:
                kedalaman = max(0, kedalaman - 1)
                peta.extend([kedalaman] * (tutup.end() - i))
                i = tutup.end()
                continue

            buka = BUKA.match(src, i)
            if buka:
                peta.extend([kedalaman] * (buka.end() - i))
                kedalaman += 1
                i = buka.end()
                continue

        peta.append(kedalaman)
        i += 1

    for blok in PHP_BLOK.finditer(src):
        if peta[blok.start()] == 0:
            continue  # Peringkat atas — sah.

        for u in IMPORT.finditer(blok.group(1)):
            baris = src[: blok.start()].count("\n") + 1 + blok.group(1)[: u.start()].count("\n")
            masalah.append(f"  ❌ {f.relative_to(ROOT)}:{baris}  {u.group(0).strip()}")

if masalah:
    print("\n".join(masalah))

print(f"Diperiksa {jumlah} fail Blade")

if masalah:
    print(f"❌ {len(masalah)} pernyataan use dalam blok @php bersarang")
    print("   Guna nama penuh: \\App\\Support\\OrgPalette::clean(...)")
    sys.exit(1)

print("✅ Tiada pernyataan use dalam blok @php bersarang")
