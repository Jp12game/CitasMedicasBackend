<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Widgets\Widget;

class AppointmentCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.appointment-calendar-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $query = Appointment::with(['patient', 'doctor'])
            ->where('status', '!=', 'cancelled');

        if (auth()->user()?->hasRole('medico')) {
            $query->where('doctor_id', auth()->id());
        }

        $events = $query->get()->map(fn ($appointment) => [
            'id'    => $appointment->id,
            'title' => $appointment->patient->name . ' — ' . $appointment->doctor->name,
            'start' => $appointment->date_time_begin,
            'end'   => $appointment->date_time_end,
            'color' => match ($appointment->status) {
                'scheduled' => '#3b82f6',
                'completed' => '#22c55e',
                default     => '#6b7280',
            },
        ]);

        return ['events' => $events->toJson()];
    }
}
