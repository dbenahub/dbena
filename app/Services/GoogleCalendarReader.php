<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SheetReadException;
use App\Services\Sheets\ServiceAccountSheetReader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Membaca acara Google Calendar menggunakan service account yang sama
 * seperti sheet.
 *
 * PERKONGSIAN, BUKAN PENIRUAN.
 *
 * Service account tidak boleh menyamar sebagai akaun gmail.com — peniruan
 * memerlukan delegasi seluruh domain, yang hanya wujud untuk Google
 * Workspace. Jadi kalendar mesti DIKONGSI dengan emel service account,
 * sama seperti sheet. Setelah dikongsi, kalendar itu muncul sebagai
 * kalendar service account sendiri dan boleh dibaca terus.
 *
 * Langkah (sekali sahaja):
 *   1. Google Calendar → tetapan kalendar berkenaan
 *   2. "Share with specific people" → tambah emel service account
 *   3. Kebenaran: "See all event details"
 *   4. Salin "Calendar ID" dari bahagian Integrate calendar
 *   5. Tampal ID itu dalam Panel Admin → Roadmap
 *
 * Kalendar kekal peribadi. Tiada "Make available to public", dan tiada
 * alamat iCal rahsia yang boleh diedar tanpa had.
 */
class GoogleCalendarReader
{
    private const SCOPE = 'https://www.googleapis.com/auth/calendar.readonly';

    /*
     * Kunci cache BERASINGAN daripada token sheet.
     *
     * Token Google terikat kepada skop yang dimintanya. Berkongsi satu
     * kunci cache bermakna token sheet sahaja akan digunakan untuk
     * kalendar, dan Google menolaknya dengan 403 yang kelihatan seperti
     * masalah perkongsian — menghantar admin membetulkan perkara yang
     * sudah betul.
     */
    private const TOKEN_CACHE_KEY = 'dbena.calendar.google_access_token';

    /**
     * Acara bagi satu tahun kalendar, dikumpulkan mengikut bulan.
     *
     * @return array<int, array<int, array<string, mixed>>> bulan => acara
     */
    public function eventsByMonth(string $calendarId, int $year): array
    {
        if (trim($calendarId) === '') {
            return [];
        }

        $cacheKey = 'dbena.roadmap.events.'.md5($calendarId).'.'.$year;

        /*
         * Cache lima belas minit.
         *
         * Roadmap dipaparkan pada Dashboard Utama, yang dibuka berpuluh
         * kali sehari. Memanggil Google pada setiap paparan menambah
         * sesaat kepada halaman yang paling kerap dilihat, untuk data
         * yang berubah beberapa kali sebulan.
         */
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($calendarId, $year): array {
            $events = $this->fetch($calendarId, $year);
            $byMonth = [];

            foreach ($events as $event) {
                $byMonth[(int) $event['start']->month][] = $event;
            }

            ksort($byMonth);

            return $byMonth;
        });
    }

    /** Buang cache selepas admin menukar ID kalendar. */
    public function forget(string $calendarId, int $year): void
    {
        Cache::forget('dbena.roadmap.events.'.md5($calendarId).'.'.$year);
    }

    /**
     * Uji sambungan dan pulangkan mesej yang boleh ditindaklanjuti.
     *
     * @return array{ok: bool, message: string, count: int}
     */
    public function test(string $calendarId, int $year): array
    {
        try {
            $events = $this->fetch($calendarId, $year);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'count' => 0];
        }

        return [
            'ok' => true,
            'message' => __('roadmap.calendar.ok', ['count' => count($events), 'year' => $year]),
            'count' => count($events),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetch(string $calendarId, int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->get('https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($calendarId).'/events', [
                    'timeMin' => $from->toRfc3339String(),
                    'timeMax' => $to->toRfc3339String(),
                    // Acara berulang dikembangkan kepada kejadian sebenar.
                    // Tanpa ini, mesyuarat mingguan muncul sekali pada
                    // bulan ia dicipta dan tidak pernah lagi.
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 2500,
                ]);
        } catch (Throwable $e) {
            throw SheetReadException::network($e->getMessage());
        }

        if ($response->status() === 403 || $response->status() === 404) {
            // Mesej khusus KALENDAR. Yang untuk sheet menyuruh admin
            // membuka fail dalam Google Sheets dan menetapkan General
            // access — arahan yang menghantar mereka ke aplikasi yang
            // salah untuk mencari tetapan yang tidak wujud di sana.
            throw SheetReadException::calendarNotShared(
                ServiceAccountSheetReader::serviceAccountEmail()
            );
        }

        if (! $response->successful()) {
            throw SheetReadException::network(
                $response->json('error.message') ?? (string) $response->status()
            );
        }

        $events = [];

        foreach ($response->json('items', []) as $item) {
            if (($item['status'] ?? '') === 'cancelled') {
                continue;
            }

            // Acara sepanjang hari membawa 'date'; acara bermasa membawa
            // 'dateTime'. Membaca salah satu sahaja menyembunyikan separuh
            // kalendar.
            $rawStart = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;

            if ($rawStart === null) {
                continue;
            }

            $start = Carbon::parse($rawStart);

            if ($start->year !== $year) {
                continue;
            }

            $events[] = [
                'title' => trim((string) ($item['summary'] ?? __('roadmap.calendar.untitled'))),
                'start' => $start,
                'allDay' => ! isset($item['start']['dateTime']),
                'location' => trim((string) ($item['location'] ?? '')) ?: null,
                'url' => $item['htmlLink'] ?? null,
            ];
        }

        return $events;
    }

    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $credentials = ServiceAccountSheetReader::credentials();

            $now = time();

            $segments = [
                $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR)),
                $this->base64Url(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => self::SCOPE,
                    'aud' => 'https://oauth2.googleapis.com/token',
                    'exp' => $now + 3600,
                    'iat' => $now,
                ], JSON_THROW_ON_ERROR)),
            ];

            $signingInput = implode('.', $segments);
            $signature = '';

            openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            $response = Http::asForm()
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $signingInput.'.'.$this->base64Url($signature),
                ]);

            if (! $response->successful()) {
                throw SheetReadException::network(
                    $response->json('error_description') ?? 'OAuth token exchange failed'
                );
            }

            return (string) $response->json('access_token');
        });
    }

    private function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
