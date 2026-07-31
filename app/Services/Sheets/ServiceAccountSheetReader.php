<?php

declare(strict_types=1);

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Exceptions\SheetReadException;
use App\Models\SheetIntegration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Membaca Google Sheet PERIBADI menggunakan service account Google Cloud.
 *
 * Setup (sekali sahaja):
 *   1. Google Cloud Console → cipta projek → aktifkan "Google Sheets API"
 *   2. Cipta Service Account → Keys → Add Key → JSON → muat turun
 *   3. Letak fail JSON di storage/app/google/service-account.json
 *      (JANGAN commit ke Git — .gitignore sudah menyekat storage/)
 *   4. Buka Google Sheet → Share → tambah emel client_email dari JSON
 *      sebagai Viewer
 *   5. .env → DBENA_SHEETS_DRIVER=service
 *
 * Kami menandatangani JWT sendiri dan menukarnya dengan token akses, jadi
 * pakej google/apiclient (~40 MB) tidak diperlukan.
 */
class ServiceAccountSheetReader implements SheetReader
{
    private const SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    private const TOKEN_CACHE_KEY = 'dbena.sheets.google_access_token';

    public function label(): string
    {
        return __('sheets.driver.service');
    }

    public function read(SheetIntegration $integration): array
    {
        $id = $integration->spreadsheet_id;

        if (blank($id)) {
            throw SheetReadException::notFound();
        }

        $range = filled($integration->tab_name)
            ? "'".str_replace("'", "''", $integration->tab_name)."'"
            : 'A:ZZ';

        try {
            $response = Http::withToken($this->accessToken())
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->get("https://sheets.googleapis.com/v4/spreadsheets/{$id}/values/".rawurlencode($range), [
                    'majorDimension' => 'ROWS',
                    'valueRenderOption' => 'UNFORMATTED_VALUE',
                ]);
        } catch (\Throwable $e) {
            throw SheetReadException::network($e->getMessage());
        }

        if ($response->status() === 403) {
            throw SheetReadException::notShared();
        }

        if ($response->status() === 404) {
            throw SheetReadException::notFound();
        }

        if (! $response->successful()) {
            throw SheetReadException::network($response->json('error.message') ?? (string) $response->status());
        }

        $values = $response->json('values', []);

        if ($values === []) {
            throw SheetReadException::empty();
        }

        return array_map(
            fn (array $row) => array_map(fn ($cell) => trim((string) $cell), $row),
            $values
        );
    }

    /** Tukar JWT bertandatangan dengan token akses OAuth2 (cache 50 minit). */
    private function accessToken(): string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function (): string {
            $credentials = $this->credentials();

            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claims = [
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ];

            $segments = [
                $this->base64Url(json_encode($header, JSON_THROW_ON_ERROR)),
                $this->base64Url(json_encode($claims, JSON_THROW_ON_ERROR)),
            ];

            $signingInput = implode('.', $segments);
            $signature = '';

            openssl_sign($signingInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

            $jwt = $signingInput.'.'.$this->base64Url($signature);

            $response = Http::asForm()
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

            if (! $response->successful()) {
                throw SheetReadException::network(
                    $response->json('error_description') ?? 'OAuth token exchange failed'
                );
            }

            return (string) $response->json('access_token');
        });
    }

    /** @return array{client_email: string, private_key: string} */
    private function credentials(): array
    {
        $path = (string) config('dbena.sheets.service_account.credentials_path');

        if (! is_readable($path)) {
            throw SheetReadException::missingCredentials($path);
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || blank($json['client_email'] ?? null) || blank($json['private_key'] ?? null)) {
            throw SheetReadException::missingCredentials($path);
        }

        return ['client_email' => $json['client_email'], 'private_key' => $json['private_key']];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
