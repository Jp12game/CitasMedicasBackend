<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function records()
    {
        return $this->hasMany(Record::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOwnedByUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $patientQuery) use ($user) {
            $patientQuery->where('user_id', $user->id)
                ->orWhere(function (Builder $fallbackQuery) use ($user) {
                    $fallbackQuery->whereNull('user_id')
                        ->where('email', $user->email);
                });
        });
    }

    public function belongsToUser(User $user): bool
    {
        if ($this->user_id !== null) {
            return (int) $this->user_id === (int) $user->id;
        }

        return $this->email !== null && $this->email === $user->email;
    }
}
