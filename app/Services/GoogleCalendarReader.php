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
            /*
             * Sebelum melaporkan kegagalan, tanya soalan yang membezakan
             * dua punca: bolehkah robot bercakap dengan Calendar API
             * LANGSUNG?
             *
             * Panggilan calendarList tidak menyentuh kalendar DBENA. Kalau
             * ia berjaya, API aktif dan token sah — jadi masalahnya
             * perkongsian atau ID. Kalau ia gagal dengan cara yang sama,
             * masalahnya bukan perkongsian langsung, dan menyuruh admin
             * mengongsi semula ialah membuang masa mereka.
             */
            $probe = $this->probe();

            /*
             * Kedua-dua siasatan boleh mengesan API yang dimatikan — satu
             * daripada panggilan acara, satu daripada calendarList. Tanpa
             * semakan ini admin membaca arahan yang SAMA dua kali
             * berturut-turut, yang kelihatan seperti sistem tersekat dan
             * bukan seperti satu masalah yang jelas.
             */
            return [
                'ok' => false,
                'message' => $probe['state'] === 'api_disabled'
                    ? $probe['message']
                    : trim($probe['message'].' '.$e->getMessage()),
                'count' => 0,
            ];
        }

        return [
            'ok' => true,
            'message' => __('roadmap.calendar.ok', ['count' => count($events), 'year' => $year]),
            'count' => count($events),
        ];
    }

    /**
     * Bolehkah robot bercakap dengan Calendar API sama sekali?
     *
     * Mengembalikan satu ayat awalan yang menamakan lapisan mana yang
     * gagal, supaya admin tahu tetapan mana perlu disentuh.
     */
    /**
     * @return array{state: string, message: string}
     */
    private function probe(): array
    {
        try {
            $response = Http::withToken($this->accessToken())
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', ['maxResults' => 10]);
        } catch (Throwable $e) {
            return [
                'state' => 'network',
                'message' => __('roadmap.calendar.probe_network', ['message' => $e->getMessage()]),
            ];
        }

        $reason = (string) ($response->json('error.errors.0.reason') ?? '');
        $message = (string) ($response->json('error.message') ?? '');

        if ($reason === 'accessNotConfigured' || str_contains($message, 'has not been used in project')) {
            return [
                'state' => 'api_disabled',
                'message' => __('roadmap.calendar.api_disabled', ['url' => $this->enableApiUrl() ?? '—']),
            ];
        }

        if (! $response->successful()) {
            return [
                'state' => 'failed',
                'message' => __('roadmap.calendar.probe_failed', [
                    'message' => $message ?: (string) $response->status(),
                ]),
            ];
        }

        // API aktif dan token sah. Senaraikan kalendar yang ROBOT nampak —
        // kalau kalendar DBENA tiada dalam senarai itu, perkongsian belum
        // sampai, dan senarai itu membuktikannya tanpa perlu diteka.
        $ids = collect($response->json('items', []))
            ->pluck('id')
            ->filter()
            ->take(5)
            ->implode(', ');

        return [
            'state' => 'ok',
            'message' => __('roadmap.calendar.probe_ok', [
                'list' => $ids !== '' ? $ids : __('roadmap.calendar.probe_none'),
            ]),
        ];
    }

    /**
     * Pautan terus untuk mengaktifkan Calendar API dalam projek yang betul.
     *
     * ID projek terbenam dalam emel service account:
     *   dbena-sync@PROJEK.iam.gserviceaccount.com
     *
     * Membina pautan itu bermakna admin menekan sekali, bukan mencari
     * projek yang betul antara beberapa dalam Cloud Console.
     */
    private function enableApiUrl(): ?string
    {
        $email = ServiceAccountSheetReader::serviceAccountEmail();

        if (! is_string($email) || ! preg_match('/@([^.]+)\.iam\.gserviceaccount\.com$/', $email, $m)) {
            return null;
        }

        return 'https://console.cloud.google.com/apis/library/calendar-json.googleapis.com?project='.$m[1];
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
            /*
             * Google MEMBERITAHU sebab sebenar; jangan buang.
             *
             * Versi pertama memetakan setiap 403 kepada "belum dikongsi".
             * Tetapi 403 juga bermaksud "Calendar API belum diaktifkan
             * dalam projek ini" — masalah yang sama sekali berbeza dengan
             * pembetulan yang sama sekali berbeza. Menekan kedua-duanya
             * menjadi satu mesej menghantar admin membetulkan perkongsian
             * berulang kali sementara punca sebenar tidak pernah disentuh.
             *
             * Sebab Google dibawa keluar dan pemanggil memutuskan.
             */
            throw SheetReadException::calendarRefused(
                ServiceAccountSheetReader::serviceAccountEmail(),
                (string) ($response->json('error.message') ?? ''),
                (string) ($response->json('error.errors.0.reason') ?? ''),
                $this->enableApiUrl()
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
