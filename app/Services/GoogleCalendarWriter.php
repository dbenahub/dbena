<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TaskMark;
use App\Models\MonthlyTask;
use App\Models\TaskDayMark;
use App\Services\Sheets\ServiceAccountSheetReader;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Menulis tugasan DBENA ke Google Calendar.
 *
 * PERKONGSIAN DENGAN HAK MENULIS, bukan peniruan.
 *
 * Service account tidak boleh menyamar sebagai akaun gmail.com. Untuk
 * MEMBACA, kalendar dikongsi dengan kebenaran "See all event details".
 * Untuk MENULIS, ia mesti dikongsi dengan "Make changes to events" —
 * kebenaran yang berbeza, dan kegagalan paling biasa ialah menyangka
 * perkongsian baca sudah memadai.
 *
 * Skop juga berbeza: calendar.events, bukan calendar.readonly. Token
 * Google terikat kepada skop yang dimintanya, jadi token baca yang
 * digunakan untuk menulis ditolak dengan 403 yang kelihatan seperti
 * masalah perkongsian.
 */
class GoogleCalendarWriter
{
    /*
     * Skop PENUH, bukan calendar.events sahaja.
     *
     * calendar.events membenarkan penulisan acara tetapi TIDAK membenarkan
     * calendarList — dan tanpa calendarList kita tidak boleh bertanya
     * kepada Google apa kebenaran sebenar yang dimiliki robot ini. Tanpa
     * jawapan itu, setiap 403 hanya boleh diteka.
     */
    private const SCOPE = 'https://www.googleapis.com/auth/calendar';



    /** Warna acara Google mengikut status tugasan. */
    private const COLOR_IDS = [
        'planning' => '5',   // kuning
        'due_date' => '6',   // oren
        'complete' => '10',  // hijau
        'kiv' => '3',        // ungu
        'cancel' => '11',    // merah
    ];

    /**
     * Segerak semua tanda satu bulan ke Google Calendar.
     *
     * @return array{ok: bool, created: int, updated: int, deleted: int, message: string}
     */
    public function syncMonth(string $calendarId, int $year, int $month): array
    {
        if (trim($calendarId) === '') {
            return $this->fail(__('calendar_task.google.not_connected'));
        }

        $tasks = MonthlyTask::with(['marks', 'department'])
            ->where('year', $year)->where('month', $month)
            ->get();

        $dicipta = $dikemas = $dipadam = 0;

        try {
            $token = $this->accessToken();

            // Skop yang ditolak dikesan sebelum gelung bermula: satu
            // panggilan murah lebih baik daripada gagal pada tugasan
            // ketujuh belas dan meninggalkan enam belas separuh disegerak.
            if ($this->scopeRejected($this->calendarList())) {
                $this->forgetToken();
                $token = $this->accessToken();
            }

            foreach ($tasks as $task) {
                foreach ($task->marks as $mark) {
                    /*
                     * Tugasan yang dibatalkan DIPADAM daripada kalendar,
                     * bukan ditulis sebagai acara merah. Kalendar yang
                     * memaparkan acara yang dibatalkan bermakna orang
                     * masih pergi ke mesyuarat yang tidak berlaku.
                     */
                    if ($mark->mark === TaskMark::Cancel) {
                        if ($mark->google_event_id) {
                            $this->delete($token, $calendarId, (string) $mark->google_event_id);
                            $mark->forceFill(['google_event_id' => null, 'google_synced_at' => now()])->save();
                            $dipadam++;
                        }

                        continue;
                    }

                    $hasil = $this->upsert($token, $calendarId, $task, $mark, $year, $month);

                    $hasil === 'created' ? $dicipta++ : $dikemas++;
                }
            }
        } catch (Throwable $e) {
            /*
             * Sebelum melaporkan kegagalan, tanya Google apa kebenaran
             * sebenar yang ada. Mesej yang meneka menghantar admin
             * membetulkan perkara yang sudah betul.
             */
            return $this->fail($e->getMessage()) + ['diagnosis' => $this->diagnose($calendarId)];
        }

        return [
            'ok' => true,
            'created' => $dicipta,
            'updated' => $dikemas,
            'deleted' => $dipadam,
            'message' => __('calendar_task.google.done', [
                'created' => $dicipta, 'updated' => $dikemas, 'deleted' => $dipadam,
            ]),
        ];
    }

