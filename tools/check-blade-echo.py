#!/usr/bin/env python3
"""
Mengesan {{ $baris['kunci'] }} dalam Blade yang sebenarnya memaparkan
tatasusunan atau Collection.

PHP melemparkan "htmlspecialchars(): Argument #1 must be of type string,
array given" — halaman 500 penuh, bukan amaran. Ia hanya muncul apabila
templat itu benar-benar dirender, jadi laluan yang jarang digunakan
seperti eksport PDF boleh rosak berminggu-minggu tanpa disedari.

Pemeriksa ini membaca setiap `return [...]` dalam app/Services dan
app/Livewire, menandakan kunci yang nilainya jelas bukan skalar, kemudian
mengimbas Blade untuk paparan terus kunci-kunci tersebut.

    python3 tools/check-blade-echo.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

AKAR = Path(__file__).resolve().parent.parent

# Ungkapan yang jelas menghasilkan tatasusunan atau Collection.
BUKAN_SKALAR = (
    re.compile(r"^\["),
    re.compile(r"^collect\("),
    re.compile(r"->(map|filter|pluck|values|keys|groupBy|sortBy|sortByDesc|take|flatMap|merge|all)\("),
    re.compile(r"->all\(\)$"),
    re.compile(r"^\$\w+Rows\b"),
    re.compile(r"^\$lines\b"),
)

# Kunci yang selamat walaupun sepadan corak di atas — nilai skalar yang
# kebetulan dibina daripada koleksi.
KECUALI = {"implode", "count", "sum", "first", "last", "label", "name"}


def kunci_bukan_skalar() -> dict[str, str]:
    """
    Kunci yang SENTIASA bukan skalar -> fail yang mengisytiharkannya.

    Nama kunci digunakan semula merentas perkhidmatan yang tidak berkaitan.
    'owner' ialah objek dalam satu tempat dan nama dalam tempat lain;
    'target' ialah nombor dalam satu tempat dan peta lajur dalam tempat
    lain. Menandakan setiap penggunaan menghasilkan bunyi bising, dan
    pemeriksa yang bising akan diabaikan — yang lebih teruk daripada tiada
    pemeriksa langsung.

    Jadi kunci hanya dilaporkan apabila SETIAP tempat yang menghasilkannya
    menghasilkan bukan skalar.
    """
    bukan_skalar: dict[str, str] = {}
    pernah_skalar: set[str] = set()

    for fail in list((AKAR / "app").rglob("*.php")):
        teks = fail.read_text(encoding="utf-8")

        kaedah_array = set(
            re.findall(
                r"function (\w+)\([^)]*\)\s*:\s*(?:array|Collection"
                r"|\\?Illuminate\\Support\\Collection)\b",
                teks,
            )
        )

        for m in re.finditer(r"'(\w+)' => (.+?),?\n", teks):
            kunci, nilai = m.group(1), m.group(2).strip()

            if kunci in KECUALI:
                continue

            panggil = re.match(r"\$this->(\w+)\(", nilai)

            if any(p.search(nilai) for p in BUKAN_SKALAR) or (
                panggil and panggil.group(1) in kaedah_array
            ):
                bukan_skalar.setdefault(kunci, fail.name)
            else:
                pernah_skalar.add(kunci)

    return {k: v for k, v in bukan_skalar.items() if k not in pernah_skalar}


def main() -> int:
    bukan_skalar = kunci_bukan_skalar()
    masalah: list[str] = []

    for fail in (AKAR / "resources" / "views").rglob("*.blade.php"):
        teks = fail.read_text(encoding="utf-8")

        for no, baris in enumerate(teks.split("\n"), 1):
            # Hanya paparan TERUS: {{ $apa['kunci'] }} tanpa panggilan lain.
            for m in re.finditer(r"\{\{\s*\$(\w+)\['(\w+)'\]\s*\}\}", baris):
                pemboleh, kunci = m.group(1), m.group(2)

                if kunci in bukan_skalar:
                    rel = fail.relative_to(AKAR)
                    masalah.append(
                        f"  {rel}:{no}\n"
                        f"      {{{{ ${pemboleh}['{kunci}'] }}}}  "
                        f"— '{kunci}' dihasilkan sebagai bukan skalar "
                        f"({bukan_skalar[kunci]})"
                    )

    jumlah = len(list((AKAR / "resources" / "views").rglob("*.blade.php")))

    if masalah:
        print(f"Diperiksa {jumlah} fail Blade")
        print(f"\n❌ {len(masalah)} paparan berkemungkinan tatasusunan:\n")
        print("\n\n".join(masalah))
        print(
            "\nPaparan tatasusunan menghempaskan halaman dengan "
            "htmlspecialchars TypeError.\n"
            "Cantumkan dengan implode(), atau ulang dengan @foreach."
        )
        return 1

    print(f"Diperiksa {jumlah} fail Blade")
    print("✅ Tiada paparan tatasusunan")
    return 0


if __name__ == "__main__":
    sys.exit(main())
