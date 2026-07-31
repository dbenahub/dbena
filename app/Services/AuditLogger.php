<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * PEMBETULAN isu #25 — prototaip tiada jejak audit langsung.
 *
 * Hanya perubahan SEBENAR direkodkan; jika nilai lama = nilai baharu,
 * tiada baris ditulis (mengelak bunyi bising dalam log).
 */
class AuditLogger
{
    public function record(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $subjectLabel = null,
    ): ?AuditLog {
        $changed = $this->diff($oldValues, $newValues);

        if ($changed === []) {
            return null;
        }

        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subjectLabel,
            'old_values' => array_intersect_key($oldValues, $changed),
            'new_values' => $changed,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** Rekod tindakan tanpa perbandingan nilai (cth. lulus/tolak PIC). */
    public function log(string $action, ?Model $subject = null, ?string $subjectLabel = null, array $context = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subjectLabel,
            'old_values' => null,
            'new_values' => $context ?: null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> hanya kunci yang benar-benar berubah */
    private function diff(array $old, array $new): array
    {
        $changed = [];

        foreach ($new as $key => $value) {
            $previous = $old[$key] ?? null;

            if (is_numeric($value) && is_numeric($previous)) {
                if (abs((float) $value - (float) $previous) > 0.001) {
                    $changed[$key] = $value;
                }

                continue;
            }

            if ((string) $value !== (string) $previous) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }
}
