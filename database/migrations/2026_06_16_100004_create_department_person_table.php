<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_person', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['department_id', 'person_id']);
        });

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                'ALTER TABLE department_person ADD active_person_key BIGINT UNSIGNED AS (IF(left_at IS NULL, person_id, NULL)) STORED'
            );
            DB::statement(
                'CREATE UNIQUE INDEX department_person_active_unique ON department_person (department_id, active_person_key)'
            );

            return;
        }

        DB::statement(
            'CREATE UNIQUE INDEX department_person_active_unique ON department_person (department_id, person_id) WHERE left_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('department_person');
    }
};
