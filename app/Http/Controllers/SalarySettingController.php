<?php

namespace App\Http\Controllers;

use App\Models\SalarySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;


class SalarySettingController extends Controller
{


public function update(Request $request)
{
    $validated = $request->validate([
        'default_hourly_rate' => 'required|numeric|min:0',
    ]);

    $setting = SalarySetting::firstOrCreate([]);
    $setting->default_hourly_rate = $validated['default_hourly_rate'];
    $setting->save();

    return back()->with('success', 'تم تحديث أجر الساعة بنجاح');
}

}