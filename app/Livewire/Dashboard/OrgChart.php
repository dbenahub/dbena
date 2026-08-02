<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\OrgLink;
use App\Models\OrgNode;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Carta Organisasi — paparan sahaja.
 *
 * Komponen ini tiada satu pun kaedah penulis. Itu bukan kebetulan: suntingan
 * hidup dalam Panel Admin, dan cara paling pasti untuk memastikan skrin ini
 * kekal paparan sahaja ialah tidak memberinya cara untuk menulis.
 */
#[Layout('components.layouts.app')]
class OrgChart extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard.org-chart', [
            'nodes' => OrgNode::orderBy('sort_order')->get(),
            'links' => OrgLink::all(),
        ])->layoutData([
            'pageTitle' => __('org.title'),
            'pageSubtitle' => __('org.subtitle'),
        ]);
    }
}
