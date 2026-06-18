<?php

namespace App\Console\Commands;

use App\Services\Calendar\CalendarService;
use Illuminate\Console\Command;

class SyncEmploymentCalendarEvents extends Command
{
    protected $signature = 'calendar:sync-employment';

    protected $description = 'سینک رویدادهای تقویم HR از سوابق همکاری (پایان قرارداد، دوره آزمایشی، تمدید)';

    public function handle(CalendarService $calendarService): int
    {
        $count = $calendarService->syncAllEmploymentRecordEvents();

        $this->info("{$count} سابقه همکاری بررسی و رویدادهای تقویم به‌روزرسانی شد.");

        return self::SUCCESS;
    }
}
