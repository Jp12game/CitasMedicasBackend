<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    use HasFactory;

     protected $fillable = [
        'patient_id',
        'weight',
        'height',
        'last_checkup_notes',
        'last_checkup_date',
    ];

    protected $casts = [
        'last_checkup_date' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
