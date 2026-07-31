<?php

declare(strict_types=1);

namespace App\Services\Sheets;

use App\Contracts\SheetReader;
use App\Exceptions\SheetReadException;
use App\Models\SheetIntegration;
use Illuminate\Support\Facades\Http;

/**
 * Membaca Google Sheet yang dikongsi "anyone with the link can view"
 * melalui endpoint eksport CSV Google. Tiada kunci API, tiada OAuth.
 *
 * Google menyediakan dua endpoint yang berfungsi tanpa auth untuk sheet
 * yang dikongsi melalui pautan:
 *
 *   /export?format=csv&gid=<gid>        — pantas, perlukan gid berangka
 *   /gviz/tq?tqx=out:csv&sheet=<nama>   — boleh rujuk tab mengikut NAMA
 *
 * Kami cuba endpoint yang paling spesifik dahulu, kemudian berundur.
 */
class LinkSheetReader implements SheetReader
{
    public function label(): string
    {
        return __('sheets.driver.link');
    }

    public function read(SheetIntegration $integration): array
    {
        $csv = $this->fetchCsv($integration);

        return $this->parseCsv($csv);
    }

    private function fetchCsv(SheetIntegration $integration): string
    {
        $id = $integration->spreadsheet_id;

        if (blank($id)) {
            throw SheetReadException::notFound();
        }

        foreach ($this->candidateUrls($integration) as $url) {
            $response = $this->request($url);

            if ($response === null) {
                continue;
            }

            // Google memulangkan HTML log masuk (bukan CSV) apabila sheet
            // tidak dikongsi secara awam — kesan ini dengan jelas.
            if ($this->looksLikeHtml($response)) {
                continue;
            }

            if (trim($response) !== '') {
                return $response;
            }
        }

        throw SheetReadException::notShared();
    }

    /** @return array<int, string> */
    private function candidateUrls(SheetIntegration $integration): array
    {
        $id = $integration->spreadsheet_id;
        $urls = [];

        // Sheet "publish to web" mempunyai ID berawalan 'e/'.
        if (str_starts_with((string) $id, 'e/')) {
            $pub = "https://docs.google.com/spreadsheets/d/{$id}/pub?output=csv";

            if (filled($integration->gid)) {
                $urls[] = $pub.'&gid='.$integration->gid;
            }

            $urls[] = $pub;

            return $urls;
        }

        $base = "https://docs.google.com/spreadsheets/d/{$id}";

        if (filled($integration->gid)) {
            $urls[] = "{$base}/export?format=csv&gid={$integration->gid}";
        }

        if (filled($integration->tab_name)) {
            $urls[] = "{$base}/gviz/tq?tqx=out:csv&sheet=".rawurlencode($integration->tab_name);
        }

        // Berundur: helaian pertama.
        $urls[] = "{$base}/export?format=csv";

        return $urls;
    }

    private function request(string $url): ?string
    {
        try {
            $response = Http::withOptions(['allow_redirects' => true])
                ->timeout((int) config('dbena.sheets.timeout_seconds'))
                ->withHeaders(['Accept' => 'text/csv,*/*'])
                ->get($url);
        } catch (\Throwable $e) {
            throw SheetReadException::network($e->getMessage());
        }

        if ($response->status() === 404) {
            throw SheetReadException::notFound();
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->body();
        $max = (int) config('dbena.sheets.max_bytes');

        if (strlen($body) > $max) {
            throw SheetReadException::tooLarge($max);
        }

        return $body;
    }

    private function looksLikeHtml(string $body): bool
    {
        $head = ltrim(substr($body, 0, 400));

        return str_starts_with($head, '<!DOCTYPE')
            || str_starts_with($head, '<html')
            || str_contains($head, '<HTML')
            || str_contains($head, 'accounts.google.com');
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        // Buang BOM UTF-8 yang kerap disisipkan Google.
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;

        $handle = fopen('php://memory', 'r+b');
        fwrite($handle, $csv);
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            // fgetcsv memulangkan [null] untuk baris kosong sepenuhnya.
            if ($row === [null]) {
                $rows[] = [];

                continue;
            }

            $rows[] = array_map(
                fn ($cell) => trim((string) ($cell ?? '')),
                $row
            );
        }

        fclose($handle);

        if ($rows === []) {
            throw SheetReadException::empty();
        }

        return $rows;
    }
}
