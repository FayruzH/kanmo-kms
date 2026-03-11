<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SopCategory;
use App\Models\SopDepartment;
use App\Models\SopSourceApp;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'expiryThreshold' => (int) (Setting::getValue('expiry_threshold_days', 30)),
            'categories' => SopCategory::query()->where('active', true)->orderBy('name')->pluck('name'),
            'departments' => SopDepartment::query()->where('active', true)->orderBy('name')->pluck('name'),
            'sourceApps' => SopSourceApp::query()->where('active', true)->orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'expiry_threshold_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'expiry_threshold_days'],
            ['value_json' => $data['expiry_threshold_days']]
        );

        return back()->with('success', 'Settings saved.');
    }
}
