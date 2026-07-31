<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PEMBETULAN isu #5 — dalam prototaip, "Admin Panel" hanyalah <a href> biasa
 * tanpa sebarang semakan. Di sini akses disekat di peringkat PELAYAN.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);
        abort_unless($user->is_active, 403);
        abort_unless(in_array($user->role->value, $roles, true), 403);

        return $next($request);
    }
}
