"""
Kesan pembolehubah yang digunakan di dalam closure PHP tetapi tidak diimport
melalui klausa `use`.

  ->map(function ($item) use ($allRows) {
      return helper($item, $rows);        ← $rows tidak wujud → ralat runtime
  })

Ini tidak dapat dikesan oleh semakan kurungan mahupun semakan string, dan
hanya muncul sebagai 500 apabila laluan kod itu dijalankan. Ia benar-benar
berlaku dalam projek ini.

Skop: hanya `function (...) use (...) { ... }` — arrow fn (`fn () =>`)
menangkap skop secara automatik, jadi tidak perlu disemak.
"""
import os, re, sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

# Pembolehubah yang sentiasa sah dalam mana-mana skop
BUILTIN = {
    'this', 'GLOBALS', '_SERVER', '_GET', '_POST', '_SESSION', '_COOKIE',
    '_FILES', '_ENV', '_REQUEST', 'php_errormsg', 'http_response_header',
}

def find_closures(src):
    """Hasilkan (offset_mula_badan, params, uses, baris) untuk setiap closure."""
    for m in re.finditer(
        r'function\s*\(([^)]*)\)\s*(?::\s*[\w\\|?]+\s*)?use\s*\(([^)]*)\)\s*(?::\s*[\w\\|?]+\s*)?\{',
        src
    ):
        params = set(re.findall(r'\$(\w+)', m.group(1)))
        uses = set(re.findall(r'\$(\w+)', m.group(2)))
        line = src.count('\n', 0, m.start()) + 1
        yield m.end() - 1, params, uses, line

def body_of(src, brace_pos):
    """Ekstrak badan closure dengan mengira kurungan kurawal."""
    depth, i, n = 0, brace_pos, len(src)
    while i < n:
        c = src[i]
        if c in "'\"":
            q, i = c, i + 1
            while i < n:
                if src[i] == '\\': i += 2; continue
                if src[i] == q: break
                i += 1
        elif c == '{':
            depth += 1
        elif c == '}':
            depth -= 1
            if depth == 0:
                return src[brace_pos + 1:i]
        i += 1
    return ''

problems = 0
scanned = 0

for dp, dn, fn in os.walk(ROOT):
    dn[:] = [d for d in dn if d not in ('vendor', 'node_modules', '.git')]
    for f in fn:
        if not f.endswith('.php') or f.endswith('.blade.php'):
            continue
        path = os.path.join(dp, f)
        src = open(path, encoding='utf-8').read()
        scanned += 1

        for pos, params, uses, line in find_closures(src):
            body = body_of(src, pos)
            if not body:
                continue

            # Pembolehubah yang ditugaskan di dalam badan adalah tempatan
            assigned = set(re.findall(r'\$(\w+)\s*=(?!=)', body))
            # foreach (expr as $v)  dan  foreach (expr as $k => $v)
            # [^{]*? diperlukan kerana expr boleh mengandungi kurungan bersarang:
            #   foreach (Model::whereIn('id', array_keys($x))->get() as $row)
            assigned |= set(re.findall(r'foreach\s*\([^{]*?\bas\s+\$(\w+)', body))
            assigned |= set(re.findall(r'\bas\s+\$\w+\s*=>\s*\$(\w+)', body))
            assigned |= set(re.findall(r'catch\s*\([^)]*\$(\w+)\s*\)', body))
            # list($a, $b) = ... dan [$a, $b] = ...
            assigned |= set(re.findall(r'\[\s*\$(\w+)[^\]]*\]\s*=(?!=)', body))

            # Closure bersarang membawa skop sendiri - abaikan isinya
            inner = set()
            for ipos, iparams, iuses, _ in find_closures(body):
                inner |= iparams
            # arrow fn juga
            inner |= set(re.findall(r'fn\s*\(([^)]*)\)', body)) and set(
                re.findall(r'fn\s*\([^)]*\$(\w+)', body)
            ) or set()

            used = set(re.findall(r'\$(\w+)', body))
            unknown = used - params - uses - assigned - inner - BUILTIN

            if unknown:
                rel = os.path.relpath(path, ROOT)
                for v in sorted(unknown):
                    print(f"  ❌ {rel}:{line}  ${v} digunakan tetapi tiada dalam use()")
                    problems += 1

print(f"\nDiperiksa {scanned} fail PHP")
print("✅ Semua closure lengkap" if not problems else f"❌ {problems} pembolehubah hilang")
sys.exit(1 if problems else 0)
