<?php

namespace App\Console\Commands;

use App\Services\Recruitment\GravityFormsCsvImportService;
use Illuminate\Console\Command;

class ImportGravityFormsApplications extends Command
{
    protected $signature = 'applications:import-csv
                            {path? : مسیر فایل CSV خروجی گرویتی‌فرمز}
                            {--force : وارد کردن مجدد حتی اگر قبلاً import شده باشد}';

    protected $description = 'وارد کردن درخواست‌های قدیمی گرویتی‌فرمز از فایل CSV';

    public function handle(GravityFormsCsvImportService $importService): int
    {
        $path = $this->argument('path')
            ?? storage_path('imports/gravity-resume-export.csv');

        if (! is_file($path)) {
            $this->error("فایل یافت نشد: {$path}");

            return self::FAILURE;
        }

        $this->info("در حال خواندن: {$path}");

        $result = $importService->importFromFile($path);

        $this->table(
            ['وضعیت', 'تعداد'],
            [
                ['وارد شد', $result['imported']],
                ['به‌روزرسانی شد', $result['updated']],
                ['رد شد', $result['skipped']],
                ['خطا', count($result['errors'])],
            ],
        );

        foreach ($result['errors'] as $error) {
            $this->warn($error);
        }

        $this->info('پایان import.');

        return $result['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}