#!/usr/bin/env python3
"""
Mengesan ikon Phosphor yang menggunakan berat yang tidak diimport.

Phosphor mengedarkan setiap berat sebagai fail CSS berasingan. Menggunakan
`ph-fill` sedangkan hanya `duotone` diimport tidak menghasilkan ralat,
tiada amaran, dan tiada apa dalam konsol — ikon itu sekadar tidak
dirender. Ruang kosong di tempatnya kelihatan seperti pelapik yang salah,
bukan ikon yang hilang.

Ditulis selepas butang buka/tutup sidebar dihantar dengan `ph-bold`.
Anak panahnya tidak pernah muncul, dan butang itu kelihatan seperti ruang
kosong di sebelah pautan Dashboard. Enam ikon lain dalam Peta Perjalanan
sudah tidak kelihatan atas sebab yang sama tanpa disedari.

    python3 tools/check-icons.py
"""

from __future__ import annotations

import re
import sys
from pathlib import Path

AKAR = Path(__file__).resolve().parent.parent

BERAT = ("duotone", "fill", "bold", "regular", "light", "thin")


def berat_diimport() -> set[str]:
    """Berat yang benar-benar diimport oleh stylesheet."""
    css = (AKAR / "resources" / "css" / "app.css").read_text(encoding="utf-8")

    return {
        m.group(1)
        for m in re.finditer(r"@phosphor-icons/web/(\w+)", css)
        if m.group(1) in BERAT
    }


def main() -> int:
    ada = berat_diimport()

    if not ada:
        print("❌ Tiada berat Phosphor diimport dalam resources/css/app.css")
        return 1

    tiada = [b for b in BERAT if b not in ada]
    corak = re.compile(r"\bph-(" + "|".join(tiada) + r")\b") if tiada else None

    masalah: list[str] = []
    fail_diperiksa = 0

    for fail in (AKAR / "resources" / "views").rglob("*.blade.php"):
        fail_diperiksa += 1

        if corak is None:
            continue

        teks = fail.read_text(encoding="utf-8")

        # Kosongkan komen Blade sambil MENGEKALKAN baris baharu, supaya
        # nombor baris kekal tepat. Komen yang menerangkan pepijat ini
        # menyebut nama berat itu, dan menandakannya menghasilkan bunyi
        # bising yang menyembunyikan ikon sebenar yang rosak.
        tanpa_komen = re.sub(
            r"\{\{--.*?--\}\}",
            lambda m: re.sub(r"[^\n]", " ", m.group(0)),
            teks,
            flags=re.S,
        )

        for no, baris in enumerate(tanpa_komen.split("\n"), 1):
            if baris.lstrip().startswith(("*", "//", "/*")):
                continue

            for m in corak.finditer(baris):
                masalah.append(
                    f"  {fail.relative_to(AKAR)}:{no}\n"
                    f"      ph-{m.group(1)} — berat itu tidak diimport"
                )

    print(f"Diperiksa {fail_diperiksa} fail Blade")
    print(f"Berat diimport: {', '.join(sorted(ada))}")

    if masalah:
        print(f"\n❌ {len(masalah)} ikon tidak akan dirender:\n")
        print("\n\n".join(masalah))
        print(
            "\nGunakan berat yang diimport, atau tambah import ke "
            "resources/css/app.css.\nSetiap berat tambahan ialah fail CSS "
            "penuh, jadi menukar ikon biasanya lebih murah."
        )
        return 1

    print("✅ Setiap ikon menggunakan berat yang diimport")
    return 0


if __name__ == "__main__":
    sys.exit(main())
