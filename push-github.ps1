# ======================================================================
#  DBENA Dashboard - hantar kod ke GitHub
#
#      powershell -ExecutionPolicy Bypass -File push-github.ps1
#
#  Skrip ini buat semuanya: init repo, commit, sambung ke GitHub, push.
# ======================================================================

$RepoUrl = "https://github.com/dbenahub/dbena.git"

function Tajuk { param($m) Write-Host ""; Write-Host ("--- " + $m + " ---") -ForegroundColor Cyan }
function Baik  { param($m) Write-Host ("  OK    " + $m) -ForegroundColor Green }
function Ralat { param($m) Write-Host ("  RALAT " + $m) -ForegroundColor Red }
function Nota  { param($m) Write-Host ("        " + $m) -ForegroundColor DarkGray }

Write-Host ""
Write-Host "=== Hantar DBENA Dashboard ke GitHub ===" -ForegroundColor Cyan

# ---------------------------------------------------- LANGKAH 1: Git
Tajuk "LANGKAH 1 dari 5 - Semak Git"

$v = (git --version) 2>$null
if (-not $v) {
    Ralat "Git tidak dipasang"
    Nota "Muat turun: https://git-scm.com/download/win"
    Nota "Pasang, TUTUP terminal ini, buka semula, jalankan skrip lagi."
    exit 1
}
Baik $v

# ------------------------------------------------ LANGKAH 2: Folder
Tajuk "LANGKAH 2 dari 5 - Semak folder"

if (-not (Test-Path "artisan")) {
    Ralat "Anda bukan dalam folder projek"
    Nota "Jalankan dahulu:"
    Nota '  cd "C:\Users\User\Downloads\FILE DASHBOARD DBENA - WEBAPPS\dbena-dashboard"'
    exit 1
}
Baik ("Folder betul: " + (Get-Location).Path)

# -------------------------------------------------- LANGKAH 3: Repo
Tajuk "LANGKAH 3 dari 5 - Sediakan repo"

if (Test-Path ".git") {
    Write-Host "  Folder .git sedia ada - membuang dan mula semula..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force ".git" -ErrorAction SilentlyContinue
    if (Test-Path ".git") {
        Ralat "Tidak dapat membuang .git"
        Nota "Tutup VS Code / GitHub Desktop jika terbuka, kemudian cuba lagi."
        exit 1
    }
}

git init -b main | Out-Null
git config user.name  "Ahmad Nizam"
git config user.email "ahmadnizamuddinrosnan@gmail.com"
git config core.autocrlf false
Baik "Repo dimulakan (branch: main)"

# ----------------------------------------- LANGKAH 4: Semak & commit
Tajuk "LANGKAH 4 dari 5 - Semak keselamatan dan commit"

git add -A

$bahaya = $false

foreach ($f in @(".env", "auth.json")) {
    git ls-files --error-unmatch $f 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) { Ralat ($f + " akan masuk repo"); $bahaya = $true }
    else { Baik ($f + " dilindungi") }
}

foreach ($d in @("vendor", "node_modules")) {
    $n = @(git ls-files $d).Count
    if ($n -gt 0) { Ralat ($d + "/ ada " + $n + " fail"); $bahaya = $true }
    else { Baik ($d + "/ diabaikan") }
}

if ($bahaya) {
    Write-Host ""
    Ralat "Dibatalkan demi keselamatan"
    exit 1
}

$jumlah = @(git diff --cached --name-only).Count
Baik ($jumlah.ToString() + " fail sedia untuk dihantar")

$mesej = @'
feat: DBENA executive dashboard

Bina semula prototaip .dc.html sebagai aplikasi Laravel 12 produksi.

* Auth: log masuk berasingan user/admin, OTP emel, rate limiting
* RBAC dikuatkuasakan di backend melalui middleware + Policy
* Dashboard, Detail Servis, Laporan, Tetapan, Admin Panel
* Integrasi Google Sheet: susun atur berbilang servis, 3 pencetus sync
* Laporan Prestasi Pemilik dengan ulasan naratif + eksport PDF
* Dwibahasa BM/EN penuh, mobile-first responsive
* 167 ujian merangkumi formula bisnes, RBAC dan penghuraian sheet
'@

git commit -q -m $mesej
if ($LASTEXITCODE -ne 0) { Ralat "Commit gagal"; exit 1 }
Baik "Commit selesai"

# -------------------------------------------------- LANGKAH 5: Push
Tajuk "LANGKAH 5 dari 5 - Hantar ke GitHub"

git remote remove origin 2>&1 | Out-Null
git remote add origin $RepoUrl
Baik ("Disambung ke " + $RepoUrl)

Write-Host ""
Write-Host "  Menghantar... GitHub mungkin minta anda log masuk." -ForegroundColor Yellow
Write-Host "  Jika tetingkap browser terbuka, klik 'Authorize'." -ForegroundColor Yellow
Write-Host ""

git push -u origin main

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Green
    Write-Host " BERJAYA" -ForegroundColor Green
    Write-Host "==================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host " Kod sudah ada di GitHub."
    Write-Host " Semak: https://github.com/dbenahub/dbena"
    Write-Host ""
    Write-Host " SETERUSNYA:" -ForegroundColor Cyan
    Write-Host "   1. Kembali ke halaman Forge"
    Write-Host "   2. Tekan F5 untuk muat semula penuh"
    Write-Host "   3. Dropdown Branch akan papar 'main'"
    Write-Host ""
}
else {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Red
    Write-Host " PUSH GAGAL" -ForegroundColor Red
    Write-Host "==================================================" -ForegroundColor Red
    Write-Host ""
    Write-Host " Sebab paling biasa:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host " 1. Kata laluan ditolak"
    Write-Host "    GitHub tidak terima kata laluan lagi. Anda perlu token:"
    Write-Host "      - Buka https://github.com/settings/tokens"
    Write-Host "      - Generate new token (classic)"
    Write-Host "      - Tandakan kotak 'repo'"
    Write-Host "      - Salin token, guna sebagai KATA LALUAN semasa diminta"
    Write-Host ""
    Write-Host " 2. Repo sudah ada fail (README)"
    Write-Host "    Jalankan: git push -u origin main --force"
    Write-Host ""
    Write-Host " 3. Tiada akses ke organisasi dbenahub"
    Write-Host "    Minta pemilik org beri anda akses Write ke repo ini."
    Write-Host ""
}
