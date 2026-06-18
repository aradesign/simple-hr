<?php

namespace Database\Seeders;

use App\Domain\Enums\SettingGroup;
use App\Services\Settings\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(SettingService::class);

        foreach ($service->defaults() as $group => $values) {
            $service->setMany($group, $values);
        }

        $this->command->info('Default settings seeded for: '.implode(', ', array_map(fn ($g) => SettingGroup::from($g)->label(), array_keys($service->defaults()))));
    }
}
