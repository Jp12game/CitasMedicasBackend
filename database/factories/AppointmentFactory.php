<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-30 days', '+30 days');
        $end   = (clone $start)->modify('+1 hour');

        return [
            'patient_id'      => Patient::factory(),
            'doctor_id'       => User::factory(),
            'date_time_begin' => $start,
            'date_time_end'   => $end,
            'status'          => fake()->randomElement(['scheduled', 'completed', 'cancelled']),
        ];
    }
}
