<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Models\OrgLink;
use App\Models\OrgNode;
use App\Services\AuditLogger;
use App\Support\OrgPalette;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Editor Carta Organisasi — Admin sahaja.
 *
 * Aplikasi ialah penulis di sini, jadi SETIAP kaedah yang menulis menyemak
 * gate sendiri. Menyorok butang tidak menghalang sesiapa daripada memanggil
 * kaedah Livewire secara terus.
 */
#[Layout('components.layouts.app')]
class OrgChartEditor extends Component
{
    /** Grid untuk melekatkan kedudukan — 10px cukup halus untuk kelihatan bebas. */
    private const GRID = 10;

    public ?int $selectedId = null;

    /**
     * Semua kotak yang dipilih.
     *
     * $selectedId kekal sebagai pilihan UTAMA — kotak yang panel butiran
     * sedang menyunting. Dua konsep, dua medan: menggabungkannya bermakna
     * panel perlu meneka kotak mana yang dimaksudkan apabila lima dipilih.
     *
     * @var array<int, int>
     */
    public array $selectedIds = [];

    public ?int $connectFrom = null;

    public string $linkStyle = 'solid';

    // Medan borang bagi kotak yang dipilih.
    public string $title = '';

    public string $subtitle = '';

    public string $name = '';

    public string $icon = '';

    public string $style = 'department';

    public int $width = 200;

    public int $height = 66;

    /** Kosong bermakna "ikut gaya". */
    public string $color = '';

    public function mount(): void
    {
        $this->authorize('manage-org-chart');
    }

    private function node(): ?OrgNode
    {
        return $this->selectedId ? OrgNode::find($this->selectedId) : null;
    }

    #[On('org-node-clicked')]
    public function selectNode(int $id, bool $additive = false): void
    {
        $this->authorize('manage-org-chart');

        // Dalam mod sambung, klik bermaksud "sambung", bukan "pilih". Dua
        // makna untuk satu isyarat memerlukan mod, dan mod itu mesti
        // menang selagi ia hidup.
        if ($this->connectFrom !== null) {
            $this->finishLink($id);

            return;
        }

        $node = OrgNode::find($id);

        if ($node === null) {
            return;
        }

        if ($additive) {
            // Ctrl-klik pada kotak yang sudah dipilih MEMBUANGNYA. Tanpa
            // itu, tersalah tambah satu kotak bermakna mula semula dari
            // kosong.
            $this->selectedIds = in_array($node->id, $this->selectedIds, true)
                ? array_values(array_diff($this->selectedIds, [$node->id]))
                : [...$this->selectedIds, $node->id];
        } else {
            $this->selectedIds = [$node->id];
        }

        $this->loadForm($node);
    }

    /** Isi medan borang daripada satu kotak. */
    private function loadForm(OrgNode $node): void
    {
        $this->selectedId = $node->id;
        $this->title = (string) $node->title;
        $this->subtitle = (string) $node->subtitle;
        $this->name = (string) $node->name;
        $this->icon = (string) $node->icon;
        $this->style = $node->style->value;
        $this->width = $node->width;
        $this->height = $node->boxHeight();
        $this->color = (string) $node->color;
    }

    /**
     * Simpan kedudukan selepas seretan.
     *
     * Alpine memegang kedudukan semasa seretan dan hanya memanggil di sini
     * apabila jari dilepaskan. Menghantar setiap piksel akan membanjiri
     * pelayan dengan ratusan penulisan untuk satu pergerakan.
     */
    #[On('org-node-moved')]
    public function moveNode(int $id, int $x, int $y): void
    {
        $this->authorize('manage-org-chart');

        OrgNode::where('id', $id)->update([
            'x' => max(0, (int) round($x / self::GRID) * self::GRID),
            'y' => max(0, (int) round($y / self::GRID) * self::GRID),
        ]);
    }

