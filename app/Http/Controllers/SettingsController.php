<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = BusinessSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $old = BusinessSetting::pluck('value', 'key')->all();

        foreach ($validated['settings'] as $key => $value) {
            BusinessSetting::where('key', $key)->update(['value' => $value]);
        }

        AuditLogService::record([
            'action' => 'SETTINGS_UPDATED',
            'module' => 'Settings',
            'description' => 'Updated business control settings',
            'old_values' => $old,
            'new_values' => $validated['settings'],
            'severity' => 'WARNING',
        ]);

        return back()->with('success', 'Settings updated.');
    }
}

