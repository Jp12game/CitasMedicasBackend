<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'date_time_begin',
        'date_time_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_time_begin' => 'datetime',
            'date_time_end' => 'datetime',
        ];
    }

    // Relaciones
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
