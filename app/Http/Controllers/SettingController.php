<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessSetting;

class SettingController extends Controller
{
    // Public method to get settings for frontend
    public function index()
    {
        $settings = BusinessSetting::all();
        return response()->json($settings);
    }
    
    // Admin methods handled in global route or specific controllers
    // Store/Update settings
    public function store(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($request->settings as $setting) {
            BusinessSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }
}
