<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BettingPreferencesController extends Controller
{
    /**
     * Show the betting preferences page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/BettingPreferences', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's betting preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'favorite_team' => ['nullable', 'string', 'max:255'],
            'favorite_sport' => ['nullable', 'string', 'max:255'],
            'primary_betting_app' => ['nullable', 'string', 'max:255'],
        ]);

        $request->user()->update($validated);

        return to_route('betting-preferences.edit')->with('status', 'Betting preferences updated successfully!');
    }
}
