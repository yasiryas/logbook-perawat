<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('nurses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained();
            $table->string('name');
            $table->string('nip')->unique();
            $table->string('pk');
            $table->timestamps();
        });

        Schema::create('indicator_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->unsignedTinyInteger('weight_percent');
            $table->timestamps();
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('indicator_categories')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort');
            $table->string('name');
            $table->string('target')->default('100');
            $table->timestamps();

            $table->index(['category_id', 'unit_id', 'sort']);
        });

        Schema::create('monthly_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('working_days');
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['nurse_id', 'month', 'year']);
        });

        Schema::create('logbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('value', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['nurse_id', 'indicator_id', 'date']);
        });

        Schema::create('annual_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nurse_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('status')->default('draft');
            $table->string('assessor_name')->nullable();
            $table->string('assessor_nip')->nullable();
            $table->string('assessor_position')->nullable();
            $table->string('assessor_unit')->nullable();
            $table->string('superior_name')->nullable();
            $table->string('superior_nip')->nullable();
            $table->string('superior_position')->nullable();
            $table->string('superior_unit')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['nurse_id', 'year']);
        });

        Schema::create('annual_assessment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('annual_assessments')->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('target');
            $table->decimal('achievement', 12, 6)->default(0);
            $table->decimal('weight', 5, 2)->default(0);
            $table->decimal('score', 12, 6)->default(0);
            $table->timestamps();

            $table->unique(['assessment_id', 'indicator_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_assessment_items');
        Schema::dropIfExists('annual_assessments');
        Schema::dropIfExists('logbook_entries');
        Schema::dropIfExists('monthly_configs');
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('indicator_categories');
        Schema::dropIfExists('nurses');
        Schema::dropIfExists('units');
    }
};
