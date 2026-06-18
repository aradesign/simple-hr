<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_form_fields', function (Blueprint $table) {
            $table->unsignedInteger('gravity_field_id')->nullable()->after('id');
            $table->text('description')->nullable()->after('label');
            $table->string('css_class', 100)->nullable()->after('options');
            $table->json('conditional_logic')->nullable()->after('css_class');
            $table->json('list_columns')->nullable()->after('conditional_logic');
            $table->string('layout_group_id', 50)->nullable()->after('list_columns');

            $table->index('gravity_field_id');
        });

        Schema::table('employment_applications', function (Blueprint $table) {
            $table->string('contact_mobile', 15)->nullable()->after('person_id');
            $table->index('contact_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('employment_applications', function (Blueprint $table) {
            $table->dropIndex(['contact_mobile']);
            $table->dropColumn('contact_mobile');
        });

        Schema::table('application_form_fields', function (Blueprint $table) {
            $table->dropIndex(['gravity_field_id']);
            $table->dropColumn([
                'gravity_field_id',
                'description',
                'css_class',
                'conditional_logic',
                'list_columns',
                'layout_group_id',
            ]);
        });
    }
};
