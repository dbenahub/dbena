# ======================================================================
#  Buang fail berkaitan Projek yang kini usang
#
#      powershell -ExecutionPolicy Bypass -File buang-projek.ps1
#
#  Jadual `projects` digugurkan; fail ini tidak lagi dirujuk oleh
#  mana-mana kod. Skrip ini memadamkannya dengan selamat.
# ======================================================================

$files = @(
    "app\Models\Project.php",
    "app\Policies\ProjectPolicy.php",
    "app\Enums\ProjectStatus.php",
    "database\factories\ProjectFactory.php",
    "database\seeders\ProjectSeeder.php",
    "resources\views\livewire\dashboard\_projects-table.blade.php"
)

Write-Host ""
Write-Host "=== Buang fail Projek yang usang ===" -ForegroundColor Cyan
Write-Host ""

if (-not (Test-Path "artisan")) {
    Write-Host "  Jalankan dari dalam folder dbena-dashboard." -ForegroundColor Red
    exit 1
}

$removed = 0
foreach ($f in $files) {
    if (Test-Path $f) {
        Remove-Item $f -Force
        Write-Host ("  dipadam  " + $f) -ForegroundColor Green
        $removed++
    }
    else {
        Write-Host ("  tiada    " + $f) -ForegroundColor DarkGray
    }
}

Write-Host ""
Write-Host ("$removed fail dipadam.") -ForegroundColor Green
Write-Host ""
Write-Host " SETERUSNYA:" -ForegroundColor Cyan
Write-Host "   powershell -ExecutionPolicy Bypass -File hantar.ps1"
Write-Host "   Kemudian Deploy di Forge."
Write-Host ""
