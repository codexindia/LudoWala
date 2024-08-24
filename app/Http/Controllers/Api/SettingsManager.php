<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;
class SettingsManager extends Controller
{
    public function getSetting(Request $request)
    {
       // $setting = Settings::select('name','payload')->get();
        return response()->json([
            'status' => true,
            'forceUpdate' => get_setting('forceUpdate'),
            'apkVersion' => get_setting('apkVersion'),
        ]);
    }
}
