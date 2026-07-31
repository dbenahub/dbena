<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function (): void {
    $this->seed();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
});

it('streams a real CSV file, not a fake toast', function (): void {
    // PEMBETULAN isu #9 — prototaip hanya memaparkan toast.
    $response = $this->actingAs($this->user)
        ->get(route('laporan.export', ['tahun' => 2026, 'bulan' => 7]));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload('laporan-dbena-2026-07.csv');
});

it('starts the CSV with a UTF-8 BOM so Excel renders Malay correctly', function (): void {
    $content = $this->actingAs($this->user)
        ->get(route('laporan.export', ['tahun' => 2026, 'bulan' => 7]))
        ->streamedContent();

    expect(substr($content, 0, 3))->toBe("\xEF\xBB\xBF");
});

it('includes every service and a total row', function (): void {
    $content = $this->actingAs($this->user)
        ->get(route('laporan.export', ['tahun' => 2026, 'bulan' => 7]))
        ->streamedContent();

    expect($content)->toContain('Renovation')
        ->and($content)->toContain('Kabinet')
        ->and($content)->toContain('Bina Rumah')
        ->and($content)->toContain('Divider')
        ->and($content)->toContain('Mihrab')
        ->and($content)->toContain('TOTAL');
});

it('narrows the export to a single service when filtered', function (): void {
    $content = $this->actingAs($this->user)
        ->get(route('laporan.export', ['tahun' => 2026, 'bulan' => 7, 'servis' => 'mihrab']))
        ->streamedContent();

    expect($content)->toContain('Mihrab')
        ->and($content)->not->toContain('Renovation');
});

it('requires authentication', function (): void {
    $this->get(route('laporan.export'))->assertRedirect(route('login'));
});
