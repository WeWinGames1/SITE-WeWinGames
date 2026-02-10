<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiteSettingsController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::orderBy('group')->orderBy('label')->get();

        // Group settings by their group field
        $groupedSettings = $settings->groupBy('group')->map(function ($items) {
            return $items->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => SiteSetting::get($setting->key),
                    'type' => $setting->type,
                    'group' => $setting->group,
                    'label' => $setting->label,
                    'description' => $setting->description,
                ];
            });
        });

        return Inertia::render('admin/SiteSettings/Index', [
            'settings' => $settings,
            'groupedSettings' => $groupedSettings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:site_settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($validated['settings'] as $setting) {
            SiteSetting::set($setting['key'], $setting['value']);
        }

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Site settings updated successfully.');
    }
}
