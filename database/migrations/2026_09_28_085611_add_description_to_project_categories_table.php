<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each category doubles as a showcase section on the single-page site, so it
     * needs its own intro copy.
     */
    public function up(): void
    {
        Schema::table('project_categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('project_categories', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
