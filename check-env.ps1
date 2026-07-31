# ======================================================================
#  DBENA Dashboard - semak persekitaran
#
#      powershell -ExecutionPolicy Bypass -File check-env.ps1
#
#  Menyemak PHP, sambungan PHP, Composer, Node dan MySQL.
#  Tidak mengubah apa-apa - hanya melaporkan.
# ======================================================================

$script:problems = @()

function Show-Ok      { param($m) Write-Host ("  [ OK ]  " + $m) -ForegroundColor Green }
function Show-Fail    { param($m) Write-Host ("  [GAGAL] " + $m) -ForegroundColor Red;    $script:problems += $m }
function Show-Warn    { param($m) Write-Host ("  [ ! ]   " + $m) -ForegroundColor Yellow }
function Show-Section { param($m) Write-Host ""; Write-Host $m -ForegroundColor Cyan }

Write-Host ""
Write-Host "=== Semakan Persekitaran DBENA Dashboard ===" -ForegroundColor Cyan

# ---------------------------------------------------------------- PHP
Show-Section "PHP"

$phpPath = (Get-Command php -ErrorAction SilentlyContinue)
if (-not $phpPath) {
    Show-Fail "PHP tidak dijumpai dalam PATH"
}
else {
    $raw = (php -r "echo PHP_VERSION;") 2>$null
    $major = [int](php -r "echo PHP_MAJOR_VERSION;") 2>$null
    $minor = [int](php -r "echo PHP_MINOR_VERSION;") 2>$null

    if ($major -gt 8 -or ($major -eq 8 -and $minor -ge 4)) {
        Show-Ok ("PHP " + $raw)
    }
    else {
        Show-Fail ("PHP " + $raw + " - projek memerlukan 8.4 atau lebih baru")
    }

    # Sambungan wajib
    $needed = @(
        "pdo_mysql", "mbstring", "openssl", "bcmath", "fileinfo",
        "curl", "dom", "xml", "tokenizer", "ctype", "json", "zip"
    )
    $loaded = (php -r "echo implode(',', get_loaded_extensions());") 2>$null
    $loadedList = $loaded -split ","

    $missing = @()
    foreach ($ext in $needed) {
        if ($loadedList -notcontains $ext) { $missing += $ext }
    }

    if ($missing.Count -eq 0) {
        Show-Ok ("Semua " + $needed.Count + " sambungan wajib dimuatkan")
    }
    else {
        Show-Fail ("Sambungan PHP tiada: " + ($missing -join ", "))
    }

    # gd atau imagick diperlukan untuk upload avatar & PDF
    if ($loadedList -contains "gd" -or $loadedList -contains "imagick") {
        Show-Ok "Sambungan imej (gd/imagick) dimuatkan"
    }
    else {
        Show-Fail "gd atau imagick diperlukan untuk avatar dan PDF"
    }

    # intl elok ada tetapi bukan wajib
    if ($loadedList -contains "intl") { Show-Ok "intl dimuatkan" }
    else { Show-Warn "intl tiada - format tarikh mungkin kurang tepat (tidak kritikal)" }
}

# ----------------------------------------------------------- Composer
Show-Section "Composer"

if (Get-Command composer -ErrorAction SilentlyContinue) {
    $cv = (composer --version --no-ansi) 2>$null
    Show-Ok $cv
}
else {
    Show-Fail "Composer tidak dijumpai - https://getcomposer.org/download/"
}

# --------------------------------------------------------------- Node
Show-Section "Node.js"

if (Get-Command node -ErrorAction SilentlyContinue) {
    $nv = (node -v) 2>$null
    $nvNum = [int](($nv -replace "[^\d\.]", "") -split "\." )[0]
    if ($nvNum -ge 20) { Show-Ok ("Node " + $nv) }
    else { Show-Fail ("Node " + $nv + " - projek memerlukan 20 atau lebih baru") }
}
else {
    Show-Fail "Node.js tidak dijumpai - https://nodejs.org"
}

