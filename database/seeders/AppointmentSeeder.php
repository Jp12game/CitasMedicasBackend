<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::all();
        $doctors  = User::role('doctor')->get();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            return;
        }

        // Generate 30 appointments spread across patients and doctors
        for ($i = 0; $i < 30; $i++) {
            $start = now()->subDays(rand(-15, 30))->setTime(rand(8, 16), 0, 0);
            $end   = (clone $start)->addHour();

            Appointment::create([
                'patient_id'      => $patients->random()->id,
                'doctor_id'       => $doctors->random()->id,
                'date_time_begin' => $start,
                'date_time_end'   => $end,
                'status'          => fake()->randomElement(['scheduled', 'completed', 'cancelled']),
            ]);
        }
    }
}
