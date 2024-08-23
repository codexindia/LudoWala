<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SettingName extends Settings
{
    public string $apkVersion;
    public int $maintenance;
    public string $forceUpdate;
    public static function group(): string
    {
        return 'general';
    }
}