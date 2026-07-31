<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SyncSheetJob;
use App\Models\SheetIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Titik masuk untuk Apps Script yang dipasang dalam Google Sheet.
 *
 * Skrip menembak titik akhir ini setiap kali sel diedit, memberikan
 * kemas kini hampir masa-nyata tanpa menunggu kitaran jadual.
 *
 * Keselamatan:
 *   • Token per-integrasi dibandingkan menggunakan hash_equals (masa tetap)
 *   • Laluan dikecualikan daripada CSRF (permintaan luar), tetapi dihadkan kadar
 *   • Kerja sebenar dibaris gilirkan supaya Apps Script mendapat balasan segera
 */
class SheetWebhookController extends Controller
{
    public function __invoke(Request $request, SheetIntegration $integration, string $token): JsonResponse
    {
        $secret = (string) $integration->webhook_secret;

        if (blank($secret) || ! hash_equals($secret, $token)) {
            return response()->json(['ok' => false, 'error' => 'invalid_token'], 403);
        }

        if (! $integration->sync_enabled) {
            return response()->json(['ok' => false, 'error' => 'sync_disabled'], 409);
        }

        // Google boleh menembak berkali-kali semasa suntingan pukal —
        // hadkan kepada 20 pencetus seminit setiap integrasi.
        $key = "sheet-webhook:{$integration->id}";

        if (RateLimiter::tooManyAttempts($key, 20)) {
            return response()->json([
                'ok' => false,
                'error' => 'rate_limited',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $year = (int) $request->integer('year', (int) now()->year);
        $month = (int) $request->integer('month', (int) now()->month);

        $year = max(2000, min(2100, $year));
        $month = max(1, min(12, $month));

        // Tangguh sedikit supaya suntingan pukal mengendap sebelum kami membaca.
        SyncSheetJob::dispatch($integration->id, $year, $month, 'webhook')
            ->delay(now()->addSeconds(10));

        return response()->json([
            'ok' => true,
            'queued' => true,
            'service' => $integration->service?->key,
            'period' => sprintf('%04d-%02d', $year, $month),
        ]);
    }
}
