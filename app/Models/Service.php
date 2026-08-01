<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasBilingualAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    use HasBilingualAttributes, HasFactory;

    protected $fillable = [
        'key', 'name_ms', 'name_en', 'icon_class', 'monthly_target', 'chart_color', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'monthly_target' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Warna carta yang belum digunakan oleh mana-mana servis.
     *
     * Dua servis berkongsi warna bermakna carta trend menjadi mustahil
     * dibaca, dan tiada apa dalam UI yang menghalangnya berlaku. Roda ini
     * memilih rona yang paling jauh daripada yang sedia ada.
     */
    public static function nextChartColor(): string
    {
        $roda = [
            'oklch(0.6 0.2 350)', 'oklch(0.75 0.15 85)', 'oklch(0.6 0.16 250)',
            'oklch(0.65 0.15 145)', 'oklch(0.7 0.16 40)', 'oklch(0.62 0.19 300)',
            'oklch(0.68 0.15 190)', 'oklch(0.7 0.17 20)', 'oklch(0.64 0.14 120)',
            'oklch(0.66 0.18 270)',
        ];

        $guna = static::pluck('chart_color')->all();

        foreach ($roda as $warna) {
            if (! in_array($warna, $guna, true)) {
                return $warna;
            }
        }

        // Lebih daripada sepuluh servis — kitar semula, masih tersusun.
        return $roda[static::count() % count($roda)];
    }

    /**
     * Salin metrik Data Kritikal daripada servis lain.
     *
     * Servis tanpa metrik ialah halaman kosong: tiada corong, tiada
     * diagnosis, tiada baris dalam laporan. Ia kelihatan seperti sistem
     * rosak dan bukan servis yang baru dicipta, jadi metrik disalin pada
     * saat penciptaan dan bukan diserahkan sebagai langkah kedua yang
     * mungkin terlupa.
     *
     * Nilai mingguan TIDAK disalin — hanya struktur dan sasaran.
     */
    public function copyMetricsFrom(self $sumber): int
    {
        $disalin = 0;

        foreach ($sumber->criticalMetrics()->orderBy('sort_order')->get() as $metrik) {
            $baharu = $this->criticalMetrics()->firstOrCreate(
                ['metric_key' => $metrik->metric_key],
                [
                    'label_ms' => $metrik->label_ms,
                    'label_en' => $metrik->label_en,
                    'type' => $metrik->type,
                    'value_type' => $metrik->value_type,
                    'default_owner_id' => $metrik->default_owner_id,
                    'sort_order' => $metrik->sort_order,
                ]
            );

            if ($baharu->wasRecentlyCreated) {
                $disalin++;

                foreach ($metrik->targets as $sasaran) {
                    $baharu->targets()->create([
                        'year' => $sasaran->year,
                        'monthly_target' => $sasaran->monthly_target,
                        'target_text' => $sasaran->target_text,
                    ]);
                }
            }
        }

        return $disalin;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function criticalMetrics(): HasMany
    {
        return $this->hasMany(CriticalMetric::class)->orderBy('sort_order');
    }

    public function priorities(): HasMany
    {
        return $this->hasMany(Priority::class);
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(ServiceMonthlyTarget::class);
    }

    public function sheetIntegration(): HasOne
    {
        return $this->hasOne(SheetIntegration::class);
    }

    /** Nama mengikut locale semasa. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => $this->localized('name'));
    }

    /** Nama dalam bahasa lain — untuk paparan sekunder jika dikehendaki. */
    protected function alternateName(): Attribute
    {
        return Attribute::get(fn (): string => app()->getLocale() === 'en' ? $this->name_ms : $this->name_en);
    }

    /**
     * Sasaran bagi satu bulan tertentu.
     *
     * Menggunakan baris service_monthly_targets bila wujud; jika tidak,
     * berundur ke nilai asas `monthly_target`. Ini bermakna DBENA boleh
     * menetapkan hanya bulan yang berbeza dan biarkan yang lain.
     */
    public function targetForMonth(int $year, int $month): float
    {
        $override = $this->relationLoaded('monthlyTargets')
            ? $this->monthlyTargets->first(fn (ServiceMonthlyTarget $t) => $t->year === $year && $t->month === $month)
            : $this->monthlyTargets()->where('year', $year)->where('month', $month)->first();

        return (float) ($override?->target ?? $this->monthly_target);
    }

    /**
     * Sasaran setahun penuh — jumlah kesemua 12 bulan.
     *
     * BUKAN monthly_target × 12. Kalau setiap bulan mempunyai sasaran sendiri,
     * pendaraban ringkas akan memberi jawapan yang salah.
     */
    public function targetForYear(int $year): float
    {
        return collect(range(1, 12))->sum(fn (int $m) => $this->targetForMonth($year, $m));
    }

    /**
     * Sasaran terkumpul dari Januari sehingga bulan yang diberikan.
     *
     * Ini yang patut dibandingkan dengan jualan terkumpul — membandingkan
     * lapan bulan jualan dengan sasaran setahun penuh akan sentiasa kelihatan
     * seperti kegagalan.
     */
    public function cumulativeTargetTo(int $year, int $month): float
    {
        return collect(range(1, max(1, min(12, $month))))
            ->sum(fn (int $m) => $this->targetForMonth($year, $m));
    }

    public function metricByKey(string $key): ?CriticalMetric
    {
        return $this->criticalMetrics->firstWhere('metric_key', $key);
    }

    /**
     * Metrik "site visit" berbeza nama antara servis:
     *  - renovation / kabinet / mihrab : no_of_site_visit
     *  - bina-rumah                    : no_of_appointment
     *  - divider                       : TIADA langsung
     */
    public function siteVisitMetric(): ?CriticalMetric
    {
        return $this->criticalMetrics->first(
            fn (CriticalMetric $m) => in_array($m->metric_key, ['no_of_site_visit', 'no_of_appointment'], true)
        );
    }
}
