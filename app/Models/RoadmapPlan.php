<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapPlan extends Model
{
    protected $fillable = ['year', 'title', 'subtitle', 'summary', 'calendar_id'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'summary' => 'array'];
    }

    /**
     * Keluarkan Calendar ID daripada apa sahaja yang ditampal admin.
     *
     * "Calendar ID" ialah istilah Google, bukan istilah manusia. Apa yang
     * orang jumpa dan salin ialah pautan — URL embed dari Integrate
     * calendar, alamat bar URL semasa melihat kalendar, atau pautan iCal.
     * Kesemuanya MENGANDUNGI ID itu.
     *
     * Menolak pautan itu dengan "ID tidak sah" adalah tepat secara teknikal
     * dan tidak berguna sepenuhnya: maklumat yang diperlukan ada di depan
     * mata, dan kita menghantar orang mencari semula sesuatu yang mereka
     * sudah pegang.
     *
     * Bentuk yang dikenali:
     *   https://calendar.google.com/calendar/embed?src=nama%40gmail.com&ctz=...
     *   https://calendar.google.com/calendar/u/0?cid=nama%40gmail.com
     *   https://calendar.google.com/calendar/ical/nama%40gmail.com/private/basic.ics
     *   nama@gmail.com
     *   c_abc123@group.calendar.google.com
     */
    public static function extractCalendarId(?string $raw): ?string
    {
        $text = trim((string) $raw);

        if ($text === '') {
            return null;
        }

        // Bukan URL — anggap ia sudah ID.
        if (! str_contains($text, '/') && ! str_contains($text, '?')) {
            return $text;
        }

        // Parameter src= atau cid=
        $query = parse_url($text, PHP_URL_QUERY);

        if (is_string($query) && $query !== '') {
            parse_str($query, $params);

            foreach (['src', 'cid'] as $key) {
                $value = trim((string) ($params[$key] ?? ''));

                if ($value !== '') {
                    // cid kadangkala base64url. Nyahkod hanya jika hasilnya
                    // kelihatan seperti ID; teruskan dengan nilai asal jika
                    // tidak, kerana tekaan yang salah lebih teruk daripada
                    // tiada tekaan.
                    if ($key === 'cid' && ! str_contains($value, '@')) {
                        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

                        if (is_string($decoded) && str_contains($decoded, '@')) {
                            return trim($decoded);
                        }
                    }

                    return $value;
                }
            }
        }

        // Pautan iCal: /calendar/ical/<id>/private/basic.ics
        if (preg_match('#/calendar/ical/([^/]+)/#', $text, $m)) {
            return urldecode($m[1]);
        }

        return null;
    }

    /**
     * Adakah ini kelihatan seperti Calendar ID yang boleh digunakan?
     *
     * Diperiksa SEBELUM memanggil Google. Tanpa ini, ID yang salah bentuk
     * menghasilkan 403 dan mesej "kalendar belum dikongsi" — menghantar
     * admin membetulkan perkongsian yang sudah betul.
     */
    public static function looksLikeCalendarId(?string $id): bool
    {
        $text = trim((string) $id);

        return $text !== ''
            && str_contains($text, '@')
            && ! str_contains($text, ' ')
            && ! str_contains($text, '/');
    }

    public static function forYear(int $year): self
    {
        return static::firstOrCreate(['year' => $year]);
    }
}
