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

/*
|--------------------------------------------------------------------------
| Susunan mesti sepadan dengan carta rasmi
|--------------------------------------------------------------------------
*/

it('centres each director between its own departments', function (): void {
    // Susunan itu yang menjadikan carta boleh diimbas: mata mengikut
    // garisan menegak ke bawah tanpa perlu mencari.
    $tengah = fn (string $title) => OrgNode::where('title', $title)->firstOrFail()->centerX();

    $anak = ($tengah('MARKETING DEPARTMENT') + $tengah('ID DEPARTMENT')) / 2;

    expect(abs($tengah('Executive Director') - $anak))->toBeLessThan(20);
});

it('centres the managing director over the four directors', function (): void {
    $tengah = fn (string $title) => OrgNode::where('title', $title)->firstOrFail()->centerX();

    $purata = (
        $tengah('Executive Director')
        + $tengah('Management Department')
        + $tengah('Contract & Project Director')
        + $tengah('Operation Director')
    ) / 4;

    expect(abs($tengah('Managing Director') - $purata))->toBeLessThan(30);
});

it('lays the four directors on one row', function (): void {
    $y = OrgNode::whereIn('title', [
        'Executive Director', 'Management Department',
        'Contract & Project Director', 'Operation Director',
    ])->pluck('y')->unique();

    expect($y)->toHaveCount(1);
});

it('lays the seven departments on one row', function (): void {
    $y = OrgNode::whereIn('title', [
        'MARKETING DEPARTMENT', 'ID DEPARTMENT', 'HR Manager', 'Freelancer Account',
        'CONTRACT DEPARTMENT', 'PROJECT DEPARTMENT', 'PRODUCTION DEPARTMENT',
    ])->pluck('y')->unique();

    expect($y)->toHaveCount(1);
});

it('keeps the boxes from overlapping each other', function (): void {
    // Kotak bertindih bermakna satu nama tersembunyi di belakang nama lain,
    // dan tiada siapa perasan sehingga orang itu bertanya kenapa dia tiada
    // dalam carta.
    $nodes = OrgNode::all();

    foreach ($nodes as $a) {
        foreach ($nodes as $b) {
            if ($a->id >= $b->id) {
                continue;
            }

            $bertindihX = $a->x < $b->x + $b->width && $b->x < $a->x + $a->width;
            $bertindihY = $a->y < $b->bottomY() && $b->y < $a->bottomY();

            expect($bertindihX && $bertindihY)->toBeFalse(
                "'{$a->title}' bertindih dengan '{$b->title}'"
            );
        }
    }
});

it('carries the middle Head of Dept. line', function (): void {
    // Memampatkannya ke dalam tajuk menghasilkan satu baris panjang yang
    // membalut dengan hodoh dan kehilangan hierarki tipografi.
    expect(OrgNode::where('title', 'MARKETING DEPARTMENT')->firstOrFail()->subtitle)
        ->toBe('Head of Dept.');
});

it('gives department boxes room for three lines', function (): void {
    $dept = OrgNode::where('title', 'CONTRACT DEPARTMENT')->firstOrFail();
    $exec = OrgNode::where('title', 'Operation Director')->firstOrFail();

    expect($dept->boxHeight())->toBeGreaterThan($exec->boxHeight());
});

it('caps the box height like the width', function (): void {
    // Satu kotak setinggi halaman menolak segala-galanya keluar dari skrin.
    $node = OrgNode::first();

    Livewire::actingAs($this->admin)
        ->test(OrgChartEditor::class)
        ->call('selectNode', $node->id)
        ->set('height', 5000)
        ->call('saveNode');

    expect($node->fresh()->boxHeight())->toBe(180);
});

it('rebuilds the official layout on demand', function (): void {
    // Seeder berundur sebaik ada nod. Ini cara sengaja untuk mengatasi
    // perlindungan itu.
    OrgNode::first()->update(['x' => 4321]);

    $this->artisan('dbena:carta-reset', ['--force' => true])->assertSuccessful();

    expect(OrgNode::where('x', 4321)->exists())->toBeFalse()
        ->and(OrgNode::count())->toBeGreaterThan(10)
        ->and(OrgLink::count())->toBeGreaterThan(10);
});
