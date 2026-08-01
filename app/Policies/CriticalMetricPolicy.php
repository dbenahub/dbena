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

    /** Kedua-dua role boleh menulis pelan tindakan. */
    public function updateMeta(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Hanya Admin boleh menukar PIC sesuatu baris.
     *
     * Di Dashboard Pengguna lajur PEMILIK adalah paparan sahaja. Nama itu
     * datang daripada lajur DATA OWNER dalam Google Sheet, jadi suntingan
     * oleh pengguna akan ditulis ganti pada sync berikutnya — dua sumber
     * kebenaran yang bertelagah. Admin masih boleh menukarnya sebagai
     * penindihan sementara apabila nama dalam sheet tidak sepadan.
     */
    public function assignOwner(User $user): bool
    {
        return $user->isAdmin();
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
