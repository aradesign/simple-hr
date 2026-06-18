<?php

use App\Domain\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::Candidate->value)->after('password');
            $table->boolean('hr_access')->default(false)->after('role');
            $table->unsignedBigInteger('person_id')->nullable()->after('hr_access');

            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['person_id']);
            $table->dropColumn(['role', 'hr_access', 'person_id']);
        });
    }
};
