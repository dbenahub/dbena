<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Service;
use App\Models\SheetIntegration;
use App\Services\CriticalDataService;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Notifikasi DINAMIK, dijana daripada keadaan data sebenar.
 *
 * Prototaip mengisytiharkan array `this.notifications` dengan 4 item statik
 * yang TIDAK PERNAH digunakan, lalu membina `liveNotifications` secara
 * berasingan. Di sini hanya versi dinamik yang wujud.
 */
class NotificationBell extends Component
{
    public bool $read = false;

    public function markRead(): void
    {
        $this->read = true;
    }

    /** @return array<int, array<string, mixed>> */
    public function getItemsProperty(CriticalDataService $critical): array
    {
        $year = (int) now()->year;
        $month = (int) now()->month;
        $items = [];

        foreach (Service::orderBy('sort_order')->get() as $service) {
            $redCount = $critical->redCountFor($service, $year, $month);

            if ($redCount > 0) {
                $items[] = [
                    'icon' => 'ph-warning-circle',
                    'color' => 'oklch(0.6 0.2 25)',
                    'text' => __('dashboard.notif_red_metrics', [
                        'service' => $service->name,
                        'count' => $redCount,
                    ]),
                    'time' => __('dashboard.notif_source_critical'),
                    'url' => route('service.detail', $service->key),
                ];
            }
        }

        $sheet = SheetIntegration::global();

        if ($sheet->connected && $sheet->last_synced_at) {
            $items[] = [
                'icon' => 'ph-check-circle',
                'color' => 'oklch(0.55 0.15 145)',
                'text' => __('dashboard.notif_sheet_connected', [
                    'time' => $sheet->last_synced_at->translatedFormat('d M, H:i'),
                ]),
                'time' => __('dashboard.notif_source_integration'),
                'url' => null,
            ];
        }

        return $items;
    }

    public function render(): View
    {
        return view('livewire.notification-bell', ['items' => $this->items]);
    }
}
