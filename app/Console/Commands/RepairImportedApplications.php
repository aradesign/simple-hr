<?php

namespace App\Console\Commands;

use App\Services\Recruitment\GravityFormsCsvImportService;
use Illuminate\Console\Command;

class RepairImportedApplications extends Command
{
    protected $signature = 'applications:repair-imported';

    protected $description = 'اصلاح تاریخ تولد و کد ملی درخواست‌های import‌شده از گرویتی‌فرمز';

    public function handle(GravityFormsCsvImportService $importService): int
    {
        $result = $importService->repairImportedApplications();

        $this->info("{$result['fixed']} درخواست اصلاح شد.");

        if ($result['invalid_national_ids'] !== []) {
            $this->warn('کدهای ملی نامعتبر حذف شدند:');
            foreach ($result['invalid_national_ids'] as $nationalId) {
                $this->line(" - {$nationalId}");
            }
        }

        return self::SUCCESS;
    }
}
