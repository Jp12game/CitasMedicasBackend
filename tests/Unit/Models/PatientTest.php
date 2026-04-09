<?php

use App\Models\Patient;

test('a patient belongs to a user', function () {
    $user = apiUser('paciente');
    $patient = Patient::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($patient->user->is($user))->toBeTrue();
});
