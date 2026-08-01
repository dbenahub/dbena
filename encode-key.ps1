# ======================================================================
#  Enkod fail kunci service account Google ke base64
#
#      powershell -ExecutionPolicy Bypass -File encode-key.ps1
#
#  Hasilnya disalin terus ke clipboard - tampal ke Forge Environment
#  sebagai GOOGLE_SERVICE_ACCOUNT_BASE64
# ======================================================================

Write-Host ""
Write-Host "=== Enkod kunci service account ===" -ForegroundColor Cyan
Write-Host ""

# Cari fail JSON dalam folder Downloads
$downloads = Join-Path $env:USERPROFILE "Downloads"
$candidates = @(Get-ChildItem -Path $downloads -Filter "*.json" -ErrorAction SilentlyContinue |
                Sort-Object LastWriteTime -Descending | Select-Object -First 10)

$path = $null

if ($candidates.Count -gt 0) {
    Write-Host "Fail JSON terkini dalam Downloads:" -ForegroundColor Cyan
    Write-Host ""
    for ($i = 0; $i -lt $candidates.Count; $i++) {
        Write-Host ("  [" + ($i + 1) + "] " + $candidates[$i].Name)
    }
    Write-Host ""
    $pick = Read-Host "Nombor fail kunci anda (atau Enter untuk taip laluan penuh)"

    if ($pick -match '^\d+$' -and [int]$pick -ge 1 -and [int]$pick -le $candidates.Count) {
        $path = $candidates[[int]$pick - 1].FullName
    }
}

if (-not $path) {
    $path = Read-Host "Laluan penuh ke fail kunci JSON"
    $path = $path.Trim('"')
}

if (-not (Test-Path $path)) {
    Write-Host ""
    Write-Host "  Fail tidak dijumpai: $path" -ForegroundColor Red
    exit 1
}

# Sahkan ia benar-benar kunci service account
try {
    $json = Get-Content $path -Raw | ConvertFrom-Json
}
catch {
    Write-Host ""
    Write-Host "  Fail ini bukan JSON yang sah." -ForegroundColor Red
    exit 1
}

if (-not $json.client_email -or -not $json.private_key) {
    Write-Host ""
    Write-Host "  Ini bukan fail kunci service account." -ForegroundColor Red
    Write-Host "  Fail yang betul mengandungi 'client_email' dan 'private_key'." -ForegroundColor DarkGray
    exit 1
}

$encoded = [Convert]::ToBase64String([IO.File]::ReadAllBytes($path))

Write-Host ""
Write-Host "==================================================" -ForegroundColor Green
Write-Host " SIAP" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green
Write-Host ""
Write-Host " Emel service account:" -ForegroundColor Cyan
Write-Host ("   " + $json.client_email) -ForegroundColor Yellow
Write-Host ""
Write-Host " KONGSI GOOGLE SHEET ANDA DENGAN EMEL DI ATAS (Viewer)" -ForegroundColor Cyan
Write-Host ""
Write-Host (" Panjang base64: " + $encoded.Length + " aksara") -ForegroundColor DarkGray
Write-Host ""

# Salin ke clipboard
try {
    Set-Clipboard -Value ("GOOGLE_SERVICE_ACCOUNT_BASE64=" + $encoded)
    Write-Host " Sudah disalin ke clipboard." -ForegroundColor Green
    Write-Host " Tampal terus ke Forge -> Settings -> Environment" -ForegroundColor Green
}
catch {
    Write-Host " Clipboard gagal. Salin baris di bawah secara manual:" -ForegroundColor Yellow
    Write-Host ""
    Write-Host ("GOOGLE_SERVICE_ACCOUNT_BASE64=" + $encoded)
}

Write-Host ""
Write-Host " Jangan lupa tetapkan juga:" -ForegroundColor Cyan
Write-Host "   DBENA_SHEETS_DRIVER=service"
Write-Host ""
Write-Host " Kemudian jalankan: php artisan config:clear" -ForegroundColor Cyan
Write-Host ""
