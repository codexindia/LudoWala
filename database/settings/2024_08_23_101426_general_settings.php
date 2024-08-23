<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.maintenance', 0);
        $this->migrator->add('general.forceUpdate', 1);
        $this->migrator->add('general.apkVersion', '1.0');
    }
};
