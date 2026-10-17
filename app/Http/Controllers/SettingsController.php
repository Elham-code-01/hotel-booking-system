<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\Audit;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['rate' => Setting::get('exchange_rate', '3.75')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exchange_rate' => ['required', 'numeric', 'between:0.01,100'],
        ]);

        Setting::put('exchange_rate', (string) $data['exchange_rate']);

        Audit::record('settings.updated', ['by' => $request->user()->email, 'exchange_rate' => $data['exchange_rate']]);

        return back()->with('success', 'Settings saved.');
    }
}
