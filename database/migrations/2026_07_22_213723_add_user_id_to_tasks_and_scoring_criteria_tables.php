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
        // Asegurar que exista un usuario ID=1 para los registros existentes
        $userExists = \Illuminate\Support\Facades\DB::table('users')->where('id', 1)->exists();
        if (!$userExists) {
            \Illuminate\Support\Facades\DB::table('users')->insert([
                'id' => 1,
                'name' => 'Admin Default',
                'email' => 'admin@secondbrain.local',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('user_id')->default(1)->constrained()->cascadeOnDelete();
        });

        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->foreignId('user_id')->default(1)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });

        Schema::table('scoring_criteria', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
