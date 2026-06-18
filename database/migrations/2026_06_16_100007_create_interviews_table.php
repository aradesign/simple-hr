<?php

use App\Domain\Enums\InterviewStatus;
use App\Domain\Enums\InterviewType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignId('employment_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default(InterviewType::InPerson->value);
            $table->string('status')->default(InterviewStatus::Scheduled->value);
            $table->string('result')->nullable();
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('location')->nullable();
            $table->string('meeting_url', 500)->nullable();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('person_id');
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
