# ======================================================================
#  Hantar perubahan ke GitHub
#
#      powershell -ExecutionPolicy Bypass -File hantar.ps1
#
#  Guna skrip ini setiap kali ada pembetulan untuk dihantar ke server.
# ======================================================================

Write-Host ""
Write-Host "=== Hantar perubahan ke GitHub ===" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "artisan")) {
    Write-Host "  RALAT: anda bukan dalam folder projek" -ForegroundColor Red
    Write-Host '         cd "C:\Users\User\Downloads\FILE DASHBOARD DBENA - WEBAPPS\dbena-dashboard"' -ForegroundColor DarkGray
    exit 1
}

if (-not (Test-Path ".git")) {
    Write-Host "  RALAT: repo Git belum wujud" -ForegroundColor Red
    Write-Host "         Jalankan push-github.ps1 dahulu" -ForegroundColor DarkGray
    exit 1
}

# Buang fail kunci tertinggal.
# Git menulis .git\*.lock semasa bekerja dan memadamnya selepas siap. Jika
# sesuatu terhenti separuh jalan, fail itu kekal dan setiap arahan git
# selepas itu gagal dengan "Another git process seems to be running".
foreach ($kunci in @("HEAD.lock", "index.lock", "config.lock", "objects\maintenance.lock")) {
    $laluan = Join-Path ".git" $kunci
    if (Test-Path $laluan) {
        Remove-Item $laluan -Force -ErrorAction SilentlyContinue
        Write-Host ("  Fail kunci tertinggal dibuang: " + $kunci) -ForegroundColor DarkGray
    }
}

# Ada DUA jenis kerja belum siap, dan mengelirukan keduanya bermakna
# commit yang sudah wujud tidak pernah sampai ke GitHub:
#
#   1. Fail berubah yang belum di-commit
#   2. Commit yang sudah dibuat tetapi belum ditolak ke GitHub
#
# Skrip ini mesti mengendalikan kedua-duanya.

$berubah = @(git status --porcelain).Count

git fetch --quiet 2>&1 | Out-Null
$cawangan = (git rev-parse --abbrev-ref HEAD).Trim()
$belumTolak = @(git log --oneline "origin/$cawangan..HEAD" 2>$null).Count

if ($berubah -eq 0 -and $belumTolak -eq 0) {
    Write-Host "  Semuanya sudah berada di GitHub." -ForegroundColor Green
    Write-Host ""
    exit 0
}

if ($belumTolak -gt 0) {
    Write-Host ("Commit menunggu untuk dihantar (" + $belumTolak + "):") -ForegroundColor Cyan
    git log --oneline "origin/$cawangan..HEAD"
    Write-Host ""
}

if ($berubah -eq 0) {
    # Tiada fail baharu untuk di-commit, tetapi ada commit menunggu.
    # Terus ke push — JANGAN keluar.
    Write-Host "Tiada fail baharu untuk di-commit." -ForegroundColor DarkGray
    Write-Host ""
}
else {
    Write-Host "Fail yang berubah:" -ForegroundColor Cyan
    git status --short
    Write-Host ""
}

# Semakan keselamatan
git add -A

$bahaya = $false
foreach ($f in @(".env", "auth.json")) {
    git ls-files --error-unmatch $f 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host ("  RALAT: " + $f + " akan masuk repo") -ForegroundColor Red
        $bahaya = $true
    }
}
foreach ($d in @("vendor", "node_modules")) {
    if (@(git ls-files $d).Count -gt 0) {
        Write-Host ("  RALAT: " + $d + "/ akan masuk repo") -ForegroundColor Red
        $bahaya = $true
    }
}
if ($bahaya) { Write-Host ""; Write-Host "Dibatalkan." -ForegroundColor Red; exit 1 }

# Mesej commit — hanya jika ada sesuatu untuk di-commit
if ($berubah -gt 0) {
    $mesej = Read-Host "Mesej commit (Enter untuk guna lalai)"
    if ([string]::IsNullOrWhiteSpace($mesej)) {
        $mesej = "fix: kemas kini dari pembangunan"
    }

    git commit -q -m $mesej
    if ($LASTEXITCODE -ne 0) { Write-Host "Commit gagal." -ForegroundColor Red; exit 1 }
}

Write-Host ""
Write-Host "Menghantar ke GitHub..." -ForegroundColor Yellow
git push

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Green
    Write-Host " BERJAYA DIHANTAR" -ForegroundColor Green
    Write-Host "==================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host " SETERUSNYA: buka Forge, klik butang Deploy" -ForegroundColor Cyan
    Write-Host ""
}
else {
    Write-Host ""
    Write-Host " PUSH GAGAL" -ForegroundColor Red
    Write-Host " Jika diminta kata laluan, guna Personal Access Token:" -ForegroundColor Yellow
    Write-Host " https://github.com/settings/tokens" -ForegroundColor Yellow
    Write-Host ""
}
