<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Record>
 */
class RecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'          => Patient::factory(),
            'weight'              => fake()->randomFloat(2, 40, 150),
            'height'              => fake()->randomFloat(2, 140, 200),
            'last_checkup_date'   => fake()->dateTimeBetween('-2 years', 'now'),
            'last_checkup_notes'  => fake()->sentences(3, true),
        ];
    }
}
