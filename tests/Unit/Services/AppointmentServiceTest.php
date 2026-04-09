<?php

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Patient;
use App\Services\AppointmentService;

test('an occupied slot returns false', function () {
    $doctor = apiUser('medico');
    $patient = Patient::factory()->create();

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '11:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    Appointment::factory()->create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'date_time_begin' => '2026-04-13 09:00:00',
        'date_time_end' => '2026-04-13 09:30:00',
        'status' => 'scheduled',
    ]);

    $service = new AppointmentService();

    expect($service->isSlotAvailable(
        $doctor->id,
        '2026-04-13 09:00:00',
        '2026-04-13 09:30:00',
    ))->toBeFalse();
});

test('a free slot returns true', function () {
    $doctor = apiUser('medico');

    DoctorSchedule::query()->create([
        'doctor_id' => $doctor->id,
        'day_of_week' => 1,
        'start_time' => '09:00:00',
        'end_time' => '11:00:00',
        'slot_duration' => 30,
        'is_available' => true,
    ]);

    $service = new AppointmentService();

    expect($service->isSlotAvailable(
        $doctor->id,
        '2026-04-13 09:00:00',
        '2026-04-13 09:30:00',
    ))->toBeTrue();
});
