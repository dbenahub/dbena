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

# Apa yang berubah
Write-Host "Fail yang berubah:" -ForegroundColor Cyan
git status --short
Write-Host ""

$berubah = @(git status --porcelain).Count
if ($berubah -eq 0) {
    Write-Host "  Tiada perubahan untuk dihantar." -ForegroundColor Yellow
    Write-Host ""
    exit 0
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

# Mesej commit
$mesej = Read-Host "Mesej commit (Enter untuk guna lalai)"
if ([string]::IsNullOrWhiteSpace($mesej)) {
    $mesej = "fix: kemas kini dari pembangunan"
}

git commit -q -m $mesej
if ($LASTEXITCODE -ne 0) { Write-Host "Commit gagal." -ForegroundColor Red; exit 1 }

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
