<?php

declare(strict_types=1);

use App\Enums\RoadmapStatus;
use App\Models\RoadmapPlan;

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

/*
|--------------------------------------------------------------------------
| Calendar ID daripada apa sahaja yang ditampal
|--------------------------------------------------------------------------
*/

it('pulls the id out of the embed link', function (): void {
    // Ini yang orang sebenarnya salin. "Calendar ID" ialah istilah Google,
    // bukan istilah manusia — apa yang mereka jumpa ialah pautan, dan
    // pautan itu MENGANDUNGI ID.
    expect(RoadmapPlan::extractCalendarId(
        'https://calendar.google.com/calendar/embed?src=dbenagroup%40gmail.com&ctz=Asia%2FKuala_Lumpur'
    ))->toBe('dbenagroup@gmail.com');
});

it('pulls the id out of a group calendar embed link', function (): void {
    expect(RoadmapPlan::extractCalendarId(
        'https://calendar.google.com/calendar/embed?src=c_abc123%40group.calendar.google.com'
    ))->toBe('c_abc123@group.calendar.google.com');
});

it('pulls the id out of a cid link', function (): void {
    expect(RoadmapPlan::extractCalendarId(
        'https://calendar.google.com/calendar/u/0?cid=dbenagroup%40gmail.com'
    ))->toBe('dbenagroup@gmail.com');
});

it('pulls the id out of an iCal link', function (): void {
    expect(RoadmapPlan::extractCalendarId(
        'https://calendar.google.com/calendar/ical/dbenagroup%40gmail.com/private/basic.ics'
    ))->toBe('dbenagroup@gmail.com');
});

it('leaves a plain calendar id alone', function (): void {
    expect(RoadmapPlan::extractCalendarId('dbenagroup@gmail.com'))->toBe('dbenagroup@gmail.com')
        ->and(RoadmapPlan::extractCalendarId('  c_x@group.calendar.google.com  '))
        ->toBe('c_x@group.calendar.google.com');
});

it('returns nothing for a link with no id in it', function (): void {
    // Tekaan yang salah lebih teruk daripada tiada tekaan: ia menghasilkan
    // 403 yang bermaksud "belum dikongsi", dan admin dihantar membetulkan
    // perkongsian yang sudah betul.
    expect(RoadmapPlan::extractCalendarId('https://calendar.google.com/'))->toBeNull()
        ->and(RoadmapPlan::extractCalendarId(''))->toBeNull()
        ->and(RoadmapPlan::extractCalendarId(null))->toBeNull();
});

it('rejects a pasted url as a usable id', function (): void {
    expect(RoadmapPlan::looksLikeCalendarId('https://calendar.google.com/calendar/embed?src=a%40b.com'))
        ->toBeFalse()
        ->and(RoadmapPlan::looksLikeCalendarId('dbenagroup@gmail.com'))->toBeTrue()
        ->and(RoadmapPlan::looksLikeCalendarId('bukan id'))->toBeFalse();
});
