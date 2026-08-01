# ======================================================================
#  Enkod fail kunci service account Google ke base64
#
#      powershell -ExecutionPolicy Bypass -File encode-key.ps1
#
#  Hasilnya ditulis ke fail teks DAN dibuka dalam Notepad,
#  supaya anda boleh salin dengan pasti (Ctrl+A, Ctrl+C).
# ======================================================================

Write-Host ""
Write-Host "=== Enkod kunci service account ===" -ForegroundColor Cyan
Write-Host ""

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
    Write-Host "Taip nombor fail kunci anda, kemudian tekan Enter:" -ForegroundColor Yellow
    $pick = Read-Host "Nombor"

    if ($pick -match '^\d+$' -and [int]$pick -ge 1 -and [int]$pick -le $candidates.Count) {
        $path = $candidates[[int]$pick - 1].FullName
    }
}

if (-not $path) {
    $path = (Read-Host "Laruan penuh ke fail kunci JSON").Trim('"')
}

if (-not (Test-Path $path)) {
    Write-Host ""
    Write-Host "  Fail tidak dijumpai: $path" -ForegroundColor Red
    exit 1
}

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
$line = "GOOGLE_SERVICE_ACCOUNT_BASE64=" + $encoded

# Tulis ke fail - lebih boleh dipercayai daripada clipboard
$out = Join-Path (Get-Location) "google-key-base64.txt"
Set-Content -Path $out -Value $line -Encoding ASCII -NoNewline

Write-Host ""
Write-Host "==================================================" -ForegroundColor Green
Write-Host " SIAP" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green
Write-Host ""
Write-Host " Emel service account:" -ForegroundColor Cyan
Write-Host ("   " + $json.client_email) -ForegroundColor Yellow
Write-Host ""
Write-Host (" Panjang baris: " + $line.Length + " aksara") -ForegroundColor DarkGray
Write-Host ""

if ($line.Length -lt 500) {
    Write-Host " AMARAN: baris ini nampak terlalu pendek." -ForegroundColor Red
    Write-Host " Kunci sebenar biasanya 2,000-4,000 aksara." -ForegroundColor Red
    Write-Host ""
}

# Cuba clipboard juga
try { Set-Clipboard -Value $line; Write-Host " Disalin ke clipboard." -ForegroundColor Green }
catch { Write-Host " Clipboard tidak tersedia." -ForegroundColor Yellow }

Write-Host " Ditulis ke: google-key-base64.txt" -ForegroundColor Green
Write-Host ""
Write-Host " LANGKAH SETERUSNYA:" -ForegroundColor Cyan
Write-Host "   1. Notepad akan terbuka dengan baris penuh"
Write-Host "   2. Tekan Ctrl+A kemudian Ctrl+C untuk salin SEMUANYA"
Write-Host "   3. Tampal ke Forge -> Settings -> Environment"
Write-Host "   4. Save, kemudian jalankan: php artisan config:clear"
Write-Host ""
Write-Host " Selepas siap, PADAM google-key-base64.txt - ia mengandungi kunci peribadi." -ForegroundColor Yellow
Write-Host ""

Start-Process notepad.exe $out
