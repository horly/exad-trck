<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ApplyClientPreview;
use App\Models\Fleet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClientPreviewController extends Controller
{
    public function store(Request $request, Fleet $fleet): RedirectResponse
    {
        $request->session()->put(ApplyClientPreview::SESSION_KEY, $fleet->id);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(ApplyClientPreview::SESSION_KEY);

        return redirect()->route('fleets.index');
    }
}
