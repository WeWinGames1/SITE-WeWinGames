<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnderConstructionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class UnderConstructionController extends Controller
{
    public function index()
    {
        $settings = UnderConstructionSetting::first();

        return Inertia::render('admin/UnderConstruction/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'blurb' => 'nullable|string',
        ]);

        $settings = UnderConstructionSetting::firstOrCreate(
            [],
            $validated
        );

        $settings->update($validated);

        // Clear the cache so changes take effect immediately
        Cache::forget('under_construction_settings');

        return redirect()->route('admin.under-construction.index')
            ->with('success', 'Under construction settings updated successfully.');
    }
}
