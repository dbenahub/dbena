<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keputusan D1 — locale keutamaan:
 *   1. profil pengguna (users.locale) jika log masuk
 *   2. sesi (untuk guest di skrin log masuk)
 *   3. lalai aplikasi (ms)
 */
class SetLocale
{
    private const SUPPORTED = ['ms', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
