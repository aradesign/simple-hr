<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->string('managed_by_mobile', 15)->nullable()->after('mobile');
            $table->index('managed_by_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            $table->dropIndex(['managed_by_mobile']);
            $table->dropColumn('managed_by_mobile');
        });
    }
};
