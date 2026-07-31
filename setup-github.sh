#!/usr/bin/env bash
# DBENA Dashboard — sediakan repo & tolak ke GitHub
set -euo pipefail

echo -e "\n=== DBENA Dashboard — setup GitHub ===\n"

command -v git >/dev/null || { echo "Git tidak dijumpai."; exit 1; }

[ -d .git ] && { echo "Membuang .git sedia ada..."; rm -rf .git; }

git init -b main
git config user.name  "Ahmad Nizam"
git config user.email "ahmadnizamuddinrosnan@gmail.com"

echo -e "\nSemakan keselamatan..."
git add -A
leaked=0

for f in .env auth.json storage/app/google/service-account.json; do
  if git ls-files --error-unmatch "$f" >/dev/null 2>&1; then
    echo "  BAHAYA: $f akan di-commit!"; leaked=1
  else
    echo "  OK  $f dilindungi"
  fi
done

for d in vendor node_modules public/build; do
  n=$(git ls-files "$d" | wc -l)
  if [ "$n" -gt 0 ]; then echo "  BAHAYA: $d/ ada $n fail!"; leaked=1
  else echo "  OK  $d/ diabaikan"; fi
done

[ "$leaked" -eq 1 ] && { echo -e "\nDibatalkan. Betulkan .gitignore dahulu."; exit 1; }

echo -e "\n$(git diff --cached --name-only | wc -l) fail sedia untuk commit.\n"

git commit -q -m "feat: DBENA executive dashboard

Bina semula prototaip .dc.html sebagai aplikasi Laravel 12 produksi.

- Auth: log masuk berasingan user/admin, OTP emel, rate limiting
- RBAC dikuatkuasakan di backend melalui middleware + Policy
- Dashboard, Detail Servis, Laporan, Tetapan, Admin Panel
- Integrasi Google Sheet: susun atur berbilang servis, 3 pencetus sync
- Laporan Prestasi Pemilik dengan ulasan naratif + eksport PDF
- Dwibahasa BM/EN penuh, mobile-first responsive
- 167 ujian merangkumi formula bisnes, RBAC dan penghuraian sheet"

echo "Commit pertama selesai."
echo
echo "Langkah seterusnya:"
echo "  1. Cipta repo PRIVATE di https://github.com/new (kosong, tiada README)"
echo "  2. git remote add origin https://github.com/<username>/dbena-dashboard.git"
echo "     git push -u origin main"
