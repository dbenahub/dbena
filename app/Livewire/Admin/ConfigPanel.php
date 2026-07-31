<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OwnerStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\IndexTier;
use App\Models\Owner;
use App\Models\Service;
use App\Models\SheetIntegration;
use App\Models\User;
use App\Models\YearGrowthFactor;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin Panel — port Admin Panel.dc.html dengan tambahan:
 *   • Kelulusan PIC (keputusan D2, betulkan isu #11)
 *   • Urus pengguna
 *   • Log audit (betulkan isu #25)
 *   • Guard buang PIC sebenar (betulkan isu #10)
 *
 * "Simpan Semua" membungkus SEMUA kemas kini dalam satu DB::transaction()
 * dan merekod hanya nilai yang benar-benar berubah.
 */
#[Layout('components.layouts.app')]
class ConfigPanel extends Component
{
    /** [serviceId => ['name_ms','name_en','monthly_target']] */
    public array $services = [];

    /** [tierId => ['monthly_revenue_threshold','monthly_profit_threshold']] */
    public array $tiers = [];

    /** [year => factor] */
    public array $growth = [];

    public string $sheetUrl = '';
    public string $newOwnerName = '';
    public string $newYear = '';

    // Modal cipta pengguna
    public bool $showUserModal = false;
    public string $userName = '';
    public string $userUsername = '';
    public string $userEmail = '';
    public string $userRole = 'user';
    public ?string $generatedPassword = null;

    public function mount(): void
    {
        $this->authorize('access-admin-panel');
        $this->loadState();
    }

    private function loadState(): void
    {
        foreach (Service::orderBy('sort_order')->get() as $service) {
            $this->services[$service->id] = [
                'name_ms' => $service->name_ms,
                'name_en' => $service->name_en,
                'monthly_target' => (string) (float) $service->monthly_target,
            ];
        }

        foreach (IndexTier::orderBy('sort_order')->get() as $tier) {
            $this->tiers[$tier->id] = [
                'monthly_revenue_threshold' => (string) (float) $tier->monthly_revenue_threshold,
                'monthly_profit_threshold' => (string) (float) $tier->monthly_profit_threshold,
            ];
        }

        foreach (YearGrowthFactor::orderBy('year')->get() as $factor) {
            $this->growth[$factor->year] = (string) $factor->factor;
        }

        $this->sheetUrl = (string) (SheetIntegration::global()->url ?? '');
    }

    // ── Simpan Semua ──────────────────────────────────────────────────────

    public function saveAll(AuditLogger $audit): void
    {
        $this->authorize('access-admin-panel');

        $changes = 0;

        DB::transaction(function () use ($audit, &$changes): void {
            foreach (Service::whereIn('id', array_keys($this->services))->get() as $service) {
                $input = $this->services[$service->id];
                $new = [
                    'name_ms' => trim($input['name_ms']),
                    'name_en' => trim($input['name_en']),
                    'monthly_target' => (float) preg_replace('/[^0-9.]/', '', $input['monthly_target']),
                ];
                $old = $service->only(array_keys($new));

                $service->update($new);

                if ($audit->record('service.updated', $service, $old, $new, $service->name_ms)) {
                    $changes++;
                }
            }

            foreach (IndexTier::whereIn('id', array_keys($this->tiers))->get() as $tier) {
                $input = $this->tiers[$tier->id];
                $new = [
                    'monthly_revenue_threshold' => (float) preg_replace('/[^0-9.]/', '', $input['monthly_revenue_threshold']),
                    'monthly_profit_threshold' => (float) preg_replace('/[^0-9.]/', '', $input['monthly_profit_threshold']),
                ];
                $old = $tier->only(array_keys($new));

                $tier->update($new);

                if ($audit->record('tier.updated', $tier, $old, $new, $tier->name_ms)) {
                    $changes++;
                }
            }

            foreach ($this->growth as $year => $value) {
                $factor = YearGrowthFactor::firstOrNew(['year' => (int) $year]);
                $old = ['factor' => $factor->factor];
                $new = ['factor' => (float) preg_replace('/[^0-9.]/', '', (string) $value)];

                $factor->fill($new)->save();

                if ($audit->record('growth.updated', $factor, $old, $new, (string) $year)) {
                    $changes++;
                }
            }

            $sheet = SheetIntegration::global();
            $old = ['url' => $sheet->url];
            $new = ['url' => trim($this->sheetUrl) ?: null];

            $sheet->update($new + ['updated_by' => auth()->id()]);

            if ($audit->record('sheet.updated', $sheet, $old, $new, 'default')) {
                $changes++;
            }
        });

        $this->dispatch('dbena-toast', message: $changes > 0
            ? __('admin.saved_all')
            : __('admin.no_changes'));
    }

    // ── PIC ───────────────────────────────────────────────────────────────

    public function addOwner(AuditLogger $audit): void
    {
        $this->authorize('create', Owner::class);

        $name = Str::upper(trim($this->newOwnerName));

        if ($name === '') {
            $this->dispatch('dbena-toast', message: __('admin.owner_name_required'), variant: 'error');

            return;
        }

        if (Owner::where('name', $name)->exists()) {
            $this->dispatch('dbena-toast', message: __('admin.owner_exists'), variant: 'error');

            return;
        }

        $owner = Owner::create([
            'name' => $name,
            'color_token' => Owner::nextColor(),
            'is_core' => false,
            'is_system' => false,
            'status' => OwnerStatus::Active,
            'created_by' => auth()->id(),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $audit->log('owner.created', $owner, $name);

        $this->newOwnerName = '';
        $this->dispatch('dbena-toast', message: __('admin.owner_added', ['name' => $name]));
    }

    /**
     * GUARD SEBENAR (betulkan isu #10).
     *
     * Prototaip menyorok butang buang untuk 4 PIC teras di UI, tetapi
     * fungsi removeOwner() tidak mempunyai sekatan langsung.
     */
    public function removeOwner(int $ownerId, AuditLogger $audit): void
    {
        $owner = Owner::findOrFail($ownerId);

        if ($owner->is_core) {
            $this->dispatch('dbena-toast', message: __('admin.owner_core_locked'), variant: 'error');

            return;
        }

        if ($owner->is_system) {
            $this->dispatch('dbena-toast', message: __('admin.owner_system_locked'), variant: 'error');

            return;
        }

        if ($owner->hasActiveData()) {
            $this->dispatch('dbena-toast',
                message: __('admin.owner_has_data', ['name' => $owner->name]),
                variant: 'error');

            return;
        }

        $this->authorize('delete', $owner);

        $name = $owner->name;
        $audit->log('owner.deleted', $owner, $name);
        $owner->delete();

        $this->dispatch('dbena-toast', message: __('admin.owner_removed', ['name' => $name]));
    }

    public function approveOwner(int $ownerId, AuditLogger $audit): void
    {
        $this->authorize('approve', Owner::class);

        $owner = Owner::findOrFail($ownerId);
        $owner->update([
            'status' => OwnerStatus::Active,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $audit->log('owner.approved', $owner, $owner->name);

        $this->dispatch('dbena-toast', message: __('admin.owner_approved', ['name' => $owner->name]));
    }

    public function rejectOwner(int $ownerId, AuditLogger $audit): void
    {
        $this->authorize('approve', Owner::class);

        $owner = Owner::findOrFail($ownerId);
        $owner->update(['status' => OwnerStatus::Rejected, 'approved_by' => auth()->id(), 'approved_at' => now()]);

        $audit->log('owner.rejected', $owner, $owner->name);

        $this->dispatch('dbena-toast', message: __('admin.owner_rejected', ['name' => $owner->name]));
    }

    // ── Faktor pertumbuhan ────────────────────────────────────────────────

    public function addYear(): void
    {
        $year = (int) $this->newYear;

        if ($year < 2000 || $year > 2100 || isset($this->growth[$year])) {
            return;
        }

        $this->growth[$year] = '1.0';
        ksort($this->growth);
        $this->newYear = '';
    }

    // ── Urus pengguna ─────────────────────────────────────────────────────

    public function createUser(AuditLogger $audit): void
    {
        $this->authorize('manage-users');

        $this->validate([
            'userName' => 'required|string|max:120',
            'userUsername' => 'required|string|max:60|unique:users,username',
            'userEmail' => 'required|email|max:190|unique:users,email',
            'userRole' => 'required|in:admin,user',
        ]);

        $password = Str::password(16, symbols: false);

        $user = User::create([
            'name' => $this->userName,
            'username' => $this->userUsername,
            'email' => $this->userEmail,
            'password' => $password,
            'role' => UserRole::from($this->userRole),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $audit->log('user.created', $user, $user->name, ['role' => $user->role->value]);

        // Dipapar SEKALI sahaja dalam UI; tidak pernah disimpan sebagai teks biasa.
        $this->generatedPassword = $password;
        $this->reset(['userName', 'userUsername', 'userEmail', 'userRole']);
        $this->userRole = 'user';

        $this->dispatch('dbena-toast', message: __('admin.user_created', ['name' => $user->name]));
    }

    public function toggleUserActive(int $userId, AuditLogger $audit): void
    {
        $this->authorize('manage-users');

        if ($userId === auth()->id()) {
            $this->dispatch('dbena-toast', message: __('admin.cannot_deactivate_self'), variant: 'error');

            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);

        $audit->log('user.updated', $user, $user->name, ['is_active' => $user->is_active]);

        $this->dispatch('dbena-toast', message: __('admin.user_updated', ['name' => $user->name]));
    }

    public function render(): View
    {
        return view('livewire.admin.config-panel', [
            'serviceModels' => Service::orderBy('sort_order')->get(),
            'tierModels' => IndexTier::orderBy('sort_order')->get(),
            'activeOwners' => Owner::active()->orderByDesc('is_core')->orderBy('name')->get(),
            'pendingOwners' => Owner::pending()->with('creator')->orderBy('created_at')->get(),
            'users' => User::orderByDesc('role')->orderBy('name')->get(),
            'auditLogs' => AuditLog::with('user')->latest('created_at')->limit(50)->get(),
        ])->layoutData([
            'pageTitle' => __('admin.panel_title'),
            'pageSubtitle' => __('admin.panel_subtitle'),
        ]);
    }
}
