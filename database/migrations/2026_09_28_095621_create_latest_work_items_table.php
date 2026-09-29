<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('latest_work_items', function (Blueprint $table) {
            $table->id();
            // Null on delete, so removing a category files its items as
            // untyped rather than taking them off the site.
            $table->foreignId('latest_work_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('details')->nullable();
            $table->string('image')->nullable();
            $table->string('project_url')->nullable();
            $table->json('technologies')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_visible', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('latest_work_items');
    }
};
