# ======================================================================
#  DBENA Dashboard - sediakan repo Git & tolak ke GitHub
#
#  Jalankan dari dalam folder dbena-dashboard:
#      powershell -ExecutionPolicy Bypass -File setup-github.ps1
# ======================================================================

Write-Host ""
Write-Host "=== DBENA Dashboard - setup GitHub ===" -ForegroundColor Cyan
Write-Host ""

# --- 1. Semak Git ------------------------------------------------------
$gitVersion = (git --version) 2>$null
if (-not $gitVersion) {
    Write-Host "Git tidak dijumpai. Pasang dari https://git-scm.com" -ForegroundColor Red
    exit 1
}
Write-Host "Git dijumpai: $gitVersion" -ForegroundColor DarkGray

# --- 2. Semak kita berada di folder yang betul -------------------------
if (-not (Test-Path "artisan")) {
    Write-Host "Fail 'artisan' tidak dijumpai." -ForegroundColor Red
    Write-Host "Jalankan skrip ini dari dalam folder dbena-dashboard." -ForegroundColor Red
    exit 1
}

# --- 3. Buang repo lama jika ada ---------------------------------------
if (Test-Path ".git") {
    Write-Host "Membuang folder .git sedia ada..." -ForegroundColor Yellow
    Remove-Item -Recurse -Force ".git"
}

# --- 4. Mula repo bersih -----------------------------------------------
git init -b main | Out-Null
git config user.name  "Ahmad Nizam"
git config user.email "ahmadnizamuddinrosnan@gmail.com"
git config core.autocrlf false

Write-Host ""
Write-Host "Semakan keselamatan..." -ForegroundColor Cyan

git add -A

$leaked = $false

# Fail rahsia yang tidak boleh masuk repo
$secrets = @(".env", "auth.json", "storage/app/google/service-account.json")
foreach ($file in $secrets) {
    git ls-files --error-unmatch $file 2>&1 | Out-Null
    if ($LASTEXITCODE -eq 0) {
        Write-Host ("  BAHAYA  " + $file + " akan di-commit") -ForegroundColor Red
        $leaked = $true
    }
    else {
        Write-Host ("  OK      " + $file + " dilindungi") -ForegroundColor Green
    }
}

# Folder besar yang patut diabaikan
$folders = @("vendor", "node_modules", "public/build")
foreach ($folder in $folders) {
    $count = @(git ls-files $folder).Count
    if ($count -gt 0) {
        Write-Host ("  BAHAYA  " + $folder + "/ ada " + $count + " fail") -ForegroundColor Red
        $leaked = $true
    }
    else {
        Write-Host ("  OK      " + $folder + "/ diabaikan") -ForegroundColor Green
    }
}

if ($leaked) {
    Write-Host ""
    Write-Host "Dibatalkan. Betulkan .gitignore dahulu." -ForegroundColor Red
    exit 1
}

# --- 5. Commit pertama -------------------------------------------------
$fileCount = @(git diff --cached --name-only).Count

Write-Host ""
Write-Host ("$fileCount fail sedia untuk commit.") -ForegroundColor Cyan
Write-Host ""

# Mesej commit disimpan dalam here-string supaya PowerShell tidak
# cuba menghurai baris yang bermula dengan tanda '-'.
$commitMessage = @'
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

git commit -q -m $commitMessage

if ($LASTEXITCODE -ne 0) {
    Write-Host "Commit gagal." -ForegroundColor Red
    exit 1
}

Write-Host "Commit pertama selesai." -ForegroundColor Green
Write-Host ""

# --- 6. Arahan seterusnya ---------------------------------------------
Write-Host "LANGKAH SETERUSNYA" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1. Cipta repo PRIVATE di https://github.com/new"
Write-Host "     Nama       : dbena-dashboard"
Write-Host "     Visibility : Private"
Write-Host "     PENTING    : jangan tanda 'Add a README' - repo mesti kosong"
Write-Host ""
Write-Host "  2. Salin dan jalankan (ganti USERNAME):" -ForegroundColor Yellow
Write-Host ""
Write-Host "     git remote add origin https://github.com/USERNAME/dbena-dashboard.git"
Write-Host "     git push -u origin main"
Write-Host ""
