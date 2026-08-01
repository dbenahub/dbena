"""
Kesan string PHP yang lexically sah tetapi syntactically rosak.

  'teks ':field' lagi'   →  string, bareword, string  →  ralat parse

Penyemak kurungan saya terlepas ini kerana bilangan petikan seimbang.
Penyemak ini memeriksa apa yang MENGIKUTI setiap string tertutup: ia mesti
pengendali yang sah (. , ) ] ; => dsb.), bukan perkataan bogel.
"""
import os, re, sys

ROOT = "/sessions/kind-compassionate-fermat/mnt/FILE DASHBOARD DBENA - WEBAPPS/dbena-dashboard"

# Apa yang sah selepas string tertutup
OK_AFTER = re.compile(r"""^\s*(
      [.,;)\]}]              # penyambung / penutup
    | =>                     # kunci array
    | \?\?=?                 # null coalesce (+assign)
    | \?                     # ternary
    | :                      # ternary / label / named arg
    | ===|!==|==|!=|<=>      # perbandingan
    | <=|>=|<|>              # perbandingan
    | \|\||&&|\band\b|\bor\b|\bxor\b   # logik
    | \.=|\+=|-=|\*=|/=|%=   # assign
    | [.+\-*/%]              # aritmetik / concat
    | ->|\?->|::             # akses
    | \bas\b|\binstanceof\b # foreach / jenis
    | $                      # hujung baris
)""", re.X)

def scan(path):
    src = open(path, encoding='utf-8').read()
    problems, i, n, line = [], 0, len(src), 1

    while i < n:
        c = src[i]

        if c == '\n':
            line += 1; i += 1; continue

        # langkau komen
        if c == '/' and i+1 < n and src[i+1] == '/':
            j = src.find('\n', i); i = n if j < 0 else j; continue
        if c == '#' and not (i+1 < n and src[i+1] == '['):
            j = src.find('\n', i); i = n if j < 0 else j; continue
        if c == '/' and i+1 < n and src[i+1] == '*':
            j = src.find('*/', i+2)
            if j < 0: i = n
            else:
                line += src.count('\n', i, j); i = j+2
            continue

        # langkau heredoc/nowdoc sepenuhnya
        if src[i:i+3] == "<<<":
            m = re.match(r"<<<\s*(['\"]?)(\w+)\1\r?\n", src[i:])
            if m:
                tag = m.group(2)
                end = re.search(r"^\s*" + tag + r"\b", src[i+m.end():], re.M)
                stop = n if not end else i + m.end() + end.end()
                line += src.count('\n', i, stop); i = stop; continue

        # string
        if c in "'\"":
            q, start_line, i = c, line, i+1
            closed = False
            while i < n:
                if src[i] == '\\': i += 2; continue
                if src[i] == '\n': line += 1
                if src[i] == q: i += 1; closed = True; break
                i += 1

            if not closed:
                problems.append((start_line, "string tidak ditutup"))
                break

            # Apa yang datang selepas penutup?
            rest = src[i:i+40].split('\n')[0]
            if not OK_AFTER.match(rest):
                snippet = src[max(0, i-50):i+30].replace('\n', ' ')
                problems.append((start_line, f"aksara ganjil selepas string: …{snippet.strip()[:80]}"))
            continue

        i += 1

    return problems

total, bad = 0, 0
for dp, dn, fn in os.walk(ROOT):
    dn[:] = [d for d in dn if d not in ('vendor', 'node_modules', '.git')]
    for f in fn:
        if not f.endswith('.php') or f.endswith('.blade.php'):
            continue
        total += 1
        p = os.path.join(dp, f)
        for ln, msg in scan(p):
            bad += 1
            print(f"  ❌ {os.path.relpath(p, ROOT)}:{ln}  {msg}")

print(f"\nDiperiksa {total} fail PHP")
print("✅ Semua string PHP sah" if not bad else f"❌ {bad} masalah")