    /**
     * Tanya Google apa kebenaran sebenar yang dimiliki robot ini.
     *
     * calendarList memulangkan accessRole bagi setiap kalendar yang
     * dikongsi dengan service account: reader, writer atau owner. Itu
     * FAKTA, bukan tekaan — dan ia membezakan tiga kegagalan yang semuanya
     * memberi 403:
     *
     *   · kalendar langsung tiada dalam senarai → belum dikongsi
     *   · ada tetapi accessRole 'reader'        → dikongsi untuk baca sahaja
     *   · ada dan 'writer' tetapi masih 403     → ID kalendar salah
     *
     * Versi pertama memetakan setiap 403 kepada mesej kedua, jadi sesiapa
     * yang menghadapi yang pertama atau ketiga akan membetulkan perkara
     * yang sudah betul, berulang kali.
     *
     * @return array<string, mixed>
     */
    public function diagnose(?string $calendarId = null): array
    {
        try {
            $response = $this->calendarList();

            /*
             * "Insufficient authentication scopes" bermakna token yang
             * dicache diminta dengan skop LAMA. Membuangnya dan mencuba
             * sekali lagi memulihkan keadaan serta-merta, dan bukan
             * selepas token tamat tempoh lima puluh minit kemudian.
             */
            if ($this->scopeRejected($response)) {
                $this->forgetToken();
                $response = $this->calendarList();
            }
        } catch (Throwable $e) {
            return ['ok' => false, 'reason' => 'network', 'message' => $e->getMessage(), 'calendars' => []];
        }

        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?? $response->status());
            $reason = (string) ($response->json('error.errors.0.reason') ?? '');

