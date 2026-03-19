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
        Schema::create('record', function (Blueprint $table) {
             $table->id();

            $table->foreignId('patient_id')
                ->constrained('patient')
                ->cascadeOnDelete();

            $table->dateTime('birth_date');
            $table->decimal('weight', 5, 2); 
            $table->decimal('height', 4, 2);

            $table->text('last_checkup_notes')->nullable();
            $table->dateTime('last_checkup_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record');
    }
};
