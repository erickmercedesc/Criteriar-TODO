<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('base_score')->default(0)->after('color');
        });

        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
        });

        Schema::dropIfExists('project_scoring_criteria');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('project_scoring_criteria', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scoring_criterion_id')->constrained('scoring_criteria')->cascadeOnDelete();
            $table->primary(['project_id', 'scoring_criterion_id'], 'project_scoring_primary');
        });

        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('base_score');
        });
    }
};
