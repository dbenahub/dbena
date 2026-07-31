<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CriticalMetric;
use App\Models\User;

class CriticalMetricPolicy
{
    /** Kedua-dua role boleh melihat data kritikal. */
    public function view(User $user, CriticalMetric $metric): bool
    {
        return $user->is_active;
    }

    /** Kedua-dua role boleh mengisi nilai mingguan. */
    public function updateWeeklyValue(User $user): bool
    {
        return $user->is_active;
    }

    /** Kedua-dua role boleh menetapkan PIC & pelan tindakan. */
    public function updateMeta(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * PEMBETULAN kritikal — hanya Admin boleh mengubah SASARAN.
     *
     * Dalam prototaip lajur ini hanya "read-only" secara visual; tiada
     * penguatkuasaan sebenar. Kaedah ini dipanggil oleh Livewire SEBELUM
     * sebarang penulisan, jadi memalsukan request tidak membantu.
     */
    public function updateTarget(User $user): bool
    {
        return $user->isAdmin();
    }
}
