<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Dashboard\ServiceDetail;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
});

/*
|--------------------------------------------------------------------------
| Suis kepadatan
|--------------------------------------------------------------------------
*/

it('puts the density toggle beside the language switcher', function (): void {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('app.density_compact'))
        ->assertSee('dbenaSetDensity', escape: false);
});

it('applies the saved density before the stylesheet loads', function (): void {
    // Menetapkannya selepas paint bermakna halaman berkelip dari lapang ke
    // padat setiap kali dimuatkan.
    $html = $this->actingAs($this->user)->get(route('dashboard'))->getContent();

    $skrip = mb_strpos($html, "localStorage.getItem('dbena_density')");
    $gaya = mb_strpos($html, 'resources/css/app.css');

    expect($skrip)->not->toBeFalse();

    if ($gaya !== false) {
        expect($skrip)->toBeLessThan($gaya);
    }
});

/*
|--------------------------------------------------------------------------
| Peta perjalanan pada telefon
|--------------------------------------------------------------------------
*/

it('renders a vertical stage list for phones alongside the wide road', function (): void {
    // Jalan raya 1240px pada skrin 390px ialah tatal mendatar melalui kanvas
    // enam kali lebih lebar daripada tetingkap. Bentuk liku itu maksud peta
    // pada desktop; pada telefon ia hanya menyembunyikan nombor.
    $html = Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->html();

    expect($html)->toContain('md:hidden')
        ->and($html)->toContain('hidden overflow-x-auto px-1 md:block');
});

it('shows every funnel stage in the phone list too', function (): void {
    $html = Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->html();

    // Setiap tajuk peringkat muncul dua kali: sekali dalam senarai telefon,
    // sekali di atas jalan raya. Kurang daripada itu bermakna satu paparan
    // kehilangan peringkat.
    foreach (['lead', 'site_visit', 'quotation', 'sales'] as $key) {
        expect(mb_substr_count($html, __('journey.stage.'.$key)))->toBeGreaterThanOrEqual(2);
    }
});

/*
|--------------------------------------------------------------------------
| Susun atur mudah alih sedia ada masih utuh
|--------------------------------------------------------------------------
*/

it('keeps a mobile card layout for the critical data table', function (): void {
    // Jadual sebelas lajur tidak boleh dibaca pada telefon walau apa pun
    // kepadatan; ia perlu susun atur kad yang berasingan.
    $html = Livewire::actingAs($this->user)
        ->test(ServiceDetail::class, ['key' => 'renovation'])
        ->html();

    expect($html)->toContain('lg:hidden');
});

it('renders the dashboard overview without a fixed-width overflow trap', function (): void {
    // Lebar tetap dibenarkan HANYA di dalam bekas yang menatal. Lebar tetap
    // tanpa overflow-x memaksa seluruh halaman menatal ke sisi.
    $html = Livewire::actingAs($this->user)->test(Overview::class)->html();

    preg_match_all('/min-w-\[\d+px\]/', $html, $padanan);

    foreach ($padanan[0] as $lebar) {
        $pos = mb_strpos($html, $lebar);
        $sebelum = mb_substr($html, max(0, $pos - 400), 400);

        expect($sebelum)->toContain('overflow-x-auto');
    }
});
