<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;

beforeEach(function (): void {
    $this->seed();
    $this->user = User::where('role', UserRole::User)->firstOrFail();
});

it('defaults to Bahasa Malaysia', function (): void {
    expect(config('app.locale'))->toBe('ms');
});

it('switches the whole interface when the locale changes', function (): void {
    $this->actingAs($this->user)
        ->post(route('locale.update', 'en'))
        ->assertRedirect();

    expect($this->user->fresh()->locale)->toBe('en')
        ->and(session('locale'))->toBe('en');
});

it('rejects an unsupported locale at the route level', function (): void {
    $this->actingAs($this->user)
        ->post('/locale/fr')
        ->assertNotFound();
});

it('returns model names in the active locale', function (): void {
    $service = Service::where('key', 'kabinet')->firstOrFail();

    app()->setLocale('ms');
    expect($service->name)->toBe('Kabinet');

    app()->setLocale('en');
    expect($service->fresh()->name)->toBe('Cabinetry');
});

it('falls back to Malay when an English value is blank', function (): void {
    $service = Service::where('key', 'mihrab')->firstOrFail();
    $service->update(['name_en' => '']);

    app()->setLocale('en');

    expect($service->fresh()->name)->toBe('Mihrab');
});

it('keeps every UI string translated in both languages', function (): void {
    // Menghalang kunci tertinggal — akar punca "separuh bertukar" bahasa.
    $files = ['app', 'auth', 'dashboard', 'service', 'laporan', 'tetapan', 'admin', 'calendar'];

    foreach ($files as $file) {
        $ms = array_keys(Arr::dot(require lang_path("ms/{$file}.php")));
        $en = array_keys(Arr::dot(require lang_path("en/{$file}.php")));

        sort($ms);
        sort($en);

        expect($en)->toBe($ms, "Kunci tidak sepadan dalam lang/*/{$file}.php");
    }
});
