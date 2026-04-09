<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('patients')
            ->select(['id', 'email'])
            ->whereNull('user_id')
            ->whereNotNull('email')
            ->orderBy('id')
            ->each(function (object $patient): void {
                $userId = DB::table('users')
                    ->where('email', $patient->email)
                    ->value('id');

                if ($userId !== null) {
                    DB::table('patients')
                        ->where('id', $patient->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
