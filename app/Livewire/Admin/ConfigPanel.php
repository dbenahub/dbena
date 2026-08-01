<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OwnerStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CriticalWeeklyEntry;
use App\Models\IndexTier;
use App\Models\Owner;
use App\Models\Service;
use App\Models\ServiceMonthlyTarget;
use App\Models\SheetIntegration;
use App\Models\User;
use App\Models\YearGrowthFactor;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
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

    /** Sasaran bulanan per servis: [serviceId][month] => nilai */
    public array $monthlyTargets = [];

    /** Tahun yang sedang diedit untuk sasaran bulanan. */
    public int $targetYear;

    /** Servis yang barisan sasaran bulanannya sedang dibuka. */
    public ?int $expandedService = null;

    public string $sheetUrl = '';
    public string $newOwnerName = '';
    public string $newYear = '';

    // Modal cipta pengguna
    // Modal tambah servis
    public bool $showServiceModal = false;
    public string $newServiceNameMs = '';
    public string $newServiceNameEn = '';
    public string $newServiceTarget = '';
    public ?int $copyFromServiceId = null;

    public bool $showUserModal = false;
    public string $userName = '';
    public string $userUsername = '';
    public string $userEmail = '';
    public string $userRole = 'user';
    public ?string $generatedPassword = null;

    // Modal tukar kata laluan pengguna
    public bool $showPasswordModal = false;
    public ?int $passwordUserId = null;
    public ?string $passwordUserName = null;
    public string $newUserPassword = '';
    public string $newUserPasswordConfirmation = '';

    public function mount(): void
    {
        $this->authorize('access-admin-panel');
        $this->targetYear = (int) now()->year;
        $this->loadState();
    }

    public function updatedTargetYear(): void
    {
        $this->loadMonthlyTargets();
    }

    public function toggleService(int $serviceId): void
    {
        $this->expandedService = $this->expandedService === $serviceId ? null : $serviceId;
    }

    /**
     * Salin sasaran Januari ke sebelas bulan yang lain.
     *
     * Kebanyakan servis mempunyai sasaran yang sama sepanjang tahun; menaip
     * nilai yang sama dua belas kali adalah kerja yang tidak perlu.
     */
    public function fillFromJanuary(int $serviceId): void
    {
        $january = $this->monthlyTargets[$serviceId][1] ?? '';

        for ($m = 2; $m <= 12; $m++) {
            $this->monthlyTargets[$serviceId][$m] = $january;
        }
    }

    /**
     * Muat semula senarai servis sahaja.
     *
     * loadState() memuat SEMUA tetapan. Memanggilnya selepas menambah
     * servis akan membuang suntingan tier dan pertumbuhan yang belum
     * disimpan — kerja yang hilang tanpa amaran.
     */
    private function loadServices(): void
    {
        $this->services = [];

        foreach (Service::orderBy('sort_order')->get() as $service) {
            $this->services[$service->id] = [
                'name_ms' => $service->name_ms,
                'name_en' => $service->name_en,
                'monthly_target' => (string) (float) $service->monthly_target,
            ];
        }
    }

    private function loadMonthlyTargets(): void
    {
        $this->monthlyTargets = [];

        foreach (Service::with('monthlyTargets')->orderBy('sort_order')->get() as $service) {
            for ($m = 1; $m <= 12; $m++) {
                $this->monthlyTargets[$service->id][$m] =
                    (string) (float) $service->targetForMonth($this->targetYear, $m);
            }
        }
    }

    private function loadState(): void
    {
        $this->loadServices();

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

        $this->loadMonthlyTargets();
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

            foreach (Service::whereIn('id', array_keys($this->monthlyTargets))->get() as $service) {
                for ($month = 1; $month <= 12; $month++) {
                    $raw = $this->monthlyTargets[$service->id][$month] ?? null;

                    if ($raw === null || trim((string) $raw) === '') {
                        continue;
                    }

                    $value = (float) preg_replace('/[^0-9.]/', '', (string) $raw);

                    $record = ServiceMonthlyTarget::firstOrNew([
                        'service_id' => $service->id,
                        'year' => $this->targetYear,
                        'month' => $month,
                    ]);

                    $old = ['target' => $record->target];

                    $record->fill(['target' => $value, 'updated_by' => auth()->id()])->save();

                    if ($audit->record(
                        'service.monthly_target_updated',
                        $record,
                        $old,
                        ['target' => $value],
                        $service->name_ms.' · '.__('calendar.months_short')[$month - 1].' '.$this->targetYear
                    )) {
                        $changes++;
                    }
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

    // ── Tambah / buang servis ─────────────────────────────────────────

    public function openServiceModal(): void
    {
        $this->authorize('access-admin-panel');

        $this->newServiceNameMs = '';
        $this->newServiceNameEn = '';
        $this->newServiceTarget = '';
        $this->copyFromServiceId = Service::orderBy('sort_order')->value('id');
        $this->showServiceModal = true;
    }

    /**
     * Cipta servis baharu, lengkap dengan metrik Data Kritikalnya.
     *
     * Servis tanpa metrik ialah halaman kosong — tiada corong, tiada
     * diagnosis, tiada baris dalam laporan. Ia kelihatan seperti sistem
     * rosak dan bukan servis yang baru dicipta, jadi penciptaan dan
     * penyalinan metrik berlaku dalam SATU transaksi. Separuh siap lebih
     * mengelirukan daripada gagal terus.
     */
    public function createService(AuditLogger $audit): void
    {
        $this->authorize('access-admin-panel');

        $namaMs = trim($this->newServiceNameMs);
        $namaEn = trim($this->newServiceNameEn) ?: $namaMs;

        if ($namaMs === '') {
            $this->dispatch('dbena-toast', message: __('admin.service_name_required'), variant: 'error');

            return;
        }

        $kunci = Str::slug($namaMs);

        if ($kunci === '' || Service::where('key', $kunci)->exists()) {
            $this->dispatch('dbena-toast', message: __('admin.service_exists'), variant: 'error');

            return;
        }

        $sumber = $this->copyFromServiceId
            ? Service::with('criticalMetrics.targets')->find($this->copyFromServiceId)
            : null;

        if (! $sumber) {
            $this->dispatch('dbena-toast', message: __('admin.service_template_required'), variant: 'error');

            return;
        }

        $sasaran = (float) preg_replace('/[^0-9.]/', '', $this->newServiceTarget);

        $service = DB::transaction(function () use ($namaMs, $namaEn, $kunci, $sasaran, $sumber) {
            $service = Service::create([
                'key' => $kunci,
                'name_ms' => $namaMs,
                'name_en' => $namaEn,
                'icon_class' => 'ph-squares-four',
                'monthly_target' => $sasaran,
                'chart_color' => Service::nextChartColor(),
                'sort_order' => (int) Service::max('sort_order') + 1,
            ]);

            $service->copyMetricsFrom($sumber);

            return $service;
        });

        $audit->log('service.created', $service, $namaMs, [
            'copied_from' => $sumber->key,
            'metrics' => $service->criticalMetrics()->count(),
        ]);

        $this->showServiceModal = false;
        $this->loadMonthlyTargets();
        $this->loadServices();

        $this->dispatch('dbena-toast', message: __('admin.service_added', [
            'name' => $namaMs,
            'count' => $service->criticalMetrics()->count(),
        ]));
    }

    /**
     * Buang servis dan segala yang bergantung padanya.
     *
     * Servis yang membawa data mingguan TIDAK boleh dibuang. Memadamnya
     * memusnahkan sejarah yang laporan bulan lalu bergantung padanya, dan
     * tiada cara untuk memulihkannya dari dalam sistem.
     */
    public function removeService(int $serviceId, AuditLogger $audit): void
    {
        $this->authorize('access-admin-panel');

        $service = Service::withCount('criticalMetrics')->findOrFail($serviceId);

        $adaData = CriticalWeeklyEntry::whereIn(
            'critical_metric_id',
            $service->criticalMetrics()->select('id')
        )->exists();

        if ($adaData) {
            $this->dispatch('dbena-toast', message: __('admin.service_has_data'), variant: 'error');

            return;
        }

        if (Service::count() <= 1) {
            $this->dispatch('dbena-toast', message: __('admin.service_last_one'), variant: 'error');

            return;
        }

        $nama = $service->name;
        $audit->log('service.removed', null, $nama, ['key' => $service->key]);

        $service->delete();

        $this->loadMonthlyTargets();
        $this->loadServices();

        $this->dispatch('dbena-toast', message: __('admin.service_removed', ['name' => $nama]));
    }

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

    /**
     * Buka modal tukar kata laluan untuk seorang pengguna.
     *
     * Kata laluan diurus secara berpusat — pengguna tidak boleh menukar sendiri
     * melalui Tetapan. Admin boleh menetapkan kata laluan tertentu, atau menjana
     * yang rawak.
     */
    public function openPasswordModal(int $userId): void
    {
        $this->authorize('manage-users');

        $user = User::findOrFail($userId);

        $this->passwordUserId = $user->id;
        $this->passwordUserName = $user->name;
        $this->newUserPassword = '';
        $this->newUserPasswordConfirmation = '';
        $this->showPasswordModal = true;

        $this->resetErrorBag();
    }

    /** Isi medan dengan kata laluan rawak yang kuat. */
    public function generatePassword(): void
    {
        $this->authorize('manage-users');

        $generated = Str::password(14, symbols: false);

        $this->newUserPassword = $generated;
        $this->newUserPasswordConfirmation = $generated;
    }

    public function savePassword(AuditLogger $audit): void
    {
        $this->authorize('manage-users');

        $this->validate([
            'newUserPassword' => ['required', 'confirmed:newUserPasswordConfirmation', Password::min(8)->letters()->numbers()],
        ], attributes: [
            'newUserPassword' => __('admin.new_password'),
        ]);

        $user = User::findOrFail($this->passwordUserId);

        $user->update(['password' => $this->newUserPassword]);

        // Batalkan OTP belum guna — percubaan log masuk separa dengan kata
        // laluan lama tidak boleh diteruskan.
        $user->otps()->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $audit->log('user.password_reset', $user, $user->name);

        $this->showPasswordModal = false;
        $this->newUserPassword = '';
        $this->newUserPasswordConfirmation = '';

        $this->dispatch('dbena-toast', message: __('admin.password_changed', ['name' => $user->name]));
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
            'serviceModels' => Service::with(['monthlyTargets', 'criticalMetrics'])->orderBy('sort_order')->get(),
            'monthLabels' => __('calendar.months_short'),
            'targetYears' => range(2023, 2032),
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
