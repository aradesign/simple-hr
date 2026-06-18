<?php

namespace Database\Seeders;

use App\Services\Recruitment\GravityFormsImportService;
use Illuminate\Database\Seeder;

class ApplicationFormFieldSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/gravity-employment-form.json');

        app(GravityFormsImportService::class)->importFromFile($path);
    }
}
