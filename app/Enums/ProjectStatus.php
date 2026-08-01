<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status projek dalam Master List of Project.
 *
 * Nilai datang daripada lajur Status dalam Google Sheet. Sheet ialah
 * satu-satunya sumber kebenaran, jadi enum ini mesti menerima apa sahaja
 * yang ditaip di sana tanpa menghempaskan sync — lihat fromSheet().
 */
enum ProjectStatus: string
{
    case Quotation = 'quotation';
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Closed = 'closed';

    /**
     * Padankan teks bebas daripada sheet kepada satu kes.
     *
     * Orang menaip "In Progress", "in-progress", "IN PROGRESS" dan
     * "Ongoing" untuk perkara yang sama. Menolak baris kerana huruf besar
     * bermakna projek hilang daripada dashboard tanpa sesiapa tahu
     * sebabnya, jadi padanan dibuat secara longgar dan baris yang tidak
     * dikenali jatuh ke Pending — kelihatan dalam senarai, menunggu
     * pembetulan.
     */
    public static function fromSheet(?string $raw): self
    {
        $key = preg_replace('/[^a-z]/', '', mb_strtolower(trim((string) $raw)));

        return match (true) {
            str_contains($key, 'quot') => self::Quotation,
            str_contains($key, 'progress'), str_contains($key, 'ongoing') => self::InProgress,
            str_contains($key, 'complete'), str_contains($key, 'siap') => self::Completed,
            str_contains($key, 'close'), str_contains($key, 'tutup') => self::Closed,
            default => self::Pending,
        };
    }

    public function label(): string
    {
        return __('project.status.'.$this->value);
    }

    /** Warna teks. */
    public function color(): string
    {
        return match ($this) {
            self::Quotation => 'oklch(0.78 0.15 85)',
            self::Pending => 'oklch(0.62 0.02 260)',
            self::InProgress => 'oklch(0.62 0.19 255)',
            self::Completed => 'oklch(0.62 0.16 150)',
            self::Closed => 'oklch(0.5 0.02 260)',
        };
    }

    /** Projek yang sudah tidak aktif — tidak dikira dalam corong semasa. */
    public function isFinished(): bool
    {
        return $this === self::Completed || $this === self::Closed;
    }

    /** @return array<int, self> */
    public static function active(): array
    {
        return array_values(array_filter(self::cases(), fn (self $s) => ! $s->isFinished()));
    }
}
