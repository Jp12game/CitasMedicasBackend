<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $doctor = $this->resource['doctor'];

        return [
            'doctor' => $doctor ? [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
            ] : null,
            'date' => $this->resource['date'],
            'slots' => $this->resource['slots'],
        ];
    }
}