    /**
     * Simpan kedudukan selepas seretan berkumpulan.
     *
     * Satu transaksi, bukan satu penulisan setiap kotak. Menyeret tujuh
     * belas kotak menghasilkan tujuh belas UPDATE yang boleh gagal separuh
     * jalan — dan carta yang separuh beralih lebih teruk daripada carta
     * yang tidak beralih langsung, kerana tiada siapa tahu susunan asalnya.
     *
     * @param  array<int, array{id: int|string, x: int|string, y: int|string}>  $moves
     */
    #[On('org-nodes-moved')]
    public function moveNodes(array $moves): void
    {
        $this->authorize('manage-org-chart');

        DB::transaction(function () use ($moves): void {
            foreach ($moves as $move) {
                OrgNode::where('id', (int) ($move['id'] ?? 0))->update([
                    'x' => max(0, (int) round(((int) ($move['x'] ?? 0)) / self::GRID) * self::GRID),
                    'y' => max(0, (int) round(((int) ($move['y'] ?? 0)) / self::GRID) * self::GRID),
                ]);
            }
        });
    }

    /** Pilih setiap kotak — untuk mengalihkan seluruh carta sekali gus. */
    public function selectAll(): void
    {
        $this->authorize('manage-org-chart');

        $semua = OrgNode::orderBy('sort_order')->get();

        $this->selectedIds = $semua->pluck('id')->all();
        $this->connectFrom = null;

        // Kotak pertama menjadi pilihan UTAMA supaya panel butiran
        // mempunyai sesuatu untuk ditunjukkan dan bukan menjadi kosong
        // sebaik sahaja semuanya dipilih.
        $utama = $semua->first();

        if ($utama !== null) {
            $this->loadForm($utama);
        }
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectedId = null;
        $this->connectFrom = null;
    }

    /**
     * Warnakan setiap kotak yang dipilih.
     *
     * Menukar warna satu demi satu untuk tujuh belas kotak ialah kerja
     * yang tiada siapa akan siapkan, jadi carta kekal separuh berwarna.
     */
    public function setColorForSelection(?string $hex): void
    {
        $this->authorize('manage-org-chart');

        $warna = OrgPalette::clean($hex);
        $this->color = (string) $warna;

        OrgNode::whereIn('id', $this->selectedIds)->update(['color' => $warna]);
    }

    public function addNode(AuditLogger $audit): void
    {
        $this->authorize('manage-org-chart');

        /*
         * Kotak baharu diletak di bawah yang terendah, bukan pada (0,0).
         *
         * Pada (0,0) ia mendarat di belakang kotak sedia ada dan kelihatan
         * seolah-olah butang itu tidak berfungsi — jadi admin menekannya
         * berulang kali dan mencipta lima kotak bertindih.
         */
        $bawah = (int) (OrgNode::max('y') ?? 0);

        $node = OrgNode::create([
            'title' => __('org.editor.add'),
            'name' => null,
            'icon' => 'ph-user',
            'style' => OrgNodeStyle::Department->value,
            'x' => 40,
            'y' => $bawah + 140,
            'width' => 200,
            'height' => 66,
            'sort_order' => (int) (OrgNode::max('sort_order') ?? 0) + 1,
        ]);

        $audit->log('org_chart.node_added', $node, (string) $node->title);

        $this->selectNode($node->id);
        $this->dispatch('dbena-toast', message: __('org.editor.added'));
    }

    public function saveNode(AuditLogger $audit): void
    {
        $this->authorize('manage-org-chart');

        $node = $this->node();

        if ($node === null) {
            return;
        }

        $node->fill([
            'title' => trim($this->title) ?: null,
            'subtitle' => trim($this->subtitle) ?: null,
            'name' => trim($this->name) ?: null,
            'icon' => trim($this->icon) ?: 'ph-user',
            'style' => OrgNodeStyle::tryFrom($this->style)?->value ?? OrgNodeStyle::Department->value,
            // Had lebar menghalang kotak selebar 9000px yang menolak setiap
            // kotak lain keluar dari skrin.
            'width' => max(120, min(420, $this->width)),
            // Had tinggi atas sebab yang sama seperti lebar: satu kotak
            // setinggi halaman menolak segala-galanya keluar dari skrin.
            'height' => max(48, min(180, $this->height)),
            // Hex yang tidak sah menjadi NULL, bukan disimpan mentah. Nilai
            // yang rosak dalam lajur ini menghasilkan kotak lutsinar dalam
            // penyemak imbas tanpa sebarang ralat untuk dikesan.
            'color' => OrgPalette::clean($this->color),
        ])->save();

        $audit->log('org_chart.node_saved', $node, (string) $node->title);

        $this->dispatch('dbena-toast', message: __('org.editor.saved'));
    }

