<?php

use App\Domain\Enums\PersonLifecycleStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persons', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('national_id', 10)->nullable()->unique();
            $table->string('mobile', 15)->unique();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('lifecycle_status')->default(PersonLifecycleStatus::Applicant->value);
            $table->string('marital_status')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('profile_photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('lifecycle_status');
            $table->index('mobile');
            $table->index('national_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('person_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['person_id']);
        });

        Schema::dropIfExists('persons');
    }
};