            return [
                'ok' => false,
                'reason' => $reason === 'accessNotConfigured' ? 'api_disabled' : 'token',
                'message' => $message,
                'calendars' => [],
                'enableUrl' => $this->enableApiUrl(),
            ];
        }

        $calendars = collect($response->json('items', []))
            ->map(fn (array $c) => [
                'id' => (string) ($c['id'] ?? ''),
                'name' => (string) ($c['summary'] ?? '—'),
                'role' => (string) ($c['accessRole'] ?? '—'),
                'canWrite' => in_array($c['accessRole'] ?? '', ['writer', 'owner'], true),
            ])
            ->values()
            ->all();

        $dicari = trim((string) $calendarId);
        $padan = collect($calendars)->firstWhere('id', $dicari);

        return [
            'ok' => true,
            'reason' => match (true) {
                $dicari === '' => 'no_id',
                $padan === null => 'not_shared',
                ! $padan['canWrite'] => 'read_only',
                default => 'ready',
            },
            'message' => '',
            'calendars' => $calendars,
            'target' => $padan,
            'email' => ServiceAccountSheetReader::serviceAccountEmail(),
        ];
    }

    private function calendarList(): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->accessToken())
            ->timeout((int) config('dbena.sheets.timeout_seconds'))
            ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList', ['maxResults' => 50]);
    }

    /** Adakah Google menolak SKOP token, bukan kebenaran kalendar? */
    private function scopeRejected(\Illuminate\Http\Client\Response $response): bool
    {
        if ($response->successful()) {
            return false;
        }

        $message = (string) ($response->json('error.message') ?? '');
        $reason = (string) ($response->json('error.errors.0.reason') ?? '');

        return $reason === 'insufficientPermissions'
            || str_contains($message, 'insufficient authentication scopes');
    }

    /** Cipta atau kemas kini satu acara. */
    private function upsert(
        string $token,
        string $calendarId,
        MonthlyTask $task,
        TaskDayMark $mark,
        int $year,
        int $month,
    ): string {
        $body = $this->eventBody($task, $mark, $year, $month);

        if ($mark->google_event_id) {
            $response = Http::withToken($token)
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->patch($this->url($calendarId).'/'.rawurlencode((string) $mark->google_event_id), $body);

            /*
             * 404 bermakna acara dipadam terus dalam Google Calendar.
             * Menganggapnya ralat bermakna sync tidak akan berjaya lagi
             * selama-lamanya; menciptanya semula memulihkan keadaan.
             */
            if ($response->status() === 404) {
                $mark->forceFill(['google_event_id' => null])->save();

                return $this->upsert($token, $calendarId, $task, $mark, $year, $month);
            }

            $this->guard($response);

            $mark->forceFill(['google_synced_at' => now()])->save();

            return 'updated';
        }

        $response = Http::withToken($token)
            ->timeout((int) config('dbena.sheets.timeout_seconds'))
            ->post($this->url($calendarId), $body);

        $this->guard($response);

        $mark->forceFill([
            'google_event_id' => (string) $response->json('id'),
            'google_synced_at' => now(),
        ])->save();

        return 'created';
    }

    private function delete(string $token, string $calendarId, string $eventId): void
    {
        $response = Http::withToken($token)
            ->timeout((int) config('dbena.sheets.timeout_seconds'))
            ->delete($this->url($calendarId).'/'.rawurlencode($eventId));

        // 404 dan 410 bermakna ia sudah tiada. Itu keadaan yang kita mahu.
        if (in_array($response->status(), [404, 410], true)) {
            return;
        }

        $this->guard($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventBody(MonthlyTask $task, TaskDayMark $mark, int $year, int $month): array
    {
        $tarikh = Carbon::create($year, $month, $mark->day);

        $keterangan = collect([
            $task->department?->name,
            $task->action_by ? __('task.col.action_by').': '.$task->action_by : null,
            $task->monitor_by ? __('task.col.monitor_by').': '.$task->monitor_by : null,
            $task->remark,
            __('calendar_task.google.source'),
        ])->filter()->implode("\n");

        $body = [
            // Huruf status disertakan dalam tajuk. Kalendar Google tidak
            // memaparkan warna kepada semua peranti, dan huruf itu berfungsi
            // di mana-mana.
            'summary' => '['.$mark->mark->letter().'] '.$task->title,
            'description' => $keterangan,
            'colorId' => self::COLOR_IDS[$mark->mark->value] ?? '8',
        ];

        if ($mark->start_time) {
            $mula = $tarikh->copy()->setTimeFromTimeString((string) $mark->start_time);

            $body['start'] = ['dateTime' => $mula->toRfc3339String(), 'timeZone' => config('app.timezone')];
            $body['end'] = ['dateTime' => $mula->copy()->addHour()->toRfc3339String(), 'timeZone' => config('app.timezone')];

            return $body;
        }

        /*
         * Acara sepanjang hari. Tarikh TAMAT ialah hari BERIKUTNYA —
         * Google menganggap julat sepanjang hari sebagai separa terbuka,
         * jadi tarikh tamat yang sama menghasilkan acara sifar panjang
         * yang tidak muncul langsung dalam paparan bulan.
         */
        $body['start'] = ['date' => $tarikh->toDateString()];
        $body['end'] = ['date' => $tarikh->copy()->addDay()->toDateString()];

        return $body;
    }

    private function url(string $calendarId): string
    {
        return 'https://www.googleapis.com/calendar/v3/calendars/'.rawurlencode($calendarId).'/events';
    }

    /** Terjemah kegagalan Google kepada ayat yang boleh ditindaklanjuti. */
    private function guard(\Illuminate\Http\Client\Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $reason = (string) ($response->json('error.errors.0.reason') ?? '');
        $message = (string) ($response->json('error.message') ?? '');

        if ($reason === 'accessNotConfigured' || str_contains($message, 'has not been used in project')) {
            throw new \RuntimeException(__('calendar_task.google.api_disabled', [
                'url' => $this->enableApiUrl() ?? '—',
            ]));
        }

        if ($reason === 'insufficientPermissions' || str_contains($message, 'insufficient authentication scopes')) {
            // Bukan masalah perkongsian langsung. Token itu sendiri salah.
            throw new \RuntimeException(__('calendar_task.google.bad_scope'));
        }

        if (in_array($response->status(), [401, 403], true)) {
            // Kegagalan paling biasa: kalendar dikongsi untuk BACA sahaja.
            throw new \RuntimeException(__('calendar_task.google.needs_write', [
                'email' => ServiceAccountSheetReader::serviceAccountEmail() ?? '—',
                'message' => $message,
            ]));
        }

        throw new \RuntimeException($message !== '' ? $message : (string) $response->status());
    }

    private function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(50), function (): string {
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
                throw new \RuntimeException(
                    $response->json('error_description') ?? 'OAuth token exchange failed'
                );
            }

            return (string) $response->json('access_token');
        });
    }

    private function enableApiUrl(): ?string
    {
        $email = ServiceAccountSheetReader::serviceAccountEmail();

        if (! is_string($email) || ! preg_match('/@([^.]+)\.iam\.gserviceaccount\.com$/', $email, $m)) {
            return null;
        }

        return 'https://console.cloud.google.com/apis/library/calendar-json.googleapis.com?project='.$m[1];
    }

    /**
     * Kunci cache token DITERBITKAN daripada skop.
     *
     * Token Google terikat kepada skop yang dimintanya. Kunci tetap
     * bermakna menukar skop dalam kod tidak membuang token lama — ia
     * kekal dalam cache sehingga tamat tempoh, dan setiap panggilan
     * sehingga itu gagal dengan "Request had insufficient authentication
     * scopes" terhadap kod yang sebenarnya betul.
     *
     * Kegagalan itu sembuh sendiri selepas lima puluh minit, iaitu tepat
     * cukup lama untuk seseorang menghabiskan petang membetulkan
     * perkongsian kalendar yang tidak pernah rosak.
     */
    private function tokenCacheKey(): string
    {
        return 'dbena.google.token.'.substr(sha1(self::SCOPE), 0, 16);
    }

    /** Buang token yang dicache — dipanggil apabila Google menolak skopnya. */
    private function forgetToken(): void
    {
        Cache::forget($this->tokenCacheKey());
    }

    private function base64Url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * @return array{ok: bool, created: int, updated: int, deleted: int, message: string}
     */
    private function fail(string $message): array
    {
        return ['ok' => false, 'created' => 0, 'updated' => 0, 'deleted' => 0, 'message' => $message];
    }
}