    /**
     * Tetapkan warna dan simpan serta-merta.
     *
     * Klik pada contoh warna sepatutnya menunjukkan hasilnya. Meminta klik
     * Simpan selepasnya bermakna admin memilih tiga warna, tidak nampak
     * satu pun berubah, dan menyangka pemilih itu rosak.
     */
    public function setColor(?string $hex): void
    {
        $this->authorize('manage-org-chart');

        $node = $this->node();

        if ($node === null) {
            return;
        }

        $this->color = (string) OrgPalette::clean($hex);

        $node->update(['color' => $this->color ?: null]);
    }

    public function deleteNode(AuditLogger $audit): void
    {
        $this->authorize('manage-org-chart');

        $node = $this->node();

        if ($node === null) {
            return;
        }

        $nama = (string) ($node->title ?? $node->name);

        // Garisan digugurkan oleh kekunci asing. Membiarkannya bermakna
        // garisan menuding ke ruang kosong.
        $node->delete();

        $audit->log('org_chart.node_deleted', null, $nama);

        $this->selectedIds = array_values(array_diff($this->selectedIds, [$node->id]));
        $this->selectedId = null;
        $this->connectFrom = null;
        $this->dispatch('dbena-toast', message: __('org.editor.deleted'));
    }

    /** Mula menyambung garisan daripada kotak yang dipilih. */
    public function startLink(): void
    {
        $this->authorize('manage-org-chart');

        if ($this->selectedId === null) {
            return;
        }

        $this->connectFrom = $this->selectedId;
    }

    public function cancelLink(): void
    {
        $this->connectFrom = null;
    }

    private function finishLink(int $toId): void
    {
        $from = $this->connectFrom;
        $this->connectFrom = null;

        if ($from === null) {
            return;
        }

        if ($from === $toId) {
            // Gelung kendiri melukis bulatan kecil yang kelihatan seperti
            // pepijat pemaparan.
            $this->dispatch('dbena-toast', message: __('org.editor.connect_same'), variant: 'error');

            return;
        }

        $wujud = OrgLink::where('from_node_id', $from)->where('to_node_id', $toId)->exists();

        if ($wujud) {
            $this->dispatch('dbena-toast', message: __('org.editor.connect_exists'), variant: 'error');

            return;
        }

        OrgLink::create([
            'from_node_id' => $from,
            'to_node_id' => $toId,
            'style' => OrgLinkStyle::tryFrom($this->linkStyle)?->value ?? OrgLinkStyle::Solid->value,
        ]);

        $this->dispatch('dbena-toast', message: __('org.editor.connected'));
    }

    public function removeLink(int $linkId): void
    {
        $this->authorize('manage-org-chart');

        OrgLink::where('id', $linkId)->delete();

        $this->dispatch('dbena-toast', message: __('org.editor.unlinked'));
    }

    /** Lekatkan SEMUA kotak pada grid — membetulkan kedudukan yang terserong. */
    public function tidy(): void
    {
        $this->authorize('manage-org-chart');

        foreach (OrgNode::all() as $node) {
            $node->update([
                'x' => (int) round($node->x / self::GRID) * self::GRID,
                'y' => (int) round($node->y / self::GRID) * self::GRID,
            ]);
        }

        $this->dispatch('dbena-toast', message: __('org.editor.tidied'));
    }

    public function render(): View
    {
        $selected = $this->node();

        return view('livewire.admin.org-chart-editor', [
            'nodes' => OrgNode::orderBy('sort_order')->get(),
            'links' => OrgLink::with(['from', 'to'])->get(),
            'selected' => $selected,
            'selectedLinks' => $selected
                ? OrgLink::with(['from', 'to'])
                    ->where('from_node_id', $selected->id)
                    ->orWhere('to_node_id', $selected->id)
                    ->get()
                : collect(),
            'palette' => \App\Support\OrgPalette::COLORS,
            'selectedCount' => count($this->selectedIds),
            'styles' => OrgNodeStyle::cases(),
            'linkStyles' => OrgLinkStyle::cases(),
        ])->layoutData([
            'pageTitle' => __('org.title'),
            'pageSubtitle' => __('org.editor.drag_hint'),
        ]);
    }
}
