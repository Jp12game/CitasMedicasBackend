<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $pacienteRole = Role::findOrCreate('paciente', 'web');

        Patient::factory(25)->make()->each(function (Patient $patient) use ($pacienteRole) {
            $user = User::factory()->create([
                'name' => $patient->name,
                'email' => $patient->email,
                'password' => 'password',
            ]);
            $user->assignRole($pacienteRole);

            $patient = Patient::query()->create([
                ...$patient->toArray(),
                'user_id' => $user->id,
            ]);

            Record::factory()->create(['patient_id' => $patient->id]);
        });
    }
}
