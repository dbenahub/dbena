<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris dalam Master List of Project.
 *
 * Salinan tab Master Project dalam Google Sheet. Tiada apa dalam aplikasi
 * menulis ke model ini kecuali sync.
 */
class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'service_id', 'project_date', 'client_name', 'pic_sales',
        'phone', 'email', 'address', 'contract_amount', 'variation_order',
        'status', 'source_row', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'project_date' => 'date',
            'contract_amount' => 'decimal:2',
            'variation_order' => 'decimal:2',
            'status' => ProjectStatus::class,
            'synced_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Jumlah kontrak termasuk variation order.
     *
     * Nilai projek sebenar ialah kedua-duanya digabungkan. Melaporkan
     * jumlah kontrak sahaja memandang rendah buku pesanan setiap kali VO
     * diluluskan, yang berlaku pada kebanyakan projek renovation.
     */
    public function totalValue(): float
    {
        return (float) $this->contract_amount + (float) $this->variation_order;
    }

    // ── Skop ──────────────────────────────────────────────────────────

    public function scopeForService(Builder $query, ?int $serviceId): Builder
    {
        return $query->when($serviceId, fn (Builder $q) => $q->where('service_id', $serviceId));
    }

    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $q) => $q->where('status', $status));
    }

    /** Projek yang belum selesai — corong jualan semasa. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            ProjectStatus::Completed->value,
            ProjectStatus::Closed->value,
        ]);
    }

    /**
     * Carian merentas medan yang orang benar-benar taip.
     *
     * Kod, nama klien, PIC dan telefon. Bukan alamat — carian alamat
     * memadankan separuh senarai kerana setiap projek berada di Selangor,
     * dan hasil yang terlalu luas terasa rosak.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term): void {
            foreach (['code', 'client_name', 'pic_sales', 'phone', 'email'] as $field) {
                $q->orWhere($field, 'like', '%'.$term.'%');
            }
        });
    }
}
