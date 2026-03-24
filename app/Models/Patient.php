<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

     protected $fillable = [
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'address',
    ];

    // Relaciones
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }
}
