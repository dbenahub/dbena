<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * Admin sahaja. Butang "Tambah PIC" dialih keluar daripada Dashboard
     * Pengguna, jadi polisi ini dikemas kini supaya sekatan itu benar-benar
     * dikuatkuasakan di pelayan — bukan sekadar butang yang disorok.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->is_active;
    }

    public function approve(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * PEMBETULAN isu #10 — guard sebenar, bukan sekadar sorok butang.
     * PIC teras / sistem / yang masih memegang data tidak boleh dibuang.
     */
    public function delete(User $user, Owner $owner): bool
    {
        return $user->isAdmin() && $owner->isRemovable();
    }
}
