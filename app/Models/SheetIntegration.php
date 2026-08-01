<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Konfigurasi sambungan Google Sheet bagi satu servis
 * (atau tetapan lalai global apabila service_id NULL).
 */
class SheetIntegration extends Model
{
    /** Medan yang boleh dipetakan ke lajur sheet. */
    public const MAPPABLE_FIELDS = ['metric', 'week1', 'week2', 'week3', 'week4', 'data_type', 'target', 'owner', 'action_plan'];

    /** Medan yang WAJIB dipetakan sebelum sync boleh berjalan. */
    public const REQUIRED_FIELDS = ['metric', 'week1', 'week2', 'week3', 'week4'];

    protected $fillable = [
        'service_id', 'url', 'spreadsheet_id', 'tab_name', 'gid', 'column_map',
        'header_row', 'match_mode', 'layout_mode', 'import_targets', 'detected_services',
        'connected', 'sync_enabled', 'last_synced_at',
        'last_sync_status', 'last_sync_message', 'last_sync_rows', 'webhook_secret',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'connected' => 'boolean',
            'sync_enabled' => 'boolean',
            'column_map' => 'array',
            'detected_services' => 'array',
            'import_targets' => 'boolean',
            'header_row' => 'integer',
            'last_sync_rows' => 'integer',
            'last_synced_at' => 'datetime',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SheetSyncLog::class)->latest('created_at');
    }

    public static function global(): self
    {
        return static::firstOrCreate(['service_id' => null]);
    }

    public function effectiveUrl(): ?string
    {
        return $this->url ?: static::global()->url;
    }

    /**
     * Ekstrak ID spreadsheet daripada mana-mana bentuk URL Google Sheets.
     * Menerima URL edit, URL kongsi, URL terbitan, atau ID mentah.
     */
    public static function extractSpreadsheetId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]{20,})#', $url, $m)) {
            return $m[1];
        }

        // Format terbitan: /spreadsheets/d/e/2PACX-.../pubhtml
        if (preg_match('#/spreadsheets/d/e/([a-zA-Z0-9_-]{20,})#', $url, $m)) {
            return 'e/'.$m[1];
        }

        // ID mentah ditampal terus
        if (preg_match('#^[a-zA-Z0-9_-]{20,}$#', trim($url))) {
            return trim($url);
        }

        return null;
    }

    /** Ekstrak gid (ID tab) daripada fragmen URL. */
    public static function extractGid(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return preg_match('/[#&?]gid=(\d+)/', $url, $m) ? $m[1] : null;
    }

    /** Sync hanya boleh berjalan apabila pemetaan lengkap. */
    public function isMultiService(): bool
    {
        return ($this->layout_mode ?? 'multi') === 'multi';
    }

    /** Sheet global (service_id NULL) yang memegang semua servis. */
    public function isGlobalSheet(): bool
    {
        return $this->service_id === null && $this->isMultiService();
    }

    public function isReadyToSync(): bool
    {
        if (blank($this->spreadsheet_id) || ! $this->sync_enabled) {
            return false;
        }

        // Susun atur satu-servis memerlukan servis; susun atur berbilang tidak.
        if (! $this->isMultiService() && $this->service_id === null) {
            return false;
        }

        $map = $this->column_map ?? [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (blank($map[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Kenal pasti SEBAB TEPAT sync tidak boleh berjalan.
     *
     * isReadyToSync() memulangkan bool sahaja, yang menghasilkan mesej ralat
     * yang tidak berguna ("medan tiada:" dengan senarai kosong). Kaedah ini
     * memulangkan kunci masalah supaya pengguna tahu apa yang perlu dibetulkan.
     *
     * @return string|null null bermakna sedia
     */
    public function readinessProblem(): ?string
    {
        if (blank($this->spreadsheet_id)) {
            return 'no_link';
        }

        if (! $this->isMultiService() && $this->service_id === null) {
            return 'no_service';
        }

        if ($this->missingMappings() !== []) {
            return 'mapping';
        }

        if (! $this->sync_enabled) {
            return 'disabled';
        }

        return null;
    }

    /** @return array<int, string> medan wajib yang belum dipetakan */
    public function missingMappings(): array
    {
        $map = $this->column_map ?? [];

        return array_values(array_filter(
            self::REQUIRED_FIELDS,
            fn (string $field) => blank($map[$field] ?? null)
        ));
    }

    public function ensureWebhookSecret(): string
    {
        if (blank($this->webhook_secret)) {
            $this->forceFill(['webhook_secret' => Str::random(48)])->save();
        }

        return $this->webhook_secret;
    }

    public function webhookUrl(): string
    {
        return route('sheets.webhook', [
            'integration' => $this->id,
            'token' => $this->ensureWebhookSecret(),
        ]);
    }

    public function statusColor(): string
    {
        return match ($this->last_sync_status) {
            'success' => 'oklch(0.55 0.15 145)',
            'partial' => 'oklch(0.78 0.15 85)',
            'failed' => 'oklch(0.55 0.2 25)',
            default => 'var(--t50)',
        };
    }
}
