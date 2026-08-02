<?php

declare(strict_types=1);

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Enums\UserRole;
use App\Livewire\Admin\OrgChartEditor;
use App\Livewire\Dashboard\OrgChart;
use App\Models\OrgLink;
use App\Models\OrgNode;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();

    $this->admin = User::where('role', UserRole::Admin)->firstOrFail();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Carta bermula dengan carta DBENA sebenar
|--------------------------------------------------------------------------
*/

it('seeds the real DBENA chart rather than an empty canvas', function (): void {
    // Kanvas kosong ialah cara paling pasti untuk memastikan ciri ini tidak
    // pernah digunakan.
    expect(OrgNode::count())->toBeGreaterThan(10)
        ->and(OrgNode::where('name', 'AHMAD NIZAMUDDIN BIN ROSNAN')->exists())->toBeTrue()
        ->and(OrgLink::count())->toBeGreaterThan(10);
});

it('draws freelancers with a dashed line', function (): void {
    // Melukisnya pepejal bermakna carta mendakwa freelancer melapor secara
    // langsung, yang mengubah maksud carta.
    expect(OrgLink::where('style', OrgLinkStyle::Dashed->value)->count())->toBeGreaterThan(0);
});

it('does not overwrite hand-dragged positions when seeded again', function (): void {
    OrgNode::query()->first()->update(['x' => 999, 'y' => 888]);

    $this->seed(Database\Seeders\OrgChartSeeder::class);

    expect(OrgNode::where('x', 999)->where('y', 888)->exists())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Paparan
|--------------------------------------------------------------------------
*/

it('shows the chart to a plain user', function (): void {
    Livewire::actingAs($this->user)
        ->test(OrgChart::class)
        ->assertSee('AHMAD NIZAMUDDIN BIN ROSNAN')
        ->assertSee(__('org.view_only'));
});

it('offers a plain user no way to change the chart', function (): void {
    // Cara paling pasti untuk memastikan skrin ini kekal paparan sahaja
    // ialah tidak memberinya cara untuk menulis.
    $kaedah = get_class_methods(OrgChart::class);

    foreach (['addNode', 'saveNode', 'deleteNode', 'moveNode', 'startLink', 'removeLink'] as $tulis) {
        expect($kaedah)->not->toContain($tulis);
    }
});

it('hides the edit and export buttons from a plain user', function (): void {
    Livewire::actingAs($this->user)
        ->test(OrgChart::class)
        ->assertDontSee(__('org.edit'))
        ->assertDontSee(__('org.export'));
});

it('shows them to an admin', function (): void {
    Livewire::actingAs($this->admin)
        ->test(OrgChart::class)
        ->assertSee(__('org.edit'))
        ->assertSee(__('org.export'));
});

/*
|--------------------------------------------------------------------------
| Editor — Admin sahaja
|--------------------------------------------------------------------------
*/

it('keeps a plain user out of the editor', function (): void {
    $this->actingAs($this->user)->get('/admin/carta-organisasi')->assertForbidden();
});

it('lets an admin in', function (): void {
    $this->actingAs($this->admin)->get('/admin/carta-organisasi')->assertOk();
});

it('refuses a plain user who calls a write method directly', function (): void {
    // Menyorok butang tidak menghalang panggilan Livewire terus.
    Livewire::actingAs($this->user)
        ->test(OrgChartEditor::class)
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Seret & susun
|--------------------------------------------------------------------------
*/

it('saves a dragged position', function (): void {
    $node = OrgNode::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('moveNode', $node->id, 347, 213);

    // Dilekat pada grid 10px.
    expect($node->fresh()->x)->toBe(350)
        ->and($node->fresh()->y)->toBe(210);
});

it('refuses a negative position', function (): void {
    // Kotak yang diseret melepasi tepi kiri menjadi tidak boleh dicapai
    // dan kelihatan seolah-olah ia telah dipadam.
    $node = OrgNode::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('moveNode', $node->id, -500, -300);

    expect($node->fresh()->x)->toBe(0)
        ->and($node->fresh()->y)->toBe(0);
});

it('adds a new box below the lowest one, not on top of another', function (): void {
    // Pada (0,0) ia mendarat di belakang kotak sedia ada dan kelihatan
    // seolah-olah butang itu tidak berfungsi — jadi admin menekannya
    // berulang kali dan mencipta lima kotak bertindih.
    $bawah = (int) OrgNode::max('y');
    $sebelum = OrgNode::count();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('addNode');

    expect(OrgNode::count())->toBe($sebelum + 1)
        ->and((int) OrgNode::max('y'))->toBeGreaterThan($bawah);
});

it('edits a box and saves it', function (): void {
    $node = OrgNode::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $node->id)
        ->set('title', 'Chief Technology Officer')
        ->set('name', 'SITI AMINAH')
        ->set('style', OrgNodeStyle::Executive->value)
        ->call('saveNode');

    expect($node->fresh()->title)->toBe('Chief Technology Officer')
        ->and($node->fresh()->name)->toBe('SITI AMINAH');
});

it('caps the box width', function (): void {
    // Kotak selebar 9000px menolak setiap kotak lain keluar dari skrin.
    $node = OrgNode::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $node->id)
        ->set('width', 9000)
        ->call('saveNode');

    expect($node->fresh()->width)->toBe(420);
});

it('deletes a box together with its lines', function (): void {
    // Garisan yang tertinggal menuding ke ruang kosong.
    $node = OrgNode::whereHas('linksTo')->first();
    $bilangan = OrgLink::where('to_node_id', $node->id)
        ->orWhere('from_node_id', $node->id)->count();

    expect($bilangan)->toBeGreaterThan(0);

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $node->id)
        ->call('deleteNode');

    expect(OrgNode::find($node->id))->toBeNull()
        ->and(OrgLink::where('to_node_id', $node->id)->orWhere('from_node_id', $node->id)->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| Garisan
|--------------------------------------------------------------------------
*/

it('connects two boxes with a line', function (): void {
    [$a, $b] = OrgNode::orderByDesc('id')->take(2)->get()->all();

    OrgLink::where('from_node_id', $a->id)->where('to_node_id', $b->id)->delete();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $a->id)
        ->call('startLink')
        ->call('selectNode', $b->id);

    expect(OrgLink::where('from_node_id', $a->id)->where('to_node_id', $b->id)->exists())->toBeTrue();
});

it('refuses to connect a box to itself', function (): void {
    // Gelung kendiri melukis bulatan kecil yang kelihatan seperti pepijat
    // pemaparan.
    $node = OrgNode::first();
    $sebelum = OrgLink::count();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $node->id)
        ->call('startLink')
        ->call('selectNode', $node->id);

    expect(OrgLink::count())->toBe($sebelum);
});

it('refuses a duplicate line', function (): void {
    // Garisan pendua kelihatan lebih tebal daripada yang lain dan tiada
    // siapa dapat tahu sebabnya.
    $link = OrgLink::first();
    $sebelum = OrgLink::count();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $link->from_node_id)
        ->call('startLink')
        ->call('selectNode', $link->to_node_id);

    expect(OrgLink::count())->toBe($sebelum);
});

it('lets connect mode win over selection while it is live', function (): void {
    // Dua makna untuk satu isyarat memerlukan mod, dan mod itu mesti
    // menang selagi ia hidup.
    [$a, $b] = OrgNode::orderByDesc('id')->take(2)->get()->all();

    OrgLink::where('from_node_id', $a->id)->where('to_node_id', $b->id)->delete();

    $component = Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $a->id)
        ->call('startLink')
        ->call('selectNode', $b->id);

    // Kotak yang dipilih kekal $a — klik kedua menyambung, bukan memilih.
    expect($component->get('selectedId'))->toBe($a->id)
        ->and($component->get('connectFrom'))->toBeNull();
});

it('removes a line', function (): void {
    $link = OrgLink::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('removeLink', $link->id);

    expect(OrgLink::find($link->id))->toBeNull();
});

it('creates a dashed line when that style is chosen', function (): void {
    [$a, $b] = OrgNode::orderByDesc('id')->take(2)->get()->all();

    OrgLink::where('from_node_id', $a->id)->where('to_node_id', $b->id)->delete();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->set('linkStyle', OrgLinkStyle::Dashed->value)
        ->call('selectNode', $a->id)
        ->call('startLink')
        ->call('selectNode', $b->id);

    expect(OrgLink::where('from_node_id', $a->id)->where('to_node_id', $b->id)->firstOrFail()->style)
        ->toBe(OrgLinkStyle::Dashed);
});

/*
|--------------------------------------------------------------------------
| Eksport PDF
|--------------------------------------------------------------------------
*/

it('refuses the PDF export to a plain user', function (): void {
    // Carta ini membawa nama penuh setiap kakitangan.
    $this->actingAs($this->user)->get(route('carta.pdf'))->assertForbidden();
});

it('lets an admin download the PDF', function (): void {
    $response = $this->actingAs($this->admin)->get(route('carta.pdf'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
