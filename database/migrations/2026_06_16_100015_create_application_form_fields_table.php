<?php

use App\Domain\Enums\FormFieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_key', 100)->unique();
            $table->string('label');
            $table->string('field_type')->default(FormFieldType::Text->value);
            $table->json('options')->nullable();
            $table->integer('step')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_required')->default(false);
            $table->timestamps();

            $table->index(['step', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_form_fields');
    }
};
