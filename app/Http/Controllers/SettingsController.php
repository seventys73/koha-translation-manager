<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('settings.edit', [
            'kohaPath' => Setting::get('koha_path', DashboardController::DEFAULT_KOHA_PO_PATH),
            'targetLanguage' => Setting::get('target_language', ''),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'koha_path' => ['required', 'string'],
            'target_language' => ['nullable', 'string'],
        ]);

        Setting::set('koha_path', $data['koha_path']);
        Setting::set('target_language', $data['target_language'] ?? '');

        return redirect()
            ->route('settings.edit')
            ->with('status', 'Settings updated successfully.');
    }
}
