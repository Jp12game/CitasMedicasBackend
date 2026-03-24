<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Patient;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pacientes', Patient::count())
                ->description('Pacientes registrados')
                ->color('success'),

            Stat::make('Citas Hoy', Appointment::whereDate('date_time_begin', today())->count())
                ->description('Citas programadas para hoy')
                ->color('info'),

            Stat::make('Citas Esta Semana', Appointment::whereBetween('date_time_begin', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count())
                ->description('Citas en la semana actual')
                ->color('warning'),
        ];
    }
}
