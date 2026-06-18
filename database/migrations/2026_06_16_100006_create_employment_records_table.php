<?php

use App\Domain\Enums\EmploymentStatus;
use App\Domain\Enums\EmploymentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_code', 50)->unique();
            $table->string('employment_type')->default(EmploymentType::FullTime->value);
            $table->string('status')->default(EmploymentStatus::Active->value);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->string('position_title');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('person_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_records');
    }
};
