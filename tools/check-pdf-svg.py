#!/usr/bin/env python3
"""
<svg> sebaris dalam templat PDF.

DomPDF TIDAK memaparkan elemen <svg> sebaris. Ia hanya membaca SVG
melalui <img src="data:image/svg+xml;base64,...">.

Kegagalannya SENYAP dan itulah yang berbahaya: fail PDF dijana seperti
biasa, ia dibuka seperti biasa, dan grafik itu tiada begitu sahaja. Carta
organisasi dieksport tanpa satu pun garisan penyambung — sekumpulan kotak
terapung tanpa hierarki — dan hanya orang yang tahu rupa carta itu akan
perasan.

Untuk grafik dalam PDF: lukis dengan <div> berkedudukan mutlak, atau
benamkan SVG sebagai data-URI dalam <img>.
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PDF_VIEWS = ROOT / "resources" / "views" / "pdf"

# <svg dalam komen Blade {{-- --}} atau komen PHP /* */ tidak dikira.
KOMEN = re.compile(r"\{\{--.*?--\}\}|/\*.*?\*/|//[^\n]*", re.S)
SVG = re.compile(r"<svg\b", re.I)

masalah = []
jumlah = 0

if PDF_VIEWS.is_dir():
    for f in sorted(PDF_VIEWS.rglob("*.blade.php")):
        jumlah += 1
        src = f.read_text(encoding="utf-8")
        bersih = KOMEN.sub(lambda m: " " * len(m.group(0)), src)

        for m in SVG.finditer(bersih):
            baris = src[: m.start()].count("\n") + 1
            masalah.append(f"  ❌ {f.relative_to(ROOT)}:{baris}  <svg> sebaris")

if masalah:
    print("\n".join(masalah))

print(f"Diperiksa {jumlah} templat PDF")

if masalah:
    print(f"❌ {len(masalah)} <svg> sebaris — DomPDF tidak akan memaparkannya")
    print("   Guna <div> berkedudukan mutlak, atau <img src=\"data:image/svg+xml;base64,...\">")
    sys.exit(1)

print("✅ Tiada <svg> sebaris dalam templat PDF")
