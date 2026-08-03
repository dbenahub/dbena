#!/usr/bin/env python3
"""
Kunci bahasa yang dirujuk tetapi tidak wujud.

Laravel TIDAK menghempaskan apa-apa apabila kunci tiada — ia memaparkan
kunci itu sendiri. Jadi "calendar.title" muncul sebagai tajuk halaman,
kelihatan seperti reka bentuk yang belum siap, dan hanya dikesan oleh
orang yang kebetulan membuka halaman itu.

Hanya kunci LITERAL diperiksa. Kunci yang dibina dengan penggabungan
(__('task.mark.'.$m->value)) dilangkau kerana bahagiannya hanya diketahui
semasa larian.
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LANG = ROOT / "lang" / "ms"

# __('fail.kunci.bersarang') tanpa gabungan.
PANGGIL = re.compile(r"__\(\s*'([a-z_]+)\.([\w.]+)'\s*[,)]")


def muat(fail: Path) -> dict:
    """Baca kunci peringkat atas dan bersarang daripada fail bahasa."""
    src = fail.read_text(encoding="utf-8")
    kunci = set()
    tumpukan = []

    for baris in src.splitlines():
        # Tatasusunan BERSARANG: kurungan dibuka dan baris tamat di situ.
        #
        # Senarai satu baris seperti 'days_full' => ['MON', 'TUE'] juga
        # sepadan dengan corak "=> [", jadi memadankannya sahaja menolak
        # nama ke dalam tumpukan yang tidak pernah dikeluarkan — dan setiap
        # kunci selepasnya mendapat awalan yang salah. Versi pertama
        # melaporkan 181 kunci "hilang" yang kesemuanya wujud.
        m = re.match(r"\s*'([\w]+)'\s*=>\s*\[\s*$", baris)
        if m:
            tumpukan.append(m.group(1))
            kunci.add(".".join(tumpukan))
            continue

        # Peta bersarang SATU BARIS:
        #   'status' => ['not_updated' => 'Belum Update'],
        #
        # Ini corak biasa untuk kumpulan kecil. Merekodkan hanya 'status'
        # bermakna setiap rujukan kepada service.status.not_updated
        # dilaporkan hilang sedangkan ia ada di depan mata — dan pemeriksa
        # yang menghasilkan amaran palsu akan diabaikan sepenuhnya dalam
        # masa seminggu.
        m = re.match(r"\s*'([\w]+)'\s*=>\s*\[(.+)\]\s*,?\s*$", baris)
        if m:
            induk = m.group(1)
            kunci.add(".".join(tumpukan + [induk]))

            for anak in re.finditer(r"'([\w]+)'\s*=>", m.group(2)):
                kunci.add(".".join(tumpukan + [induk, anak.group(1)]))

            continue

        m = re.match(r"\s*'([\w]+)'\s*=>", baris)
        if m:
            kunci.add(".".join(tumpukan + [m.group(1)]))
            continue

        if re.match(r"\s*\],?\s*$", baris) and tumpukan:
            tumpukan.pop()

    return kunci


kamus = {f.stem: muat(f) for f in LANG.glob("*.php")}

masalah = []
jumlah = 0

for f in list((ROOT / "app").rglob("*.php")) + list((ROOT / "resources" / "views").rglob("*.blade.php")):
    jumlah += 1
    src = f.read_text(encoding="utf-8")

    for m in PANGGIL.finditer(src):
        fail, kunci = m.group(1), m.group(2)

        if fail not in kamus:
            baris = src[: m.start()].count("\n") + 1
            masalah.append(f"  ❌ {f.relative_to(ROOT)}:{baris}  fail bahasa '{fail}' tiada")
            continue

        if kunci not in kamus[fail]:
            baris = src[: m.start()].count("\n") + 1
            masalah.append(f"  ❌ {f.relative_to(ROOT)}:{baris}  {fail}.{kunci}")

if masalah:
    print("\n".join(sorted(set(masalah))))

print(f"Diperiksa {jumlah} fail")

if masalah:
    print(f"❌ {len(set(masalah))} kunci bahasa hilang")
    sys.exit(1)

print("✅ Setiap kunci bahasa literal wujud")
