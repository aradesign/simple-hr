<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy migration kept for history. Fresh installs already define
        // calendar_events.created_by as nullable in create_calendar_events_table.
    }

    public function down(): void
    {
        //
    }
};
