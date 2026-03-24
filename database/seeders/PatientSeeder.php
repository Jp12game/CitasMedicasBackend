<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Record;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::factory(25)->create()->each(function (Patient $patient) {
            Record::factory()->create(['patient_id' => $patient->id]);
        });
    }
}
