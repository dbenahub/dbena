<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);

        $request->user()?->update(['locale' => $locale]);

        return back();
    }
}
