<?php

namespace Database\Seeders;

use App\Models\DoctorSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $weekdayTemplates = [
            [
                'day_of_week' => 1,
                'blocks' => [
                    ['start_time' => '08:00:00', 'end_time' => '12:00:00'],
                    ['start_time' => '13:00:00', 'end_time' => '17:00:00'],
                ],
            ],
            [
                'day_of_week' => 2,
                'blocks' => [
                    ['start_time' => '08:00:00', 'end_time' => '12:00:00'],
                    ['start_time' => '13:00:00', 'end_time' => '17:00:00'],
                ],
            ],
            [
                'day_of_week' => 3,
                'blocks' => [
                    ['start_time' => '08:00:00', 'end_time' => '12:00:00'],
                    ['start_time' => '13:00:00', 'end_time' => '17:00:00'],
                ],
            ],
            [
                'day_of_week' => 4,
                'blocks' => [
                    ['start_time' => '08:00:00', 'end_time' => '12:00:00'],
                    ['start_time' => '13:00:00', 'end_time' => '17:00:00'],
                ],
            ],
            [
                'day_of_week' => 5,
                'blocks' => [
                    ['start_time' => '08:00:00', 'end_time' => '12:00:00'],
                    ['start_time' => '13:00:00', 'end_time' => '16:00:00'],
                ],
            ],
            [
                'day_of_week' => 6,
                'blocks' => [
                    ['start_time' => '09:00:00', 'end_time' => '12:00:00'],
                ],
            ],
        ];

        $doctors = User::query()
            ->role('medico')
            ->get();

        foreach ($doctors as $doctor) {
            foreach ($weekdayTemplates as $template) {
                foreach ($template['blocks'] as $block) {
                    DoctorSchedule::query()->updateOrCreate(
                        [
                            'doctor_id' => $doctor->id,
                            'day_of_week' => $template['day_of_week'],
                            'start_time' => $block['start_time'],
                            'end_time' => $block['end_time'],
                        ],
                        [
                            'slot_duration' => 30,
                            'is_available' => true,
                        ],
                    );
                }
            }
        }
    }
}
