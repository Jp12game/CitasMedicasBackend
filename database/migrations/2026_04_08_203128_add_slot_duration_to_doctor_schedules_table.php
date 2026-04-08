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
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('slot_duration')->default(30)->after('end_time'); // minutes per appointment slot
            $table->boolean('is_available')->default(true)->after('slot_duration');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropColumn(['slot_duration', 'is_available']);
        });
    }
};
