<?php

declare(strict_types=1);

use App\Enums\RoadmapStatus;

it('counts active, campaign and resumed months toward target', function (): void {
    expect(RoadmapStatus::ActiveAllYear->countsTowardTarget())->toBeTrue()
        ->and(RoadmapStatus::Campaign->countsTowardTarget())->toBeTrue()
        ->and(RoadmapStatus::Resumed->countsTowardTarget())->toBeTrue();
});

it('gives a deliberately paused month no target', function (): void {
    // "Pause" tidak bermakna servis gagal; ia bermakna kempen dihentikan
    // dengan sengaja. Sasaran yang dibawa oleh bulan itu ialah sasaran
    // yang tiada siapa komited kepadanya.
    expect(RoadmapStatus::Paused->countsTowardTarget())->toBeFalse()
        ->and(RoadmapStatus::None->countsTowardTarget())->toBeFalse();
});

it('cycles through every status and back to the start', function (): void {
    // Kitaran yang melangkau satu status bermakna status itu tidak boleh
    // dicapai melalui klik, dan admin menganggapnya rosak.
    $seen = [];
    $status = RoadmapStatus::None;

    for ($i = 0; $i < count(RoadmapStatus::cases()); $i++) {
        $seen[] = $status->value;
        $status = $status->next();
    }

    expect($status)->toBe(RoadmapStatus::None)
        ->and($seen)->toHaveCount(count(RoadmapStatus::cases()))
        ->and(array_unique($seen))->toHaveCount(count(RoadmapStatus::cases()));
});

it('keeps text readable on every cell colour', function (): void {
    // Sel gelap dengan teks gelap ialah maklumat yang hilang, bukan gaya.
    foreach (RoadmapStatus::cases() as $status) {
        expect($status->textColor())->not->toBe($status->color());
    }
});
