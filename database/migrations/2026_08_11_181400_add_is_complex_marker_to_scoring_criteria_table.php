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
        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->boolean('is_complex_marker')->default(false)->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->dropColumn('is_complex_marker');
        });
    }
};
