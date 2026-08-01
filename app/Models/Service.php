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