if (Get-Command npm -ErrorAction SilentlyContinue) {
    Show-Ok ("npm " + (npm -v))
}
else {
    Show-Fail "npm tidak dijumpai"
}

# -------------------------------------------------------------- MySQL
Show-Section "MySQL"

if (Get-Command mysql -ErrorAction SilentlyContinue) {
    $mv = (mysql --version) 2>$null
    Show-Ok $mv
}
else {
    Show-Warn "Klien mysql tiada dalam PATH (server mungkin masih berjalan)"
}

# Cuba sambung melalui PHP
if ($phpPath) {
    $probe = 'try { new PDO("mysql:host=127.0.0.1;port=3306", "root", ""); echo "SAMBUNG"; } catch (Throwable $e) { echo "GAGAL: " . $e->getMessage(); }'
    $result = (php -r $probe) 2>$null

    if ($result -like "SAMBUNG*") {
        Show-Ok "Boleh sambung ke MySQL di 127.0.0.1:3306 (root, tiada kata laluan)"

        $dbProbe = 'try { $p = new PDO("mysql:host=127.0.0.1", "root", ""); $r = $p->query("SHOW DATABASES LIKE ''dbena_dashboard''")->fetchAll(); echo count($r) ? "ADA" : "TIADA"; } catch (Throwable $e) { echo "RALAT"; }'
        $dbResult = (php -r $dbProbe) 2>$null

        if ($dbResult -eq "ADA") {
            Show-Ok "Pangkalan data 'dbena_dashboard' wujud"
        }
        else {
            Show-Warn "Pangkalan data 'dbena_dashboard' belum dicipta"
            Write-Host "          Jalankan: mysql -u root -e ""CREATE DATABASE dbena_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;""" -ForegroundColor DarkGray
        }
    }
    else {
        Show-Warn "Tidak dapat sambung ke MySQL dengan root/kosong"
        Write-Host "          Pastikan server MySQL berjalan, dan kemas kini DB_USERNAME/DB_PASSWORD dalam .env" -ForegroundColor DarkGray
    }
}

# ------------------------------------------------------------ Projek
Show-Section "Fail projek"

if (Test-Path "artisan")     { Show-Ok "artisan dijumpai" }     else { Show-Fail "artisan tiada - anda di folder yang salah?" }
if (Test-Path "composer.json") { Show-Ok "composer.json dijumpai" } else { Show-Fail "composer.json tiada" }
if (Test-Path "vendor")      { Show-Ok "vendor/ wujud (composer install sudah dijalankan)" } else { Show-Warn "vendor/ tiada - jalankan: composer install" }
if (Test-Path "node_modules"){ Show-Ok "node_modules/ wujud" } else { Show-Warn "node_modules/ tiada - jalankan: npm install" }
if (Test-Path ".env")        { Show-Ok ".env wujud" } else { Show-Warn ".env tiada - jalankan: copy .env.example .env" }

# ----------------------------------------------------------- Rumusan
Write-Host ""
Write-Host "==================================================" -ForegroundColor Cyan

if ($script:problems.Count -eq 0) {
    Write-Host "Persekitaran sedia. Langkah seterusnya:" -ForegroundColor Green
    Write-Host ""
    Write-Host "  composer install"
    Write-Host "  npm install"
    Write-Host "  copy .env.example .env"
    Write-Host "  php artisan key:generate"
    Write-Host "  php artisan migrate --seed"
    Write-Host "  php artisan storage:link"
    Write-Host "  php artisan test"
    Write-Host ""
}
else {
    Write-Host ("Ada " + $script:problems.Count + " perkara perlu dibetulkan:") -ForegroundColor Red
    Write-Host ""
    foreach ($p in $script:problems) { Write-Host ("  - " + $p) -ForegroundColor Red }
    Write-Host ""
    Write-Host "Cadangan paling mudah untuk Windows: pasang Laragon" -ForegroundColor Yellow
    Write-Host "  https://laragon.org/download/  (Full edition)" -ForegroundColor Yellow
    Write-Host "  Ia membawa PHP, MySQL, Composer dan Node sekali gus." -ForegroundColor Yellow
    Write-Host ""
}
