<?php

use App\Domain\Enums\AssignmentRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->string('assignable_type', 100);
            $table->unsignedBigInteger('assignable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default(AssignmentRole::Assignee->value);
            $table->timestamps();

            $table->index(['assignable_type', 'assignable_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
