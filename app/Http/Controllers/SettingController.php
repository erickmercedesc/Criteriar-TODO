<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $userId = auth()->id() ?? 1; // Fallback to 1 for generic single-user setup

        $settings = [
            'pomo_time' => Setting::getForUser($userId, 'pomo_time', 25),
            'short_break_time' => Setting::getForUser($userId, 'short_break_time', 5),
            'long_break_time' => Setting::getForUser($userId, 'long_break_time', 15),
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $userId = auth()->id() ?? 1;

        $validated = $request->validate([
            'pomo_time' => 'required|integer|min:1|max:120',
            'short_break_time' => 'required|integer|min:1|max:60',
            'long_break_time' => 'required|integer|min:1|max:60',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->back()->with('message', 'Configuración guardada exitosamente.');
    }
}
